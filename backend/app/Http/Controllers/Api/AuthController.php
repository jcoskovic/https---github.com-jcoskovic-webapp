<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department' => $request->department,
            'role' => 'user',
        ]);

        // Generate email verification token and send verification email
        $token = $user->generateEmailVerificationToken();
        $verificationUrl = env('FRONTEND_URL', 'http://localhost:4200').'/verify-email?token='.$token.'&email='.urlencode($user->email);

        // Send verification email
        try {
            Mail::to($user->email)->send(new EmailVerificationMail($verificationUrl, $user->name));
        } catch (\Exception $e) {
            Log::error('Failed to send email verification: '.$e->getMessage());
            // Continue with registration even if email fails
        }

        try {
            $authToken = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nije moguće kreirati token',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Korisnik je uspješno kreiran. Provjerite email za potvrdu.',
            'data' => [
                'user' => $user,
                'token' => $authToken,
                'email_verification_sent' => true,
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Neispravni podaci za prijavu',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nije moguće kreirati token',
            ], 500);
        }

        $user = JWTAuth::user();

        return response()->json([
            'status' => 'success',
            'message' => 'Prijava uspješna',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(): JsonResponse
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token nije važeći',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::setToken($token)->invalidate();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token nije pronađen',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not logout',
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'token' => $token,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not refresh token',
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Trenutna lozinka je obavezna',
            'new_password.required' => 'Nova lozinka je obavezna',
            'new_password.min' => 'Nova lozinka mora imati minimalno 8 znakova',
            'new_password.confirmed' => 'Potvrda nove lozinke se ne podudara',
        ]);

        /** @var User|null $user */
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user instanceof User) {
            return response()->json([
                'status' => 'error',
                'message' => 'Korisnik nije autentificiran',
            ], 401);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trenutna lozinka nije ispravna',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Lozinka je uspješno promijenjena',
        ]);
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Korisnik sa ovim email-om ne postoji',
            ], 404);
        }

        // Generate reset token
        $token = str()->random(60);

        // Store token in database
        $user->update([
            'password_reset_token' => $token,
            'password_reset_expires' => now()->addHours(1),
        ]);

        // Build reset URL
        $resetUrl = env('FRONTEND_URL', 'http://localhost:4200').'/reset-password?token='.$token.'&email='.urlencode($user->email);

        // Send email
        try {
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name));

            return response()->json([
                'status' => 'success',
                'message' => 'Link za resetovanje lozinke je poslat na vaš email',
            ]);
        } catch (\Exception $e) {
            // Log error but don't expose details to user
            Log::error('Failed to send password reset email: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri slanju email-a. Pokušajte ponovo.',
            ], 500);
        }
    }

    /**
     * Reset user password with token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ]);

        $user = User::where('email', $request->email)
            ->where('password_reset_token', $request->token)
            ->where('password_reset_expires', '>', now())
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Neispravni podaci za resetovanje lozinke ili je token istekao',
            ], 400);
        }

        // Reset password
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_expires' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lozinka je uspješno resetovana',
        ]);
    }

    /**
     * Verify user's email address
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {

        $user = User::where('email', $request->email)
            ->where('email_verification_token', $request->token)
            ->where('email_verification_token_expires', '>', now())
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Neispravni podaci za potvrdu email adrese ili je token istekao',
            ], 400);
        }

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email adresa je već potvrđena',
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        return response()->json([
            'status' => 'success',
            'message' => 'Email adresa je uspješno potvrđena',
        ]);
    }

    /**
     * Resend email verification link
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Korisnik sa ovim email-om ne postoji',
            ], 404);
        }

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email adresa je već potvrđena',
            ], 400);
        }

        // Generate new verification token
        $token = $user->generateEmailVerificationToken();
        $verificationUrl = env('FRONTEND_URL', 'http://localhost:4200').'/verify-email?token='.$token.'&email='.urlencode($user->email);

        // Send verification email
        try {
            Mail::to($user->email)->send(new EmailVerificationMail($verificationUrl, $user->name));

            return response()->json([
                'status' => 'success',
                'message' => 'Link za potvrdu email adrese je ponovo poslat',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email verification: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri slanju email-a. Pokušajte ponovo.',
            ], 500);
        }
    }
}
