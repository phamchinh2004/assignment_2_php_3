<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Controllers\Controller;
use App\Models\Banner_image;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function __construct(
        private readonly ReactPageService $reactPage,
        private readonly AuthorizationService $authorization,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_banners = Banner::with('banner_images')->get();
        $user = auth()->user();

        return $this->reactPage->admin('admin.banners.index', [
            'banners' => $list_banners,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'create' => route('banner.create'),
                'show' => route('banner.show', ['banner' => '__BANNER_ID__']),
                'edit' => route('banner.edit', ['banner' => '__BANNER_ID__']),
                'destroy' => route('banner.destroy', ['banner' => '__BANNER_ID__']),
                'changeStatus' => route('banner.change.status', ['banner' => '__BANNER_ID__']),
            ],
            'permissions' => [
                'create' => $this->authorization->can($user, config('authorization.capabilities.banners_create')),
                'viewDetail' => $this->authorization->can($user, config('authorization.capabilities.banners_view_detail')),
                'update' => $this->authorization->can($user, config('authorization.capabilities.banners_update')),
                'delete' => $this->authorization->can($user, config('authorization.capabilities.banners_delete')),
                'changeStatus' => $this->authorization->can($user, config('authorization.capabilities.banners_change_status')),
            ],
        ], 'Danh sách banner');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->reactPage->admin('admin.banners.create', [
            'routes' => [
                'index' => route('banner.index'),
                'store' => route('banner.store'),
            ],
        ], 'Thêm mới banner');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBannerRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->only(['name']);
            $data['images'] = [];
            if ($request->hasFile(key: 'images')) {
                foreach ((array)$request->file('images') as $file) {
                    if ($file->isValid()) {
                        $file_name = $file->store('uploads/images/banners', 'public');
                        $data['images'][] = $file_name;
                    }
                }
            }
            $newBanner = Banner::create([
                'name' => $data['name'],
                'status' => 0
            ]);

            foreach ($data['images'] as $item) {
                Banner_image::create([
                    'path' => $item,
                    'banner_id' => $newBanner->id
                ]);
            }

            DB::commit();
            return redirect()->route('banner.index')->with('success', 'Tạo banner thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    public function change_status_banner(Banner $banner)
    {
        $message = "";
        if ($banner) {
            if (!$banner->status) {
                $get_banners_activated = Banner::where('id', '!=', $banner->id)->where('status', 1)->get();
                if ($get_banners_activated) {
                    foreach ($get_banners_activated as $item) {
                        $item->status = !$item->status;
                        $item->save();
                    }
                }

                $banner->status = 1;
                $message = "Đã kích hoạt banner '" . $banner->name . "' thành công!";
            } else {
                $banners = Banner::count();
                if ($banners > 1) {
                    $get_banner = Banner::where('id', '!=', $banner->id)->orderBy('created_at', 'desc')->first();
                    if ($get_banner) {
                        $get_banner->status = !$get_banner->status;
                        $get_banner->save();
                    }
                    $banner->status = 0;
                    $message = "Đã ngừng kích hoạt banner '" . $banner->name . "' thành công!";
                } else {
                    $message = "Không thể tắt banner này, yêu cầu ít nhất 1 banner hoạt động!";
                }
            }
            $banner->save();
            return redirect()->route('banner.index')->with('success', $message);
        } else {
            return redirect()->route('banner.index')->with('error', 'Không tìm thấy banner cần thay đổi trạng thái!');
        }
    }
    public function show(Banner $banner)
    {
        $banner->load('banner_images');
        $user = auth()->user();

        return $this->reactPage->admin('admin.banners.show', [
            'banner' => $banner,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'index' => route('banner.index'),
                'edit' => route('banner.edit', ['banner' => $banner->id]),
            ],
            'permissions' => [
                'update' => $this->authorization->can($user, config('authorization.capabilities.banners_update')),
            ],
        ], "Chi tiết banner — {$banner->name}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        // Load relationship để lấy các ảnh của banner
        $banner->load('banner_images');
        return $this->reactPage->admin('admin.banners.edit', [
            'banner' => $banner,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'index' => route('banner.index'),
                'update' => route('banner.update', ['banner' => $banner->id]),
            ],
        ], "Chỉnh sửa banner — {$banner->name}");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        DB::beginTransaction();
        try {
            // Cập nhật tên banner
            $banner->update([
                'name' => $request->name
            ]);

            // Xử lý xóa các ảnh được đánh dấu
            if ($request->has('deleted_images') && !empty($request->deleted_images)) {
                $deletedImageIds = explode(',', $request->deleted_images);

                foreach ($deletedImageIds as $imageId) {
                    $bannerImage = Banner_image::find($imageId);
                    if ($bannerImage && $bannerImage->banner_id == $banner->id) {
                        // Xóa file vật lý
                        if (Storage::disk('public')->exists($bannerImage->path)) {
                            Storage::disk('public')->delete($bannerImage->path);
                        }
                        // Xóa record trong database
                        $bannerImage->delete();
                    }
                }
            }

            // Xử lý thêm ảnh mới
            if ($request->hasFile('images')) {
                foreach ((array)$request->file('images') as $file) {
                    if ($file->isValid()) {
                        $file_name = $file->store('uploads/images/banners', 'public');
                        Banner_image::create([
                            'path' => $file_name,
                            'banner_id' => $banner->id
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('banner.index')->with('success', 'Cập nhật banner thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage (nếu cần).
     */
    public function destroy(Banner $banner)
    {
        DB::beginTransaction();
        try {
            // Xóa tất cả ảnh của banner
            foreach ($banner->banner_images as $bannerImage) {
                if (Storage::disk('public')->exists($bannerImage->path)) {
                    Storage::disk('public')->delete($bannerImage->path);
                }
                $bannerImage->delete();
            }

            // Xóa banner
            $banner->delete();

            DB::commit();
            return redirect()->route('banner.index')->with('success', 'Xóa banner thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
