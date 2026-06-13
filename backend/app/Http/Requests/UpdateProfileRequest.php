<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . auth('sanctum')->id(),
            'phone' => 'nullable|string|max:20|unique:users,phone,' . auth('sanctum')->id(),
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được đăng ký',
            'phone.unique' => 'Số điện thoại này đã được đăng ký',
        ];
    }
}
