<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bill_id' => 'required|exists:bills,id',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
        ];
    }

    public function messages()
    {
        return [
            'bill_id.required' => 'Mã đơn hàng là bắt buộc',
            'bill_id.exists' => 'Đơn hàng không tồn tại',
            'address.required' => 'Địa chỉ là bắt buộc',
            'phone.required' => 'Số điện thoại là bắt buộc',
        ];
    }
}
