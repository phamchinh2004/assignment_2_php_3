<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_languages = Language::get();
        return view('admin.language.index', compact('list_languages'));
    }
    public function change(Request $request)
    {
        $request->validate(['locale' => 'required|exists:languages,code']);
        session(['locale' => $request->locale]);
        return back();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.language.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'code']);
            if ($request->hasFile('image')) {
                $file = $request->image;
                $file_name = $file->hashName();
                $file->move(public_path('uploads/language/images/'), $file_name);
                $data['image'] = $file_name;
            }
            Language::create($data);
            DB::commit();
            return redirect()->route('language.index')->with('success', 'Thêm mới ngôn ngữ thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language)
    {
        return view('admin.language.edit', compact('language'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        try {
            $data = $request->only(['name', 'code']);

            // Nếu có ảnh mới
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $file = $request->file('image');
                $file_name = $file->hashName();
                $file->move(public_path('uploads/language/images/'), $file_name);

                // Xoá ảnh cũ nếu tồn tại
                $oldImagePath = public_path('uploads/language/images/' . $language->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }

                $data['image'] = $file_name;
            }

            $language->update($data);

            return redirect()->route('language.index')->with('success', 'Cập nhật ngôn ngữ thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
