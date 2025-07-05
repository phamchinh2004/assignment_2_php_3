<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Language;
use App\Models\SectionLanguage;
use Illuminate\Support\Facades\DB;
use Str;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_sections = Section::with('sectionLanguages')->get();
        return view('admin.section.index', compact('list_sections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $languages = Language::get();
        return view('admin.section.create', compact('languages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        $name = $request->name;
        $contents = $request->input('content'); // Mảng: [language_id => content]
        $code = Str::slug($name, '_');

        $check_existed = Section::where('code', $code)->first();
        if ($check_existed) {
            return back()->with('error', 'Mã section này đã tồn tại!');
        }

        DB::beginTransaction();
        try {
            $newSection = Section::create([
                'name' => $name,
                'code' => $code,
            ]);

            foreach ($contents as $languageId => $content) {
                SectionLanguage::create([
                    'section_id' => $newSection->id,
                    'language_id' => $languageId,
                    'content' => $content,
                ]);
            }

            DB::commit();
            return redirect()->route('section.index')->with('success', 'Tạo mới section thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function change_status_section(Section $section)
    {
        $message = "";
        if ($section) {
            if (!$section->status) {
                $section->status = 1;
                $message = "Đã kích hoạt section '" . $section->name . "' thành công!";
            } else {
                $section->status = 0;
                $message = "Đã ngừng kích hoạt section '" . $section->name . "' thành công!";
            }
            $section->save();
            return redirect()->route('section.index')->with('success', $message);
        } else {
            return redirect()->route('section.index')->with('error', 'Không tìm thấy section cần thay đổi trạng thái!');
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        $languages = Language::get();
        $section->load('sectionLanguages');
        return view('admin.section.show', compact('section', 'languages'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section)
    {
        $languages = Language::get();
        $section->load('sectionLanguages');
        return view('admin.section.edit', compact('section', 'languages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|array',
        ]);

        $name = $request->input('name');
        $contents = $request->input('content'); // [language_id => content]

        DB::beginTransaction();
        try {
            // Cập nhật section chính
            $section->update([
                'name' => $name
            ]);

            // Cập nhật hoặc tạo mới nội dung theo ngôn ngữ
            foreach ($contents as $languageId => $content) {
                SectionLanguage::updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'language_id' => $languageId
                    ],
                    ['content' => $content]
                );
            }

            DB::commit();
            return redirect()->route('section.index')->with('success', 'Cập nhật section thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section)
    {
        //
    }
}
