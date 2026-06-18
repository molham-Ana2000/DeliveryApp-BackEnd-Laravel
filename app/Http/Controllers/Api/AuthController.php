<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use Throwable;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $request->user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
   public function sendResetLink(Request $request)
{
    Log::info('FORGOT_PASSWORD_START', [
        'email' => $request->input('email'),
        'all_request' => $request->all(),
    ]);

    try {
        Log::info('FORGOT_PASSWORD_VALIDATE_START');

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        Log::info('FORGOT_PASSWORD_VALIDATE_SUCCESS', [
            'validated' => $validated,
        ]);

        Log::info('FORGOT_PASSWORD_SEND_LINK_START', [
            'email' => $request->input('email'),
            'mail_mailer' => config('mail.default'),
            'app_url' => config('app.url'),
            'db_host' => config('database.connections.mysql.host'),
            'db_port' => config('database.connections.mysql.port'),
            'db_database' => config('database.connections.mysql.database'),
        ]);

        $status = Password::sendResetLink([
            'email' => $request->input('email'),
        ]);

        Log::info('FORGOT_PASSWORD_SEND_LINK_FINISHED', [
            'status' => $status,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('FORGOT_PASSWORD_SUCCESS');

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور.',
                'laravel_status' => $status,
            ]);
        }

        Log::warning('FORGOT_PASSWORD_FAILED_STATUS', [
            'status' => $status,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'فشل إرسال رابط إعادة تعيين كلمة المرور.',
            'laravel_status' => $status,
        ], 400);

    } catch (Throwable $e) {
        Log::error('FORGOT_PASSWORD_EXCEPTION', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء إرسال رابط إعادة تعيين كلمة المرور.',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح.',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'فشل تغيير كلمة المرور.',
            'error' => __($status),
        ], 400);
    }
     
}