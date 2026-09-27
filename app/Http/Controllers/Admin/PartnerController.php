<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage, private readonly AuthorizationService $authorization)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_partners = Partner::get();
        $user = auth()->user();
        return $this->reactPage->admin('admin.partners.index', [
            'items' => $list_partners,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'create' => route('partner.create'),
                'show' => route('partner.show', ['partner' => '__PARTNER_ID__']),
                'edit' => route('partner.edit', ['partner' => '__PARTNER_ID__']),
                'destroy' => route('partner.destroy', ['partner' => '__PARTNER_ID__']),
            ],
            'permissions' => [
                'create' => $this->authorization->can($user, config('authorization.capabilities.partners_create')),
                'viewDetail' => $this->authorization->can($user, config('authorization.capabilities.partners_view_detail')),
                'update' => $this->authorization->can($user, config('authorization.capabilities.partners_update')),
                'delete' => $this->authorization->can($user, config('authorization.capabilities.partners_delete')),
            ],
        ], 'Danh sách đối tác');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->reactPage->admin('admin.partners.create', ['routes' => ['index' => route('partner.index'), 'store' => route('partner.store')]], 'Thêm mới đối tác');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartnerRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'link']);
            if ($request->hasFile('image')) {
                $file = $request->image;
                $file_name = $file->store('uploads/images/partners', 'public');
                $data['image'] = $file_name;
            }
            Partner::create($data);
            DB::commit();
            return redirect()->route('partner.index')->with('success', 'Thêm mới đối tác thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    public function show(Partner $partner)
    {
        return $this->reactPage->admin('admin.partners.show', [
            'item' => $partner,
            'storageBaseUrl' => asset('storage'),
            'routes' => ['index' => route('partner.index'), 'edit' => route('partner.edit', $partner)],
            'permissions' => ['update' => $this->authorization->can(auth()->user(), config('authorization.capabilities.partners_update'))],
        ], "Chi tiết đối tác — {$partner->name}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner)
    {
        return $this->reactPage->admin('admin.partners.edit', [
            'item' => $partner,
            'storageBaseUrl' => asset('storage'),
            'routes' => ['index' => route('partner.index'), 'update' => route('partner.update', $partner)],
        ], "Chỉnh sửa đối tác — {$partner->name}");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        try {
            $data = $request->only(['name', 'link']);

            // Nếu có ảnh mới
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $file_name = $file->store("uploads/images/partners", "public");

                // Xoá ảnh cũ nếu tồn tại
                if (Storage::exists($partner->image)) {
                    Storage::delete($partner->image);
                }

                $data['image'] = $file_name;
            }

            $partner->update($data);

            return redirect()->route('partner.index')->with('success', 'Cập nhật đối tác thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner)
    {
        try {
            // Xoá ảnh nếu tồn tại
            if (Storage::exists($partner->image)) {
                Storage::delete($partner->image);
            }

            // Xoá bản ghi
            $partner->delete();

            return redirect()->route('partner.index')->with('success', 'Xoá đối tác thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Đã xảy ra lỗi khi xoá: ' . $e->getMessage());
        }
    }
}
