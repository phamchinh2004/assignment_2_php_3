<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Services\ApproximateLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
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

        return view('user.me', compact('user', 'rank', 'accountSummary'));
    }
    public function personal_information()
    {
        $user = Auth::user();
        $banks = config('banks', []);
        return view('user.personal_information', compact('user','banks'));
    }
    public function vip()
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

        return view('user.vip', compact('user', 'rank', 'list_ranks', 'nextRank', 'currentRankIndex'));
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
