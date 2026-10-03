<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && in_array($user->role, User::MANAGEMENT_ROLES, true)
            && app(AuthorizationService::class)->can(
                $user,
                config('authorization.capabilities.account_update_profile')
            );
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.string' => 'Họ và tên phải là chuỗi ký tự.',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email này đã được sử dụng bởi tài khoản khác.',
        ];
    }
}
