<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
public function sendResetLink(Request $request)
{
    $request->validate([
        'email' => ['required', 'email', 'exists:users,email'],
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    $token = Password::createToken($user);

    $resetUrl = route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]);

    Mail::html(
        '
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 30px;">
            <div style="max-width: 520px; margin: auto; background: #ffffff; padding: 30px; border-radius: 12px; border-top: 5px solid #ff6600;">
                
                <h2 style="color: #1a1a1a; text-align: center;">
                    Passwort zurücksetzen
                </h2>

                <p style="color: #555; font-size: 15px; line-height: 1.6;">
                    Hallo,<br><br>
                    Sie haben eine Zurücksetzung des Passworts für Ihr DeliveryApp-Konto angefordert.
                </p>

                <p style="color: #555; font-size: 15px; line-height: 1.6;">
                    Klicken Sie auf den folgenden Button, um ein neues Passwort festzulegen:
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetUrl . '"
                       style="
                            display: inline-block;
                            background-color: #1a1a1a;
                            color: #ffffff;
                            text-decoration: none;
                            padding: 14px 28px;
                            border-radius: 8px;
                            font-size: 16px;
                            font-weight: bold;
                       ">
                        Passwort zurücksetzen
                    </a>
                </div>

                <p style="color: #666; font-size: 14px; line-height: 1.6;">
                    Falls der Button nicht funktioniert, kopieren Sie bitte den folgenden Link und fügen Sie ihn in Ihren Browser ein:
                </p>

                <p style="word-break: break-all; font-size: 13px;">
                    <a href="' . $resetUrl . '" style="color: #ff6600;">
                        ' . $resetUrl . '
                    </a>
                </p>

                <p style="color: #999; font-size: 13px; margin-top: 30px;">
                    Wenn Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.
                </p>

                <p style="color: #999; font-size: 13px;">
                    Mit freundlichen Grüßen,<br>
                    Ihr DeliveryApp-Team
                </p>
            </div>
        </body>
        </html>
        ',
        function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Passwort zurücksetzen - DeliveryApp');
        }
    );

    return response()->json([
        'success' => true,
        'message' => 'Der Link zum Zurücksetzen des Passworts wurde erfolgreich gesendet.',
        'reset_url' => app()->environment('local') ? $resetUrl : null,
    ]);
}
//    public function sendResetLink(Request $request)
// {
//     $request->validate([
//         'email' => ['required', 'email', 'exists:users,email'],
//     ]);

//     $user = User::where('email', $request->email)->firstOrFail();

//     $token = Password::createToken($user);

//     $resetUrl = route('password.reset', [
//         'token' => $token,
//         'email' => $user->email,
//     ]);

//     Mail::raw(
//         "Hello,\n\nYou requested a password reset for your DeliveryApp account.\n\nClick the link below to reset your password:\n\n{$resetUrl}\n\nIf you did not request a password reset, please ignore this email.",
//         function ($message) use ($user) {
//             $message->to($user->email)
//                 ->subject('Reset Your Password - DeliveryApp');
//         }
//     );

//     return response()->json([
//         'success' => true,
//         'message' => 'Password reset link sent successfully.',
//         'reset_url' => app()->environment('local') ? $resetUrl : null,
//     ]);
// }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required'],
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
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid token or email.',
            'status' => $status,
        ], 422);
    }
}