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
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Vui lòng nhập Tên người dùng hoặc Email.',
            'password.required' => 'Vui lòng nhập mật khẩu.'
        ]);

        // Tự động nhận diện Email hay Username
        $login = $request->input('username');
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$loginType => $login, 'password' => $request->password])) {
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
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:20|unique:users,username',
            'email' => [
                'required',
                'email',
                'max:50',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@((student\.)?ctu\.edu\.vn|gmail\.com)$/',
            ],
            'phone' => 'nullable|max:10',
            'password' => 'required|min:6|confirmed', // 'confirmed' kiểm tra password_confirmation
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập này đã tồn tại.',
            'username.max' => 'Tên đăng nhập không được quá 20 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ Gmail.',
            'email.email' => 'Định dạng Email không hợp lệ.',
            'email.unique' => 'Tài khoản ứng với Gmail này đã tồn tại.',
            'email.regex' => 'Email phải có định dạng @gmail.com.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() // CHỈ LẤY LỖI ĐẦU TIÊN
            ], 422);
        }

        try {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'role' => 'user',
            ]);

            Auth::login($user);
            $user->syncAvatar();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công!',
                'role' => $user->role
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
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

        $validator = Validator::make($request->all(), [
            'username' => 'required|max:20|unique:users,username,' . $user->user_id . ',user_id',
            'email' => [
                'required', // Đổi từ nullable sang required
                'email',
                'max:50',
                'regex:/^[a-zA-Z0-9._%+-]+@((student\.)?ctu\.edu\.vn|gmail\.com)$/'
            ],
            'phone' => 'nullable|max:10',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập này đã tồn tại.',
            'email.required' => 'Vui lòng nhập địa chỉ Gmail.',
            'email.regex' => 'Chỉ chấp nhận địa chỉ Gmail (@gmail.com)'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user->update([
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

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
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:6|confirmed',
        ], [
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu không khớp.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

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
            'email' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@((student\.)?ctu\.edu\.vn|gmail\.com)$/'
            ],
            'phone' => 'required',
        ], [
            'email.regex' => 'Định dạng Email không hợp lệ.'
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