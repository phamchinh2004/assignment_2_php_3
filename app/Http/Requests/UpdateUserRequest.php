<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;
        $actor = auth()->user();
        $authorization = app(AuthorizationService::class);
        
        return [
            'full_name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                'min:6',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId
            ],
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'username_bank' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'balance'=>'numeric|min:0',
            'frozen_balance'=>'numeric|min:0',
            'rank' => ['required', 'integer', Rule::exists('ranks', 'id')],
            'status' => ['required', Rule::in(['inactivated', 'activated', 'banned'])],
            'warehouse_area' => ['nullable', 'string', 'max:191'],
            'warehouse_address' => ['nullable', 'string', 'max:1000'],
            'lucky_wheel_bonus_spins' => [
                Rule::prohibitedIf(fn () => $actor === null || !$authorization->can($actor, config('authorization.capabilities.manage_all_users'))),
                'nullable',
                'integer',
                'min:0',
                'max:100000',
            ],
            'role' => [
                Rule::prohibitedIf(fn () => $actor === null || !$authorization->isSuperuser($actor)),
                'nullable',
                Rule::in([User::ROLE_MEMBER, User::ROLE_STAFF, User::ROLE_ADMIN]),
            ],
        ];
    }
}
