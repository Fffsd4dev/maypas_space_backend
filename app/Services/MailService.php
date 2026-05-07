<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Models\User;

class MailService
{
    public function sendOtpMail(User $user, array $messageContent): bool
    {
        try {
            Mail::to($user->email)->send(new OtpMail($messageContent));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email to ' . $user->email . ': ' . $e->getMessage());
            return false;
        }
    }
}
