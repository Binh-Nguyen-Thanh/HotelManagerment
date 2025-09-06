<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class ForgetPassController extends Controller
{
    public function showEmailForm()
    {
        Session::forget(['reset_email', 'reset_code', 'reset_code_created_at', 'countdown_start', 'code_verified']);
        return view('auth_user.forgetpass', ['step' => 'email']);
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);

        try {
            $code = rand(100000, 999999);
            Session::put('reset_email', $request->email);
            Session::put('reset_code', $code);
            Session::put('reset_code_created_at', now()->timestamp);

            //LUÔN đặt lại countdown_start khi nhấn gửi lại mã
            Session::put('countdown_start', now()->timestamp);

            Session::forget('code_verified');

            Mail::raw("Mã xác thực của bạn là: $code", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Mã xác thực đặt lại mật khẩu');
            });

            return redirect()->route('auth_user.forget.password.verifyForm')->with('success', 'Mã xác thực đã được gửi qua email!');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Không thể gửi email: ' . $e->getMessage()]);
        }
    }

    public function showVerifyForm()
    {
        if (!Session::has('reset_email')) {
            return redirect()->route('auth_user.forget.password.emailForm');
        }

        $countdownStart = Session::get('countdown_start');
        $elapsed = now()->timestamp - $countdownStart;
        $remainingTime = max(0, 30 - $elapsed);

        return view('auth_user.forgetpass', [
            'step' => 'verify',
            'remainingTime' => $remainingTime
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric',
        ]);

        $createdAt = Session::get('reset_code_created_at');
        $expired = (now()->timestamp - $createdAt) > 30;

        if ($expired) {
            return redirect()->back()->withErrors(['code' => 'Mã đã hết hạn, vui lòng nhấn "Gửi lại mã"!']);
        }

        if ($request->code == Session::get('reset_code')) {
            Session::put('code_verified', true);
            return redirect()->route('auth_user.forget.password.resetForm');
        }

        return redirect()->back()->withErrors(['code' => 'Mã không chính xác!'])->withInput();
    }

    public function showResetForm()
    {
        if (!Session::get('code_verified')) {
            return redirect()->route('auth_user.forget.password.emailForm');
        }

        return view('auth_user.forgetpass', ['step' => 'reset']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ], [
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $user = User::where('email', Session::get('reset_email'))->first();

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        Session::forget(['reset_email', 'reset_code', 'code_verified', 'reset_code_created_at', 'countdown_start']);

        return redirect()->route('auth_user.login')->with('success', 'Đặt lại mật khẩu thành công!');
    }

    public function updateCountdown(Request $request)
    {
        // Optional AJAX support if needed to sync remaining time on frontend
        $request->validate([
            'countdown_time' => 'required|integer|min:0|max:30',
        ]);

        Session::put('remaining_time', $request->input('countdown_time'));
        return response()->json(['success' => true]);
    }
}
