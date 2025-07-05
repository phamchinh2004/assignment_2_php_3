<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_partners = Partner::get();
        return view('admin.partner.index', compact('list_partners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.partner.create');
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
                $file_name = $file->hashName();
                $file->move(public_path('uploads/partner/images/'), $file_name);
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

    /**
     * Display the specified resource.
     */
    public function show(Partner $partner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner)
    {

        return view('admin.partner.edit', compact('partner'));
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
                $file_name = $file->hashName();
                $file->move(public_path('uploads/partner/images/'), $file_name);

                // Xoá ảnh cũ nếu tồn tại
                $oldImagePath = public_path('uploads/partner/images/' . $partner->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
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
            $imagePath = public_path('uploads/partner/images/' . $partner->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath); // dùng @ để tránh lỗi nếu không có quyền
            }

            // Xoá bản ghi
            $partner->delete();

            return redirect()->route('partner.index')->with('success', 'Xoá đối tác thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Đã xảy ra lỗi khi xoá: ' . $e->getMessage());
        }
    }
}
