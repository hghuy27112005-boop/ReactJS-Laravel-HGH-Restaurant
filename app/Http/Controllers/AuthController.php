<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'role' => Auth::user()->role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thông tin đăng nhập không chính xác.'
        ], 401);
    }

    public function storeRegister(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username|max:20',
            'email' => [
                'nullable',
                'email',
                'max:50',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/'
            ],
            'phone' => 'nullable|max:10',
            'password' => 'required|min:6',
        ], [
            'email.regex' => 'Email phải có định dạng @gmail.com (Ví dụ: user@gmail.com)'
        ]);

        try {
            User::create([
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => $request->password,
                'role' => 'user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function profile()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'username' => 'required|max:20|unique:users,username,' . $user->user_id . ',user_id',
            'email' => [
                'nullable',
                'email',
                'max:50',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/'
            ],
            'phone' => 'nullable|max:10',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Tối đa 5MB
        ], [
            'email.regex' => 'Chỉ chấp nhận địa chỉ Gmail (@gmail.com)'
        ]);

        $updateData = [
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

            // Đường dẫn lưu file theo user_id
            $destinationPath = public_path('avatars/' . $user->user_id);
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            // Lưu link ảo (path) vào DB để sau này load trực tiếp, không cần phải quét thư mục
            $updateData['avatar_url'] = asset('avatars/' . $user->user_id . '/' . $filename);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công!',
            'avatar_url' => $updateData['avatar_url'] ?? $user->avatar_url
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('forgot_password');
    }

    public function verifyUser(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);

        $user = User::where('username', $request->username)
            ->where('email', $request->email)
            ->where('phone', $request->phone)
            ->first();

        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Thông tin xác thực chính xác. Vui lòng đặt lại mật khẩu.',
                'user_id' => $user->user_id
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thông tin không khớp với hệ thống.'
        ], 404);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'password' => 'required|min:6',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->update([
            'password_hash' => $request->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!'
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))
                ->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Lỗi: ' . $e->getMessage());
        }

        // Tìm user theo email
        $user = User::where('email', $googleUser->email)->first();

        if ($user) {
            // Đã có account, cập nhật avatar nếu có và log in luôn
            if ($googleUser->avatar) {
                $user->update(['avatar_url' => $googleUser->avatar]);
            }
            Auth::login($user);
        } else {
            // Chưa có account, tạo mới
            // Username: lấy từ email (phần trước @) và giới hạn 20 ký tự
            $username = explode('@', $googleUser->email)[0];
            $username = substr($username, 0, 20);

            // Kiểm tra xem username có bị trùng không
            $originalUsername = $username;
            $count = 1;
            while (User::where('username', $username)->exists()) {
                $suffix = '_' . $count;
                // Đảm bảo tổng độ dài không quá 20
                $username = substr($originalUsername, 0, 20 - strlen($suffix)) . $suffix;
                $count++;
            }

            $user = User::create([
                'username' => $username,
                'email' => $googleUser->email,
                'password_hash' => Hash::make(str()->random(16)), // Tạo pass ngẫu nhiên vì login qua Google
                'role' => 'user',
                'avatar_url' => $googleUser->avatar, // Lưu avatar từ Google
            ]);

            Auth::login($user);
        }

        // Thông báo thành công và chuyển về trang chủ
        return redirect('/')->with('success', 'Đăng nhập Gmail thành công!');
    }
}