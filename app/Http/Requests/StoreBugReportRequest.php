<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)
            && app(AuthorizationService::class)->can(
                $user,
                config('authorization.capabilities.bug_reports_create')
            );
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:2048'],
            'user_agent' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề lỗi.',
            'title.max' => 'Tiêu đề lỗi không được vượt quá 160 ký tự.',
            'description.required' => 'Vui lòng mô tả lỗi đang gặp.',
            'description.max' => 'Mô tả lỗi không được vượt quá 5000 ký tự.',
            'page_url.url' => 'Đường dẫn trang xảy ra lỗi không hợp lệ.',
            'images.max' => 'Chỉ được gửi tối đa 5 hình ảnh.',
            'images.*.image' => 'Tệp đính kèm phải là hình ảnh.',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, JPEG, PNG hoặc WEBP.',
            'images.*.max' => 'Mỗi hình ảnh không được vượt quá 5MB.',
        ];
    }
}
