<?php

namespace App\Http\Controllers;

use App\Jobs\SendOtpEmailJob;
use App\Mail\ResetPassword;
use App\Mail\VerifyOtpEmail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
                'namaLengkap' => 'required|string',
                'password' => 'required|min:8',
                'passwordConfirmation' => 'required|same:password'
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'namaLengkap.required' => 'Nama Lengkap Wajib diisi',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 8 karakter',
                'passwordConfirmation.required' => 'Konfirmasi password wajib diisi',
                'passwordConfirmation.same' => 'Konfirmasi password tidak sesuai',

            ]);



            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validate();

            // Buat user baru
            $user = User::create([
                'email' => $validated['email'],
                'nama_lengkap' => $validated['namaLengkap'],
                'password' => Hash::make($validated['password']),
            ]);

            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->emailVerifications()->create([
                'otp_code' => $otpCode,
                'expired_at' => now()->addMinutes(5),
            ]);

            try {

                Mail::to($user->email)->send(new VerifyOtpEmail($otpCode, $user->email));
            } catch (\Exception $mailException) {

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim OTP. Silakan minta OTP lagi',
                    'error' => 'email_send_failed',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Kode OTP telah dikirim ke email Anda.',
                'data' => [
                    'userId' => $user->id,
                    'email' => $user->email,
                    'namaLengkap' => $user->nama_lengkap,
                    'expiresIn' => '5 menit',
                ],
            ], 201);
        } catch (\Exception $e) {
            // dd($e->getMessage());
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
            $user->emailVerifications()->create([
                'otp_code' => $otpCode,
                'expired_at' => now()->addMinutes(5),
            ]);

            // Kirim email dengan OTP
            Mail::to($user->email)->send(new VerifyOtpEmail($otpCode, $user->email));
            // SendOtpEmailJob::dispatch($user->email,$otpCode);  //jalankan pakai php artisan queue:work
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
                    'message' => 'Email tidak ditemukan'
                ]);
            }

            // Cek apakah user sudah terverifikasi
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah terverifikasi',
                ], 400);
            }

            // $this->sendOtp($user);
            $user->emailVerifications()->delete();
            // Generate OTP 6 digit
            $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Simpan OTP ke database
            $user->emailVerifications()->create([
                'otp_code' => $otpCode,
                'expired_at' => now()->addMinutes(5),
            ]);

            // Kirim email dengan OTP
            Mail::to($user->email)->send(new VerifyOtpEmail($otpCode, $user->email));
            return response()->json([
                'success' => true,
                'message' => "Kode OTP berhasil dikirim ulang ke email anda"
            ]);
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
                'otpCode' => 'required|digits:6',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.exists' => 'Email tidak terdaftar',
                'otpCode.required' => 'Kode OTP wajib diisi',
                'otpCode.digits' => 'Kode OTP harus 6 digit',
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
                ->where('otp_code', $request->otpCode)
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


            return response()->json([
                'success' => true,
                'message' => 'Email berhasil diverifikasi',

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
                ], 403);
            }

            // hapus token sebelumnya jika ada
            $user->tokens()->delete();

            // Generate token
            $token = $user->createToken('api_token', ['*'], now()->addDays(1))->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'userId' => $user->id,
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

    public function forgotPassword(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
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

            $validated = $validator->validate();

            $user = User::where('email', $validated['email'])->first();
            if (!$user) {
                return response()->json([
                    'message' => 'Link reset dikirim ke akun yang terdaftar'
                ], 404);
            }

            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(

                ['email' => $validated['email']],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]

            );

            $resetLink = env('NEXT_URL') . '/auth/newPassword?token=' . $token . '&email=' . $validated['email'];

            Mail::to($validated['email'])->send(new ResetPassword($user->email, $resetLink));

            return response()->json([
                'message' => "Link berhasil dikirim ke " . $validated['email'] . ", silahkan cek email anda!"
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8',
                'passwordConfirmation' => 'required|same:password'
            ], [
                'token.required' => 'token wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 8 karakter',
                'passwordConfirmation.required' => 'Konfirmasi password wajib diisi',
                'passwordConfirmation.same' => 'Konfirmasi password tidak sesuai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validate();


            $reset = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

            if (!$reset) {
                return response()->json(['message' => 'Token tidak ditemukan'], 404);
            }

            // cek masa berlaku token
            $createdAt = Carbon::parse($reset->created_at);
            //kalau lebih dari 2 menit hapus token
            if (Carbon::now()->greaterThan($createdAt->addMinutes(15))) {
                //hapus token yg expired
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
                return response()->json(['message' => 'Token sudah kadaluarsa'], 400);
            }

            // Cek token valid atau tidak
            if (!Hash::check($validated['token'], $reset->token)) {
                return response()->json(['message' => 'Token tidak valid.'], 400);
            }

            //update password
            $user = User::where('email', $validated['email'])->first();
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            // Delete token setelah dipakai
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            $user->tokens()->delete();

            return response()->json(['message' => 'Password berhasil direset.']);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
