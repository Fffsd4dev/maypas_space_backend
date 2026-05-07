<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\{OtpMail, PasswordResetLinkMail};
use Illuminate\Support\Facades\Crypt;

use App\Models\Admin;

class SystemAdminAuthController extends Controller
{
    public function login(Request $request)
    {
        try{
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            // //Check if email is verified
            // if(!$admin->email_verified_at){
            //     $response = $this->mailOtp($request, $admin->name);

            //     if($response){
            //         return response()->json(['message'=> 'Please verify your account an OTP has been sent to your registered Email'], 200);
            //     }
            // }

            if($admin && !$admin->email_verified_at){
                return response()->json(['message' => 'Failed, Contact super admin']);
            }
            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $token = $admin->createToken('admin-token', ['system-admin'])->plainTextToken;

            if(!$token){
                return response()->json(['message' => 'Something went wrong. Please try again'], 500); 
            }

            return response()->json(['token' => $token, 'admin' => $admin], 200);
        } 
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }   

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Admin logged out successfully']);
    }

    public function newsendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|exists:admins,email',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            if ($admin) {
               $this->mailOtp($request, $admin->name);
            }
                
            return response()->json(['message' => 'Could not find user'], 404);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        
    }
    
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|exists:admins,email',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            if ($admin) {
                // send OTP
                $otp = $this->generateOtp(); 

                // Save OTP to the database
                DB::table('otps')->insert([
                    'email' => $request->email,
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $messageContent = [];
                $messageContent['otp'] = $otp;
                $messageContent['firstName'] = $admin->name;


                // Send OTP via email
                Mail::to($request->email)->send(new OtpMail($messageContent));

                return response()->json(['message' => 'Please verify you own the account by providing OTP sent to your registered email'], 201);
            }
                
            return response()->json(['message' => 'Could not find user'], 404);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        
    }

    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|exists:admins,email',
                'otp' => 'required|numeric',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            if (!$admin) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $otpRecord = DB::table('otps')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('is_used', false)
                ->where('expires_at', '>=', now())
                ->first();

            if (!$otpRecord) {
                return response()->json(['message' => 'Invalid or expired OTP.'], 400);
            }

            // Mark the OTP as used
            DB::table('otps')->where('id', $otpRecord->id)->update(['is_used' => true]);

            // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($admin->id);

            $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

            // Generate signed URL that expires in 30 minutes
            $resetUrl = config('app.frontend_url').'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);


            $messageContent = [
                'name' => $admin->name,
                'email' => $admin->email,
                'resetUrl' => $resetUrl,
            ];

            Mail::to($admin->email)->send(new PasswordResetLinkMail($messageContent));

            return response()->json(['message' => 'OTP verified. A reset link has been sent to your email.'], 200);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function passwordReset(Request $request)
    {   
        $request->validate([
            'token' => 'required',
            'signature' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $expectedSignature = hash_hmac('sha256', $request->token, config('app.key'));

        if (!hash_equals($expectedSignature, $request->signature)) {
            return response()->json(['message' => 'Invalid or expired reset link'], 403);
        }

        try {
            $adminId = Crypt::decryptString($request->token);
            $admin = Admin::findOrFail($adminId);
            $admin->email_verified_at = now();
            $admin->password = Hash::make($request->password);
            $admin->save();

            return response()->json(['message' => 'Password reset successfully and email has been verified']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token or user'], 400);
        }
    }

    private function mailOtp($request, $name){
        // send OTP
                $otp = $this->generateOtp(); 

                // Save OTP to the database
                DB::table('otps')->insert([
                    'email' => $request->email,
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $messageContent = [];
                $messageContent['otp'] = $otp;
                $messageContent['firstName'] = $name;


                // Send OTP via email
                try {
                Mail::to($request->email)->send(new OtpMail($messageContent));
                } catch (\Exception $e) {
                    // Log and respond to mail failure
                    return response()->json(['message' => 'Failed to send OTP email. Please try again.'], 500);
                }

                return true;
    }

    private function generateOtp($length = 6)
    {
        return random_int(100000, 999999);
    }
}
