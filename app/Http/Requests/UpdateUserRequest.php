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
            'username' => 'required|string|max:255|min:6',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId
            ],
            'phone' => [
                'required',
            ],
            'balance'=>'numeric|min:0',
            'frozen_balance'=>'numeric|min:0',
            'rank' => 'required',
            'status' => ['required', Rule::in(['inactivated', 'activated', 'banned'])],
            'warehouse_area' => 'nullable|string|max:191',
            'warehouse_address' => 'nullable|string',
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
