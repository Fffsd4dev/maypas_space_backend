<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Models\LandlordAgent as Landlord;

class LandlordMailService
{
    public function sendOtpMail(Landlord $landlord, array $messageContent): bool
    {
        try {
            Mail::to($landlord->email)->send(new OtpMail($messageContent));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email to ' . $landlord->email . ': ' . $e->getMessage());
            return false;
        }
    }
}
