<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingTableRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'table_number' => 'required|integer|between:1,50',
            'booking_date' => 'required|date|after:today',
            'arrival_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30|max:300',
            'guest_count' => 'required|integer|min:1|max:12',
            'note' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'table_number.required' => 'Bàn là bắt buộc',
            'table_number.between' => 'Bàn từ 1 đến 50',
            'booking_date.required' => 'Ngày đặt là bắt buộc',
            'booking_date.after' => 'Ngày đặt phải sau hôm nay',
            'arrival_time.required' => 'Giờ đến là bắt buộc',
            'duration.required' => 'Thời gian dự kiến là bắt buộc',
            'guest_count.required' => 'Số khách là bắt buộc',
        ];
    }
}
