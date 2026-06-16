<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailVerification;
use App\Mail\VerifyOtpEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register - Mendaftarkan user baru
     */
    public function register(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 8 karakter',
                'password.confirmed' => 'Konfirmasi password tidak sesuai',
            ]);



            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buat user baru
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            try {
                // Kirim OTP otomatis
                $this->sendOtp($user);
            } catch (\Exception $mailException) {
                // Jika email gagal, delete user yang baru dibuat
                $user->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim OTP. Silakan coba lagi.',
                    'error' => 'email_send_failed',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Kode OTP telah dikirim ke email Anda.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'expires_in' => '5 menit',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kirim OTP ke email user
     */
    public function sendOtp(User $user)
    {
        try {
            // Hapus OTP lama yang belum expired
            $user->emailVerifications()->delete();

            // Generate OTP 6 digit
            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Simpan OTP ke database
            $emailVerification = EmailVerification::create([
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'expired_at' => now()->addMinutes(5),
            ]);
dd($user->email);
            // Kirim email dengan OTP
            Mail::to($user->email)->send(new VerifyOtpEmail($otpCode, $user->email));

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda.',
                'data' => [
                    'email' => $user->email,
                    'expires_in' => '5 menit',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend OTP - Mengirim ulang OTP
     */
    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.exists' => 'Email tidak terdaftar',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Cek apakah user sudah terverifikasi
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah terverifikasi',
                ], 400);
            }

            return $this->sendOtp($user);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP - Memverifikasi kode OTP
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'otp_code' => 'required|digits:6',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.exists' => 'Email tidak terdaftar',
                'otp_code.required' => 'Kode OTP wajib diisi',
                'otp_code.digits' => 'Kode OTP harus 6 digit',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Cek apakah user sudah terverifikasi
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah terverifikasi',
                ], 400);
            }

            // Cari OTP di database
            $emailVerification = EmailVerification::where('user_id', $user->id)
                ->where('otp_code', $request->otp_code)
                ->first();

            if (!$emailVerification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP tidak valid',
                ], 400);
            }

            // Cek apakah OTP sudah expired
            if ($emailVerification->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP sudah kadaluarsa',
                ], 400);
            }

            // Update email_verified_at dan hapus OTP
            $user->update([
                'email_verified_at' => now(),
            ]);

            $emailVerification->delete();

            // Generate token API jika ada
            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Email berhasil diverifikasi',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login - Masuk ke akun
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'password.required' => 'Password wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah',
                ], 401);
            }

            // Cek apakah email sudah diverifikasi
            if (!$user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
                    'data' => [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ],
                ], 403);
            }

            // Generate token
            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout - Keluar dari akun
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Profile - Mendapatkan profil user
     */
    public function profile(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Data profil user',
                'data' => $request->user(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check Verification Status - Cek status verifikasi email
     */
    public function checkVerificationStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak terdaftar',
                ], 404);
            }

            // Jika sudah verified
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email sudah terverifikasi',
                    'data' => [
                        'verified' => true,
                        'verified_at' => $user->email_verified_at,
                    ],
                ], 200);
            }

            // Jika belum verified, check OTP status
            $emailVerification = EmailVerification::where('user_id', $user->id)->first();

            if (!$emailVerification) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email belum diverifikasi',
                    'data' => [
                        'verified' => false,
                        'otp_sent' => false,
                        'message' => 'Tidak ada OTP yang dikirim. Silakan register ulang atau resend OTP.',
                    ],
                ], 200);
            }

            // Check apakah OTP expired
            $isExpired = $emailVerification->isExpired();

            return response()->json([
                'success' => true,
                'message' => 'Email belum diverifikasi',
                'data' => [
                    'verified' => false,
                    'otp_sent' => true,
                    'otp_expired' => $isExpired,
                    'expires_at' => $emailVerification->expired_at,
                    'message' => $isExpired ? 'OTP sudah kadaluarsa. Silakan resend OTP.' : 'OTP masih berlaku. Silakan input kode untuk verifikasi.',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
