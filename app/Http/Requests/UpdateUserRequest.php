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
        $actor = auth()->user();
        if (!$actor) {
            return false;
        }

        $authorization = app(AuthorizationService::class);
        $capabilities = config('authorization.capabilities');

        if (
            $this->hasAny(['balance', 'frozen_balance'])
            && !$authorization->can($actor, $capabilities['customers_adjust_balance'])
        ) {
            return false;
        }

        if (
            $this->has('status')
            && !$authorization->can($actor, $capabilities['customers_change_status'])
        ) {
            return false;
        }

        if (
            $this->hasAny(['rank', 'lucky_wheel_bonus_spins', 'reset_progress'])
            && !$authorization->can($actor, $capabilities['customers_manage_spin'])
        ) {
            return false;
        }

        if ($this->has('role') && !$authorization->isSuperuser($actor)) {
            return false;
        }

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
            'balance' => ['sometimes', 'numeric', 'min:0'],
            'frozen_balance' => ['sometimes', 'numeric', 'min:0'],
            'rank' => ['nullable', 'integer', Rule::exists('ranks', 'id')],
            'status' => ['sometimes', Rule::in(['inactivated', 'activated', 'banned'])],
            'warehouse_area' => ['nullable', 'string', 'max:191'],
            'warehouse_address' => ['nullable', 'string', 'max:1000'],
            'lucky_wheel_bonus_spins' => [
                Rule::prohibitedIf(fn () => $actor === null || !$authorization->can($actor, config('authorization.capabilities.customers_manage_spin'))),
                'nullable',
                'integer',
                'min:0',
                'max:100000',
            ],
            'reset_progress' => ['sometimes', 'boolean'],
            'clone_account' => ['sometimes', 'boolean'],
            'role' => [
                Rule::prohibitedIf(fn () => $actor === null || !$authorization->isSuperuser($actor)),
                'nullable',
                Rule::in([User::ROLE_MEMBER, User::ROLE_STAFF, User::ROLE_ADMIN]),
            ],
        ];
    }
}
