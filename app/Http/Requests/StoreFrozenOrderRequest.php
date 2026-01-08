<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrozenOrderRequest extends FormRequest
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
    // StoreFrozenOrderRequest.php
    public function rules()
    {
        return [
            'order_data' => 'required|array|min:1',
            'order_data.*.order_id' => 'required|exists:orders,id',
            'order_data.*.custom_price' => 'nullable|numeric|min:0',
            'order_data.*.commission_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages()
    {
        return [
            'order_data.required' => 'Vui lòng chọn ít nhất một đơn hàng',
            'order_data.array' => 'Dữ liệu không hợp lệ',
            'order_data.*.order_id.required' => 'Thiếu thông tin đơn hàng',
            'order_data.*.order_id.exists' => 'Đơn hàng không tồn tại',
            'order_data.*.custom_price.numeric' => 'Giá phải là số',
            'order_data.*.custom_price.min' => 'Giá phải lớn hơn hoặc bằng 0',
            'order_data.*.commission_percentage.numeric' => 'Phần trăm hoa hồng phải là số',
            'order_data.*.commission_percentage.min' => 'Phần trăm hoa hồng phải lớn hơn hoặc bằng 0',
            'order_data.*.commission_percentage.max' => 'Phần trăm hoa hồng không được vượt quá 100',
        ];
    }

    protected function prepareForValidation()
    {
        $orderIds = $this->input('order_ids', []);
        $orderData = $this->input('order_data', []);

        $filteredData = [];
        foreach ($orderIds as $orderId) {
            if (isset($orderData[$orderId])) {
                $filteredData[] = $orderData[$orderId];
            }
        }

        $this->merge([
            'order_data' => $filteredData
        ]);
    }
}
