<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20|unique:users,phone',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên là bắt buộc',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email này đã được đăng ký',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải ít nhất 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
        ];
    }
}
