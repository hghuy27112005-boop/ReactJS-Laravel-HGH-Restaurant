<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_type' => 'required|in:booking_table,delivery',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            
            // Delivery fields
            'address' => 'required_if:order_type,delivery|string',
            
            // Booking table fields
            'table_number' => 'required_if:order_type,booking_table|integer|between:1,50',
            'booking_date' => 'required_if:order_type,booking_table|date',
            'arrival_time' => 'required_if:order_type,booking_table|date_format:H:i',
            'duration' => 'required_if:order_type,booking_table|integer|min:30|max:300',
        ];
    }

    public function messages()
    {
        return [
            'order_type.required' => 'Loại đơn hàng là bắt buộc',
            'items.required' => 'Phải có ít nhất một sản phẩm',
            'items.*.dish_id.required' => 'Mã sản phẩm là bắt buộc',
            'items.*.dish_id.exists' => 'Sản phẩm không tồn tại',
            'items.*.quantity.min' => 'Số lượng tối thiểu là 1',
        ];
    }
}
