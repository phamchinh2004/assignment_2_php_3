<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRankRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'commission_percentage' => 'required|numeric|min:0',
            'upgrade_fee' => 'required|numeric|min:0',
            'spin_count' => 'required|integer|min:1',
            'value' => 'required|numeric|min:0',
            'maximum_number_of_withdrawals' => 'required|integer|min:1',
            'maximum_withdrawal_amount' => 'required|numeric|min:1',
        ];
    }
}
