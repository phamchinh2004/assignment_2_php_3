<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Rank;
use App\Services\ApproximateLocationService;
use App\Services\ReactPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ReactPageService $reactPageService)
    {
        $user = Auth::user();
        $rank = Rank::find($user->rank_id);

        $accountSummary = [
            'received_commission' => (float) $user->transaction_histories()
                ->where('type', 'profit')
                ->sum('value'),
            'today_transactions' => $user->wallet_balance_histories()
                ->whereDate('created_at', today())
                ->count(),
        ];

        $statusClass = match ($user->status) {
            'activated' => 'is-active',
            'banned' => 'is-banned',
            default => 'is-inactive',
        };
        $statusLabel = match ($user->status) {
            'activated' => __('me.HoatDong'),
            'banned' => __('me.BiCam'),
            'inactivated' => __('me.ChuaKichHoat'),
            default => __('me.KhongHoatDong'),
        };

        return $reactPageService->user('user.me', [
            'user' => [
                'fullName' => $user->full_name,
                'username' => $user->username,
                'referralCode' => $user->referral_code,
                'avatarUrl' => get_user_avatar($user),
                'balanceFormatted' => format_money($user->balance),
                'frozenBalanceFormatted' => format_money($user->frozen_balance ?? 0),
                'warehouseArea' => $user->warehouse_area,
                'warehouseAddress' => $user->warehouse_address,
                'hasBankAccount' => filled($user->bank_name) && filled($user->account_number),
                'hasWarehouse' => filled($user->warehouse_area) && filled($user->warehouse_address),
                'hasTransactionPassword' => filled($user->transaction_password),
                'statusClass' => $statusClass,
                'statusLabel' => $statusLabel,
            ],
            'rank' => $rank ? ['name' => $rank->name] : null,
            'accountSummary' => [
                'receivedCommissionFormatted' => format_money($accountSummary['received_commission']),
                'todayTransactions' => $accountSummary['today_transactions'],
            ],
            'routes' => [
                'personalInformation' => route('personal_information'),
                'balanceFluctuation' => route('balance_fluctuation'),
                'withdrawMoney' => route('withdraw_money'),
                'distribution' => route('distribution'),
                'order' => route('order'),
                'vip' => route('vip'),
                'warehouseAddressUpdate' => route('warehouse_address.update'),
                'languageChange' => route('language.change'),
            ],
            'languages' => Language::query()->get()->map(fn (Language $language) => [
                'code' => $language->code,
                'name' => $language->name,
                'imageUrl' => Storage::url($language->image),
            ])->values()->all(),
            'locale' => App::getLocale(),
            'assets' => [
                'defaultAvatar' => asset('images/default-avatar-gray.svg'),
            ],
            'labels' => [
                'inviteCode' => __('me.MaMoi'),
                'accountBalance' => __('me.SoDuTaiKhoan'),
                'currentBalance' => __('me.SoDuHienTai'),
                'todayTransactions' => __('me.GiaoDichHomNay'),
                'deposit' => __('me.Nap'),
                'withdraw' => __('me.Rut'),
                'distribution' => __('me.PhanPhoi'),
                'balanceMovement' => __('me.BienDong'),
                'vip' => __('me.Vip'),
                'information' => __('me.ThongTin'),
                'warehouseAddress' => __('me.DiaChiKho'),
                'depositHistory' => __('me.LichSuNap'),
                'withdrawHistory' => __('me.LichSuRut'),
                'language' => __('me.NgonNgu'),
                'logout' => __('me.DangXuat'),
                'warehouseModalTitle' => __('me.TieuDeModalDiaChiKho'),
                'area' => __('me.KhuVuc'),
                'areaPlaceholder' => __('me.NhapKhuVuc'),
                'currentAddress' => __('me.DiaChiHienTai'),
                'addressPlaceholder' => __('me.NhapDiaChiHienTai'),
                'close' => __('me.Dong'),
                'save' => __('me.Luu'),
                'contactSupport' => __('me.VuiLongLienHeCskh'),
                'notificationTitle' => __('me.ThongBao'),
            ],
        ]);
    }
    public function personal_information(ReactPageService $reactPageService)
    {
        $user = Auth::user();
        $banks = config('banks', []);
        return $reactPageService->user('user.personal_information', [
            'legacyPage' => 'personal_information',
            'html' => view('user.partials.personal_information-content', compact('user', 'banks'))->render(),
            'globals' => [],
        ]);
    }
    public function vip(ReactPageService $reactPageService)
    {
        $user = Auth::user();
        $rank = Rank::find($user->rank_id);
        $list_ranks = Rank::query()
            ->orderBy('upgrade_fee')
            ->orderBy('id')
            ->get();
        $currentRankIndex = $rank
            ? $list_ranks->search(fn (Rank $item) => $item->is($rank))
            : false;
        $nextRank = $currentRankIndex !== false
            ? $list_ranks->get($currentRankIndex + 1)
            : $list_ranks->first();

        $serializeRank = static fn (Rank $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'imageUrl' => $item->image ? Storage::url($item->image) : null,
            'upgradeFee' => (float) $item->upgrade_fee,
            'upgradeFeeFormatted' => format_money($item->upgrade_fee),
            'commissionPercentage' => (float) $item->commission_percentage,
            'commissionPercentageFormatted' => format_money($item->commission_percentage),
            'spinCount' => (int) $item->spin_count,
            'maximumNumberOfWithdrawals' => (int) $item->maximum_number_of_withdrawals,
            'maximumWithdrawalAmount' => (float) $item->maximum_withdrawal_amount,
            'maximumWithdrawalAmountFormatted' => format_money($item->maximum_withdrawal_amount),
            'value' => (float) $item->value,
            'valueFormatted' => format_money($item->value),
        ];

        return $reactPageService->user('user.vip', [
            'user' => [
                'fullName' => $user->full_name,
                'username' => $user->username,
                'avatarUrl' => get_user_avatar($user),
            ],
            'rank' => $rank ? $serializeRank($rank) : null,
            'ranks' => $list_ranks->map($serializeRank)->values()->all(),
            'currentRankIndex' => $currentRankIndex === false ? null : $currentRankIndex,
            'nextRankId' => $nextRank?->id,
            'routes' => [
                'me' => route('me'),
            ],
            'assets' => [
                'defaultAvatar' => asset('images/default-avatar-gray.svg'),
            ],
            'labels' => [
                'title' => __('vip.CapDoThanhVien'),
                'noRank' => __('vip.BanChuaCoGianHang'),
            ],
        ], __('vip.CapDoThanhVien'));
    }

    public function upload_avatar(Request $request)
    {
        $user = Auth::user();
        
        // Validate request
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        
        try {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // Store new avatar
            $avatarPath = $request->file('avatar')->store('uploads/images/avatars', 'public');
            
            // Update user avatar
            $user->avatar = $avatarPath;
            $user->save();
            
            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật ảnh đại diện thành công!',
                'avatar_url' => asset('storage/' . $avatarPath)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Có lỗi xảy ra khi cập nhật ảnh đại diện: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * Cập nhật địa chỉ kho cho user hiện tại.
     */
    public function updateWarehouseAddress(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'warehouse_area' => ['required', 'string', 'max:191'],
            'warehouse_address' => ['required', 'string', 'max:1000'],
        ]);

        $user->update($validated);

        return back()->with('success', __('me.CapNhatDiaChiKhoThanhCong'));
    }

    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'permission' => ['required', 'in:granted,denied'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_if:permission,granted'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_if:permission,granted'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'country' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
        ]);

        $user = Auth::user();
        $user->location_permission = $validated['permission'];
        $hasPreciseLocation = $validated['permission'] === 'granted';
        $user->location_latitude = $hasPreciseLocation ? ($validated['latitude'] ?? null) : null;
        $user->location_longitude = $hasPreciseLocation ? ($validated['longitude'] ?? null) : null;
        $user->location_accuracy = $hasPreciseLocation ? ($validated['accuracy'] ?? null) : null;
        $user->location_country_code = $hasPreciseLocation && isset($validated['country_code'])
            ? strtoupper($validated['country_code'])
            : null;
        $user->location_country = $hasPreciseLocation ? ($validated['country'] ?? null) : null;
        $user->location_city = $hasPreciseLocation ? ($validated['city'] ?? null) : null;
        $user->location_updated_at = now();
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => $validated['permission'] === 'granted'
                ? 'Đã cập nhật vị trí hiện tại.'
                : 'Bạn đã từ chối quyền truy cập vị trí.',
            'location_permission' => $user->location_permission,
        ]);
    }

    public function updateApproximateLocation(Request $request, ApproximateLocationService $approximateLocationService)
    {
        $user = Auth::user();
        $forceRefreshKey = 'approx-location-force-refresh:' . $user->id;
        $forceRefresh = $request->boolean('force') || Cache::has($forceRefreshKey);
        $resolved = $approximateLocationService->refresh($user, $request, $forceRefresh);

        if ($forceRefresh && $resolved) {
            Cache::forget($forceRefreshKey);
        }

        $user->refresh();

        return response()->json([
            'status' => $resolved ? 200 : 503,
            'country_code' => $user->approx_location_country_code,
            'country' => $user->approx_location_country,
            'updated_at' => $user->approx_location_updated_at?->toIso8601String(),
        ], $resolved ? 200 : 503);
    }

}
