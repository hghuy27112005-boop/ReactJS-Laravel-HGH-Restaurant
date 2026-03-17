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

            // Đồng bộ avatar khi đăng nhập
            Auth::user()->syncAvatar();

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
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'role' => 'user',
            ]);

            // Đăng nhập ngay sau khi đăng kí
            Auth::login($user);

            // Đồng bộ avatar (mới tạo thì sẽ dùng mặc định)
            $user->syncAvatar();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký và đăng nhập thành công!',
                'role' => $user->role
            ]);
        }
        catch (\Exception $e) {
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
        ], [
            'email.regex' => 'Chỉ chấp nhận địa chỉ Gmail (@gmail.com)'
        ]);

        $updateData = [
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công!'
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Tối đa 5MB
        ]);

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
            $avatar_url = asset('avatars/' . $user->user_id . '/' . $filename);
            $user->update(['avatar_url' => $avatar_url]);

            return response()->json([
                'success' => true,
                'message' => 'Lưu ảnh đại diện thành công!',
                'avatar_url' => $avatar_url
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi tải ảnh lên'
        ], 400);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ], [
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
            'new_password_confirmation.same' => 'Mật khẩu xác nhận không khớp'
        ]);

        $user = Auth::user();

        $user->update([
            'password_hash' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!'
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
            'password_hash' => Hash::make($request->password),
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
        }
        catch (\Exception $e) {
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
        }
        else {
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

        // Đồng bộ avatar (kiểm tra folder địa phương trước, nếu không có mới dùng link Google)
        Auth::user()->syncAvatar();

        // Thông báo thành công và chuyển về trang chủ
        return redirect('/')->with('success', 'Đăng nhập Gmail thành công!');
    }
}