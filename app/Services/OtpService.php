<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;

class OtpService
{
    public function generate(User $user, string $type = 'email_verification'): Otp
    {
        Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->delete();

        return Otp::create([
            'user_id' => $user->id,
            'code' => random_int(100000, 999999),
            'type' => $type,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function validate(User $user, string $code, string $type = 'email_verification'): bool
    {
        $otp = Otp::where('user_id', $user->id)
            ->where('code', $code)
            ->where('type', $type)
            ->first();

        if (!$otp || now()->gt($otp->expires_at)) {
            return false;
        }

        $otp->delete(); // clean up
        return true;
    }

    public function resend(User $user, string $type = 'email_verification'): Otp
    {
        return $this->generate($user, $type);
    }
}
