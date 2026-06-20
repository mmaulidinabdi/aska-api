<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyOtpEmail;

class SendOtpEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $email,
        public string $otpCode
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(
                new VerifyOtpEmail(
                    $this->otpCode,
                    $this->email
                )
            );
    }
}
