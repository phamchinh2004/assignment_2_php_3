<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Controllers\Controller;
use App\Models\Banner_image;
use Illuminate\Support\Facades\DB;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_banners = Banner::with('banner_images')->get();
        return view('admin.banner.index', compact('list_banners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.banner.create');
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
            // dd($data['images']);
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
    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        //
    }
}
