<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'rank' => 'required',
        ];
    }
}
