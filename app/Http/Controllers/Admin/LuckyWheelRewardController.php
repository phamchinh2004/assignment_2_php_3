<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LuckyWheelSetting;
use App\Models\LuckyWheelSpin;
use App\Services\LuckyWheelRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LuckyWheelRewardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([
                LuckyWheelSpin::STATUS_PENDING,
                LuckyWheelSpin::STATUS_APPROVED,
                LuckyWheelSpin::STATUS_REJECTED,
            ])],
        ]);

        $query = LuckyWheelSpin::query()
            ->with(['user', 'handledBy'])
            ->where('reward_type', LuckyWheelSpin::REWARD_CASH)
            ->whereIn('reward_status', [
                LuckyWheelSpin::STATUS_PENDING,
                LuckyWheelSpin::STATUS_APPROVED,
                LuckyWheelSpin::STATUS_REJECTED,
            ]);

        if (!empty($validated['status'])) {
            $query->where('reward_status', $validated['status']);
        }

        $rewards = $query->latest('id')->paginate(20)->withQueryString();

        $baseQuery = LuckyWheelSpin::query()->where('reward_type', LuckyWheelSpin::REWARD_CASH);
        $counts = [
            'total' => (clone $baseQuery)->whereIn('reward_status', [
                LuckyWheelSpin::STATUS_PENDING,
                LuckyWheelSpin::STATUS_APPROVED,
                LuckyWheelSpin::STATUS_REJECTED,
            ])->count(),
            'pending' => (clone $baseQuery)->where('reward_status', LuckyWheelSpin::STATUS_PENDING)->count(),
            'approved' => (clone $baseQuery)->where('reward_status', LuckyWheelSpin::STATUS_APPROVED)->count(),
            'rejected' => (clone $baseQuery)->where('reward_status', LuckyWheelSpin::STATUS_REJECTED)->count(),
            'paid_amount' => (float) (clone $baseQuery)->where('reward_status', LuckyWheelSpin::STATUS_APPROVED)->sum('reward_amount'),
        ];

        return view('admin.lucky-wheel-rewards.index', [
            'rewards' => $rewards,
            'counts' => $counts,
            'setting' => LuckyWheelSetting::current(),
            'selectedStatus' => $validated['status'] ?? null,
        ]);
    }

    public function approve(LuckyWheelSpin $spin, LuckyWheelRewardService $rewardService)
    {
        $wasApproved = $spin->reward_status === LuckyWheelSpin::STATUS_APPROVED;
        $approved = $rewardService->approve($spin, Auth::user(), LuckyWheelSpin::APPROVAL_MANUAL);

        return back()->with(
            'success',
            $wasApproved
                ? 'Phần thưởng này đã được duyệt trước đó.'
                : 'Đã duyệt phần thưởng ' . number_format((float) $approved->reward_amount, 2) . '$ và cộng tiền thưởng vào tài khoản người dùng.'
        );
    }

    public function reject(LuckyWheelSpin $spin, LuckyWheelRewardService $rewardService)
    {
        $wasRejected = $spin->reward_status === LuckyWheelSpin::STATUS_REJECTED;
        $rewardService->reject($spin, Auth::user());

        return back()->with(
            'success',
            $wasRejected
                ? 'Phần thưởng này đã được từ chối trước đó.'
                : 'Đã từ chối phần thưởng. Không có tiền thưởng nào được cộng vào tài khoản người dùng.'
        );
    }

    public function updateAutoApproval(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $setting = LuckyWheelSetting::current();
        $setting->update([
            'auto_approve_rewards' => (bool) $validated['enabled'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with(
            'success',
            $setting->auto_approve_rewards
                ? 'Đã bật tự động duyệt. Các phần thưởng tiền mặt từ lượt quay mới sẽ được cộng ngay.'
                : 'Đã tắt tự động duyệt. Phần thưởng tiền mặt mới sẽ chờ quản trị viên duyệt.'
        );
    }
}
