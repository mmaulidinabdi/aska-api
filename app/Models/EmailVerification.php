<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $table = 'email_verifications';

    protected $fillable = [
        'otp_code',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * Relationship ke User
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Cek apakah OTP masih berlaku
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expired_at);
    }

    /**
     * Cek apakah OTP masih valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}
