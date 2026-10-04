<?php

namespace App\Services;

use App\Models\Frozen_order;
use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAutoSpinService
{
    public function __construct(
        private readonly OrderSpinService $spins,
        private readonly OrderConfirmationService $confirmation,
    ) {
    }

    public function state(User $user): array
    {
        $progress = User_spin_progress::where('user_id', $user->id)->first();
        $user->load('rank');
        $pendingId = Frozen_order::where('user_id', $user->id)
            ->where('spun', true)
            ->where('is_frozen', true)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', 'pending'))
            ->orderBy('snapshot_order_index')->orderBy('id')->value('id');

        return [
            'current_spin' => (int) ($progress?->current_spin ?? 0),
            'total_spins' => (int) ($user->rank?->spin_count ?? 0),
            'pending_order_id' => $pendingId !== null ? (int) $pendingId : null,
        ];
    }

    /** One receive/confirm cycle per request; the browser repeats until the target is confirmed. */
    public function step(User $member, int $target, int $expectedSpin, ?int $expectedPendingId, int $actorId): array
    {
        return DB::transaction(function () use ($member, $target, $expectedSpin, $expectedPendingId, $actorId) {
            // Serializes automatic runs with the normal customer receive flow.
            $user = User::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $progress = User_spin_progress::where('user_id', $user->id)->lockForUpdate()->first();
            $actor = User::findOrFail($actorId);
            abort_unless($user->role === User::ROLE_MEMBER, 404);
            abort_unless($actor->canAccessCustomer($user)
                && app(AuthorizationService::class)->can($actor, config('authorization.capabilities.customers_auto_spin')), 403);
            $state = $this->state($user);

            if ($user->status === 'banned') {
                return array_merge($state, ['status' => 409, 'message' => 'Tài khoản đang bị khóa, không thể quay đơn.']);
            }
            if (!$progress || !$user->rank || (int) $progress->rank_id !== (int) $user->rank_id) {
                throw ValidationException::withMessages(['target_spin' => 'Tài khoản chưa có cấp bậc hoặc tiến trình quay hợp lệ.']);
            }
            if ($target < $state['current_spin'] || $target > $state['total_spins']) {
                throw ValidationException::withMessages(['target_spin' => 'Đơn đích phải từ lượt hiện tại đến tổng số lượt quay của tài khoản.']);
            }
            if ($expectedSpin !== $state['current_spin'] || $expectedPendingId !== $state['pending_order_id']) {
                return array_merge($state, ['status' => 409, 'message' => 'Tiến trình đã thay đổi. Vui lòng kiểm tra lại trước khi tiếp tục.']);
            }
            if ($state['current_spin'] === $target && !$state['pending_order_id']) {
                return array_merge($state, ['status' => 200, 'done' => true, 'message' => 'Đã đạt đơn đích.']);
            }

            // The operator authorizes the run; order actions belong to the customer.
            $received = $this->spins->receive((int) $user->id, (int) $user->id)->getData(true);
            if ($received['status'] !== 200) {
                return array_merge($this->state($user), ['status' => $received['status'], 'message' => $received['message']]);
            }

            $pendingId = $received['frozen_id'] ?? $this->state($user)['pending_order_id'];
            $pending = Frozen_order::where('user_id', $user->id)->where('spun', true)->find($pendingId);
            if (!$pending) {
                return array_merge($this->state($user), ['status' => 409, 'message' => 'Không tìm thấy đơn đã nhận để xác nhận.']);
            }

            // Keeps snapshots, balance checks, penalties, history and delivery jobs identical to customer confirmation.
            $confirmed = $this->confirmation->confirm($user->refresh(), $pending, (int) $user->id)->getData(true);
            $state = $this->state($user->refresh());

            return array_merge($state, [
                'status' => $confirmed['status'],
                'message' => $confirmed['message'],
                'confirmed_order_id' => $confirmed['status'] === 200 ? $pending->id : null,
                'done' => $confirmed['status'] === 200 && $state['current_spin'] === $target && !$state['pending_order_id'],
            ]);
        });
    }
}
