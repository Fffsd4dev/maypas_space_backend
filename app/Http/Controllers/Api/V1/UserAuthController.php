<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLinkMail;
use App\Services\OtpService;
use App\Services\MailService;
use Illuminate\Support\Facades\Crypt;

use App\Models\{User,EstateManager};
use App\Models\LandlordAgent;

class UserAuthController extends Controller
{
    public function create(Request $request){
        // Validate request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
            'dob' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $estate = app('estateManager');

        //Check Email
        $checkEmail = User::where('email', $request->email)->where('estate_manager_id', $estate->id)->select('id')->get();

        if(!$checkEmail->isEmpty()){
            return response()->json(['errors' => 'This Email has already been registered on this platform'], 422);
        }

        $user = User::create([
            'first_name' => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
            'middle_name' => htmlspecialchars($validatedData['middle_name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
            'user_type_id' => 3,
            'password' => Hash::make($request->password),
            'estate_manager_id' => $estate->id,
            'dob' => $validatedData['dob'],
        ]);

        if(!$user){
            return response()->json(['message'=>'Something went wrong'], 500);
        }

        //if registered user is either an agent or landlord, save the id in landlord_tenant table
        if($user && $user->user_type_id != 3){
            $landlordAgent = LandlordAgent::create([
                'user_id' => $user->id
            ]);
            
            if(!$landlordAgent){
                $user->delete();
                return response()->json(['message'=>'Something went wrong, Try again'], 500);
            }
        }

        $otpService = new OtpService();

        $otp = $otpService->generate($user);        
        

        $messageContent = [
            'name' => $user->first_name,
            'email' => $user->email,
            'code' => $otp->code,
        ];

        $mailService = new MailService();

        if (!$mailService->sendOtpMail($user, $messageContent)) {
            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.'
            ], 500);
        }
        
        return response()->json(['message' => 'Registration was successful! An Otp code has been sent to your email.', 'user' => $user], 201);        
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpService = new OtpService();

        if ($otpService->validate($user, $request->otp)) {
            $user->update(['email_verified_at' => now()]);

            $token = $user->createToken('auth_token', ['tenant'])->plainTextToken;

            return response()->json([
                'message' => 'Email verified successfully',
                'token'=>$token
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid or expired OTP'
        ], 422);
    }

    public function resendOtp(Request $request)
    {
    
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $estate = app('estateManager');

        $user = User::where('email', $request->email)->where('estate_manager_id', $estate->id)->first();

        if (!$user || $user->deactivated === 'yes') {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpService = new OtpService();
        $otp = $otpService->resend($user); // deletes previous and creates new one

        $messageContent = [
            'name' => $user->first_name,
            'email' => $user->email,
            'code' => $otp->code,
        ];

        $mailService = new MailService();

        if (!$mailService->sendOtpMail($user, $messageContent)) {
            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.'
            ], 500);
        }

        return response()->json([
            'message' => 'OTP has been resent to your email address.'
        ], 200);
    }

    public function login(Request $request)
    {
        $estate = app('estateManager');

        $user = User::where('email', $request->email)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 404);
        }
        
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($user && !$user->email_verified_at) {
             // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($user->id);

            $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

            // Generate signed URL that expires in 30 minutes
            $resetUrl = config('app.frontend_url').'/'.$estate->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

            $messageContent = [
                'name' => $user->first_name,
                'email' => $user->email,
                'resetUrl' => $resetUrl,
            ];

            // Send OTP via email
            try {
                Mail::to($user->email)->send(new PasswordResetLinkMail($messageContent));
            } catch (\Exception $e) {
                // Log and respond to mail failure
                return response()->json(['message' => 'Failed to send password setting email. Please try again.'], 500);
            }

            return response()->json([
                'message' => 'Your account has not been verified. OTP has been sent to your email address to verify.'
            ], 200);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        //Check if account has been deactivated
        if($user->deactivated === 'yes'){
            return response()->json(['message' => 'The account you are trying to access has been deactivated'], 403);
        }

        $token = $user->createToken('auth_token', ['tenant'])->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'User logged out successfully']);
    }

    public function changePassword(Request $request){
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if (!$user || !Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        if($request->old_password === $request->password){
            return response()->json(['message'=>'Your new password must be different from your current password']);
        }

        $user->password = Hash::make($request->password);

        $response = $user->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Try again later'], 500);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token', ['tenant'])->plainTextToken;


        return response()->json(['message' =>'Password changed successfully', 'token' => $token], 200);

    }

    public function confirmUser(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estate = app('estateManager');

        $user = User::where('email', $request->email)->where('estate_manager_id', $estate->id)->select(['id', 'first_name', 'email'])->first();

        if(!$user){
            return response()->json(['message'=>'User not found'], 404);
        }

        $otpService = new OtpService();

        $otp = $otpService->generate($user, 'password_reset');        
        

        $messageContent = [
            'name' => $user->first_name,
            'email' => $user->email,
            'code' => $otp->code,
        ];

        $mailService = new MailService();

        if (!$mailService->sendOtpMail($user, $messageContent)) {
            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.'
            ], 500);
        }

        return response()->json(['message' => 'A reset password OTP has been sent your registered email'], 200);

    }

    public function resetPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|numeric',
            'password' => 'required|confirmed|min:8',
        ]);

        $estate = app('estateManager');

        $user = User::where('email', $request->email)->where('estate_manager_id', $estate->id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'You cannot use your current password as your new password'], 401);
        }

        $otpService = new OtpService();

        if ($otpService->validate($user, $request->otp, 'password_reset')) {
            $user->update(['password' => Hash::make($request->password), 'email_verified_at' => now()]);

            $user->tokens()->delete();
            $token = $user->createToken('auth_token', ['tenant'])->plainTextToken;

            return response()->json([
                'message' => 'Password reset successfully',
                'token'=>$token
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid or expired OTP'
        ], 422);
    }

    public function passwordReset(Request $request)
    {   
        $estateManager = app('estateManager');

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
            $userId = Crypt::decryptString($request->token);
        
            $user = User::where('id', (int)$userId)->where('estate_manager_id', $estateManager->id)->firstOrFail();
            $user->email_verified_at = now();
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['message' => 'Password reset successfully and email has been verified']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token or user'], 400);
        }
    }
}
