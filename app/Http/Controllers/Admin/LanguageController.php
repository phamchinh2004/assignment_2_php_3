<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;

class LanguageController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage, private readonly AuthorizationService $authorization)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_languages = Language::get();
        $user = auth()->user();
        return $this->reactPage->admin('admin.languages.index', [
            'items' => $list_languages,
            'storageBaseUrl' => asset('storage'),
            'routes' => [
                'create' => route('language.create'),
                'show' => route('language.show', ['language' => '__LANGUAGE_ID__']),
                'edit' => route('language.edit', ['language' => '__LANGUAGE_ID__']),
            ],
            'permissions' => [
                'create' => $this->authorization->can($user, config('authorization.capabilities.languages_create')),
                'viewDetail' => $this->authorization->can($user, config('authorization.capabilities.languages_view_detail')),
                'update' => $this->authorization->can($user, config('authorization.capabilities.languages_update')),
            ],
        ], 'Danh sách ngôn ngữ');
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
        return $this->reactPage->admin('admin.languages.create', ['routes' => ['index' => route('language.index'), 'store' => route('language.store')]], 'Thêm mới ngôn ngữ');
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
                $file_name = $file->store("uploads/images/languages", "public");
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

    public function show(Language $language)
    {
        return $this->reactPage->admin('admin.languages.show', [
            'item' => $language,
            'storageBaseUrl' => asset('storage'),
            'routes' => ['index' => route('language.index'), 'edit' => route('language.edit', $language)],
            'permissions' => ['update' => $this->authorization->can(auth()->user(), config('authorization.capabilities.languages_update'))],
        ], "Chi tiết ngôn ngữ — {$language->name}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language)
    {
        return $this->reactPage->admin('admin.languages.edit', [
            'item' => $language,
            'storageBaseUrl' => asset('storage'),
            'routes' => ['index' => route('language.index'), 'update' => route('language.update', $language)],
        ], "Chỉnh sửa ngôn ngữ — {$language->name}");
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
                $file_name = $file->store("uploads/images/languages", "public");

                // Xoá ảnh cũ nếu tồn tại
                if (Storage::exists($language->image)) {
                    Storage::delete($language->image);
                }
                $data['image'] = $file_name;
            }

            $language->update($data);

            return redirect()->route('language.index')->with('success', 'Cập nhật ngôn ngữ thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
