<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Http\Requests\StoreRankRequest;
use App\Http\Requests\UpdateRankRequest;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RankController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage, private readonly AuthorizationService $authorization)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rank = Rank::withCount('orders')->get();
        $user = auth()->user();

        return $this->reactPage->admin('admin.ranks.index', [
            'ranks' => $rank,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'create' => route('rank.create'),
                'show' => route('rank.show', ['rank' => '__RANK_ID__']),
                'edit' => route('rank.edit', ['rank' => '__RANK_ID__']),
                'destroy' => route('rank.destroy', ['rank' => '__RANK_ID__']),
            ],
            'permissions' => [
                'create' => $this->authorization->can($user, config('authorization.capabilities.ranks_create')),
                'viewDetail' => $this->authorization->can($user, config('authorization.capabilities.ranks_view_detail')),
                'update' => $this->authorization->can($user, config('authorization.capabilities.ranks_update')),
                'delete' => $this->authorization->can($user, config('authorization.capabilities.ranks_delete')),
            ],
        ], 'Danh sách cấp độ');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->reactPage->admin('admin.ranks.create', [
            'routes' => ['index' => route('rank.index'), 'store' => route('rank.store')],
        ], 'Thêm mới cấp độ');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRankRequest $request)
    {
        $data = $request->only(['name', 'commission_percentage', 'upgrade_fee', 'spin_count', 'value', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount']);
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $file_name = $file->store("uploads/images/ranks", "public");
            $data['image'] = $file_name;
        }
        Rank::create($data);
        return redirect()->route('rank.index')->with('success', 'Tạo cấp độ thành công!');
    }

    public function show(Rank $rank)
    {
        $rank->loadCount('orders');
        $users = \App\Models\User::where('rank_id', $rank->id)->latest()->paginate(10);
        $user = auth()->user();

        return $this->reactPage->admin('admin.ranks.show', [
            'rank' => $rank,
            'users' => $users,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'index' => route('rank.index'),
                'edit' => route('rank.edit', $rank),
                'userShow' => route('user.show', ['user' => '__USER_ID__']),
            ],
            'permissions' => [
                'update' => $this->authorization->can($user, config('authorization.capabilities.ranks_update')),
                'viewCustomerDetail' => $this->authorization->can($user, config('authorization.capabilities.customers_view_detail')),
            ],
        ], "Chi tiết cấp độ — {$rank->name}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rank $rank)
    {
        return $this->reactPage->admin('admin.ranks.edit', [
            'rank' => $rank,
            'storageBaseUrl' => asset('storage'),
            'routes' => ['index' => route('rank.index'), 'update' => route('rank.update', $rank)],
        ], "Chỉnh sửa cấp độ — {$rank->name}");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRankRequest $request, Rank $rank)
    {
        $data = $request->only(['name', 'commission_percentage', 'upgrade_fee', 'spin_count', 'value', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount']);
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($rank->image &&  Storage::exists($rank->image)) {
                Storage::delete($rank->image);
            }
            $file = $request->file('image');
            $file_name = $file->store('uploads/images/ranks', 'public');
            $data['image'] = $file_name;
        }
        DB::transaction(function () use ($rank, $data) {
            $oldCommissionPercentage = $rank->commission_percentage;

            $rank->update($data);

            if ((float) $oldCommissionPercentage !== (float) $rank->commission_percentage) {
                $rank->orders()->update([
                    'commission_percentage' => $rank->commission_percentage,
                ]);
            }
        });

        return redirect()->route('rank.index')->with('success', 'Cập nhật cấp độ thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rank $rank)
    {
        $isInUse = DB::table('users')->where('rank_id', $rank->id)->exists()
            || DB::table('orders')->where('rank_id', $rank->id)->exists()
            || DB::table('user_spin_progresses')->where('rank_id', $rank->id)->exists();

        if ($isInUse) {
            return redirect()->route('rank.index')
                ->with('error', 'Không thể xóa cấp độ đang được người dùng, đơn hàng hoặc tiến trình quay sử dụng.');
        }

        $image = $rank->image;
        $rank->delete();

        if ($image && Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->route('rank.index')->with('success', 'Xóa cấp độ thành công!');
    }
}
