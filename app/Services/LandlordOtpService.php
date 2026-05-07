<?php

namespace App\Services;

use App\Models\LandlordOtp;
use App\Models\LandlordAgent as Landlord;

class LandlordOtpService
{
    public function generate(Landlord $landlord, string $type = 'email_verification'): LandlordOtp
    {
        LandlordOtp::where('landlord_agent_id', $landlord->id)
            ->where('type', $type)
            ->delete();

        return LandlordOtp::create([
            'landlord_agent_id' => $landlord->id,
            'code' => random_int(100000, 999999),
            'type' => $type,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function validate(Landlord $landlord, string $code, string $type = 'email_verification'): bool
    {
        $otp = LandlordOtp::where('landlord_agent_id', $landlord->id)
            ->where('code', $code)
            ->where('type', $type)
            ->first();

        if (!$otp || now()->gt($otp->expires_at)) {
            return false;
        }

        $otp->delete(); // clean up
        return true;
    }

    public function resend(Landlord $landlord, string $type = 'email_verification'): LandlordOtp
    {
        return $this->generate($landlord, $type);
    }
}
