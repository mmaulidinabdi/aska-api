<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Mail\VerifyOtpEmail;
use Illuminate\Support\Facades\Mail;


// ============ PUBLIC ROUTES ============
Route::prefix('auth')->group(function () {
    // Register
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // Resend OTP
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resendOtp');

    // Verify OTP
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verifyOtp');

    // Check Verification Status
    Route::get('/verification-status', [AuthController::class, 'checkVerificationStatus'])->name('auth.verificationStatus');
    
    Route::get('/test-mail', function () {

    Mail::to('sayagary@gmail.com')
        ->send(new VerifyOtpEmail(
            '123456',
            'sayagary@gmail.com'
        ));

    return 'OK';
});

    // Login
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// ============ PROTECTED ROUTES ============
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Get Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
});

// ============ DEFAULT USER ROUTE ============
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
