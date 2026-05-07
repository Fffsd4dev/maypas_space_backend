<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\{LandlordVerificationMail, PasswordResetLinkMail};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\LandlordOtpService;
use App\Services\LandlordMailService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Crypt;

use App\Models\{LandlordAgent, Apartment, Location};

class LandlordAgentController extends Controller
{
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:landlord_agents,email',
            'otp'   => 'required|numeric',
        ]);

        $landlord = LandlordAgent::where('email', $request->email)->first();

        if (!$landlord) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpService = new LandlordOtpService();

        if ($otpService->validate($landlord, $request->otp)) {
            $landlord->update(['email_verified_at' => now()]);

            $token = $landlord->createToken('auth_token', ['landlord'])->plainTextToken;

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
            'email' => 'required|email|exists:landlord_agents,email',
        ]);

        $estate = app('estateManager');

        $landlord = LandlordAgent::where('email', $request->email)->where('estate_manager_id', $estate->id)->first();

        if (!$landlord || $landlord->deactivated === 'yes') {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpService = new LandlordOtpService();
        $otp = $otpService->resend($landlord); // deletes previous and creates new one

        $messageContent = [
            'name' => $landlord->first_name,
            'email' => $landlord->email,
            'code' => $otp->code,
        ];

        $mailService = new LandlordMailService();

        if (!$mailService->sendOtpMail($landlord, $messageContent)) {
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

        $landlord = LandlordAgent::where('email', $request->email)
                    ->where('estate_manager_id', $estate->id)->firstOrFail();

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($landlord && !$landlord->email_verified_at) {
             // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($landlord->id);

            $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

            // Generate signed URL that expires in 30 minutes
            $resetUrl = config('app.frontend_url').'/'.$estate->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

            $messageContent = [
                'name' => $landlord->first_name,
                'email' => $landlord->email,
                'resetUrl' => $resetUrl,
            ];

            // Send OTP via email
            try {
                Mail::to($landlord->email)->send(new PasswordResetLinkMail($messageContent));
            } catch (\Exception $e) {
                // Log and respond to mail failure
                return response()->json(['message' => 'Failed to send password setting email. Please try again.'], 500);
            }

            return response()->json([
                'message' => 'Your account has not been verified. OTP has been sent to your email address to verify.'
            ], 200);
        }

        if (!$landlord || !Hash::check($request->password, $landlord->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        //Check if account has been deactivated
        if($landlord->deactivated === 'yes'){
            return response()->json(['message' => 'The account you are trying to access has been deactivated'], 403);
        }

        $token = $landlord->createToken('auth_token', ['landlord'])->plainTextToken;

        return response()->json(['token' => $token, 'landlord' => $landlord], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'User logged out successfully']);
    }

    public function changePassword(Request $request){
        $landlord = $request->user();

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if (!$landlord || !Hash::check($request->old_password, $landlord->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        if($request->old_password === $request->password){
            return response()->json(['message'=>'Your new password must be different from your current password']);
        }

        $landlord->password = Hash::make($request->password);

        $response = $landlord->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Try again later'], 500);
        }

        $landlord->tokens()->delete();
        $token = $landlord->createToken('auth_token', ['landlord'])->plainTextToken;


        return response()->json(['message' =>'Password changed successfully', 'token' => $token], 200);

    }

    public function confirmUser(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:landlord_agents,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estate = app('estateManager');

        $landlord = LandlordAgent::where('email', $request->email)->where('estate_manager_id', $estate->id)->select(['id', 'first_name', 'email'])->first();

        if(!$landlord){
            return response()->json(['message'=>'User not found'], 404);
        }

        $otpService = new LandlordOtpService();

        $otp = $otpService->generate($landlord, 'password_reset');        
        

        $messageContent = [
            'name' => $landlord->first_name,
            'email' => $landlord->email,
            'code' => $otp->code,
        ];

        $mailService = new LandlordMailService();

        if (!$mailService->sendOtpMail($landlord, $messageContent)) {
            return response()->json([
                'message' => 'OTP could not be sent. Please try again later.'
            ], 500);
        }

        return response()->json(['message' => 'A reset password OTP has been sent your registered email'], 200);

    }

    public function resetPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:landlord_agents,email',
            'otp'   => 'required|numeric',
            'password' => 'required|confirmed|min:8',
        ]);

        $estate = app('estateManager');

        $landlord = LandlordAgent::where('email', $request->email)->where('estate_manager_id', $estate->id)->first();

        if (!$landlord) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (Hash::check($request->password, $landlord->password)) {
            return response()->json(['message' => 'You cannot use your current password as your new password'], 401);
        }

        $otpService = new LandlordOtpService();

        if ($otpService->validate($landlord, $request->otp, 'password_reset')) {
            $landlord->update(['password' => Hash::make($request->password), 'email_verified_at' => now()]);

            $landlord->tokens()->delete();
            $token = $landlord->createToken('auth_token', ['landlord'])->plainTextToken;

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
            $landlord = LandlordAgent::where('id', $userId)->where('estate_manager_id', $estateManager->id)->firstOrFail();
            $landlord->email_verified_at = now();
            $landlord->password = Hash::make($request->password);
            $landlord->save();

            return response()->json(['message' => 'Password reset successfully and email has been verified']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token or user'], 400);
        }
    }

    public function create(Request $request)
    {
        try {
            $landlord = $request->user();

            $estate = app('estateManager');

            if (!(int)$landlord->user_type_id === 1 || (int)$landlord->estate_manager_id !== (int)$estate->id) {
                return response()->json(['message' => "You are not authorized"], 403);
            }


            // Validate request data
            $validator = Validator::make($request->all(), [
                'first_name'  => 'required|string|max:255',
                'last_name'   => 'required|string|max:255',
                'email'       => 'required|email',
                'user_type'   => 'required|numeric|exists:user_types,id|gte:2',
                'phone'       => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validatedData = $validator->validated();

            // Create landlord
            $landlord = LandlordAgent::create([
                'first_name'        => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
                'last_name'         => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
                'email'             => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
                'phone'             => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
                'user_type_id'      => $validatedData['user_type'],
                'password'          => Hash::make('TestingPassword'),
                'estate_manager_id' => $estate->id,
            ]);

            if (!$landlord) {
                return response()->json(["message" => "Something went wrong"], 500);
            }

            // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($landlord->id);
            $signature   = hash_hmac('sha256', $encryptedId, config('app.key'));

            $resetUrl = config('app.frontend_url') . '/' . $estate->slug . '/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

            $messageContent = [
                'name'     => $landlord->first_name,
                'email'    => $landlord->email,
                'resetUrl' => $resetUrl,
            ];

            // Try sending email
            Mail::to($landlord->email)->send(new PasswordResetLinkMail($messageContent));

            return response()->json([
                'message'  => 'Agent added successfully! An email has been sent to new Agent to complete registration.',
                'estate'   => $estate->estate_name,
                'landlord' => $landlord
            ], 201);

        } catch (\Throwable $e) {
            // Log the error for debugging
            \Log::error('Error creating agent/admin: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred. Please try again later.',
                'error'   => $e->getMessage() // remove this in production for security
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $estate = app('estateManager');
        $user = $request->user();

        if ((int)$user->user_type_id !== 1 || (int)$user->estate_manager_id !== (int)$estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $agents = LandlordAgent::where('estate_manager_id', (int)$estate->id)
                    ->select('id','uuid','first_name','last_name','email','phone','user_type_id','created_at')
                    ->latest()
                    ->paginate(15);

        return response()->json($agents);
    }

    public function show(Request $request, $tenant_slug, $uuid)
    {
        $estate = app('estateManager');
        $user = $request->user();

        if ((int)$user->user_type_id !== 1 || (int)$user->estate_manager_id !== (int)$estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $agent = LandlordAgent::where('uuid', $uuid)
                    ->where('estate_manager_id', $estate->id)
                    ->select('id','uuid','first_name','last_name','email','phone','user_type_id','created_at')
                    ->first();

        if (!$agent) {
            return response()->json(['message' => 'Agent not found'], 404);
        }

        return response()->json($agent);
    }

    public function destroy(Request $request, $tenent_slug, $uuid)
    {
        $estate = app('estateManager');
        $user = $request->user();

        if ((int)$user->user_type_id !== 1 || (int)$user->estate_manager_id !== (int)$estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $agent = LandlordAgent::where('uuid', $uuid)
                    ->where('estate_manager_id', $estate->id)
                    ->firstOrFail();

        if (!$agent) {
            return response()->json(['message' => 'Agent not found'], 404);
        }

        if($agent->user_type_id == 1){
            return response()->json(['message' => 'You cannot delete an owner'], 403);
        }

        $agent->delete();

        return response()->json(['message' => 'Agent deleted successfully']);
    }

    public function completeLandlordProfile(Request $request){

        $landlord = LandlordAgent::findOrFail($request->user()->id);

        // Validate request
        $validator = Validator::make($request->all(), [
            'id_card'           => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_photo'      => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'cac'               => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
            'business_name'     => 'required|string|max:255',
            'business_state'    => 'required|string|max:255',
            'business_lga'      => 'required|string|max:255',
            'about_business'    => 'nullable|string',
            'business_services' => 'nullable|string',
            'business_address'  => 'required|string|max:500',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

    
        // Handle file uploads (store only filenames)
        if ($request->hasFile('id_card')) {
            $filename = time() . '_id_card.' . $request->id_card->extension();
            $request->id_card->storeAs('id_cards', $filename, 'public');
            $validated['id_card'] = $filename;
        }

        if ($request->hasFile('selfie_photo')) {
            $filename = time() . '_selfie.' . $request->selfie_photo->extension();
            $request->selfie_photo->storeAs('selfies', $filename, 'public');
            $validated['selfie_photo'] = $filename;
        }

        if ($request->hasFile('cac')) {
            $filename = time() . '_cac.' . $request->cac->extension();
            $request->cac->storeAs('cac_files', $filename, 'public');
            $validated['cac'] = $filename;
        }

        if ($request->hasFile('logo')) {
            $filename = time() . '_logo.' . $request->logo->extension();
            $request->logo->storeAs('logos', $filename, 'public');
            $validated['logo'] = $filename;
        }

        // Update landlord
        $landlord->update($validated);

        return response()->json([
            'message' => 'Record updated successfully',
            'data'    => $landlord
        ], 200);
    }

    public function fetchLandlordsForVerification(){
        $landlords = LandlordAgent::whereNotNull('id_card')->get();

        return response()->json($landlords);
    }

    public function verifyLandlordDocuments($userId)
    {
        $landlord = LandlordAgent::where('id', $userId)->firstOrFail();

        $updated = $landlord->update(['verified' => 'yes']);

        if (!$updated) {
            return response()->json([
                'message' => 'Something went wrong. Please try again later'
            ], 500);
        }

        $messageContent = [
            'name' => $landlord->user->first_name,
            'email' => $landlord->user->email,
            //'message' => 'Successfully Verified',
        ];

        // Send OTP via email
        try {
            Mail::to($landlord->user->email)->send(new LandlordVerificationMail($messageContent));
        } catch (\Exception $e) {
            $updated = $landlord->update(['verified' => 'no']);
            // Log and respond to mail failure
            return response()->json(['message' => 'Failed to send Verification response Mail. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Verification status successfully updated'
        ], 200);
    }

    public function rejectLandlordDocuments(Request $request, $userId){
        $user = LandlordAgent::where('id', $userId)->firstOrFail();

        if($user->verified === 'yes'){
            return response()->json(['message'=>'This user has already been verified'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $response = $user->delete();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Please try again later'], 500);
        }

        $messageContent = [
            'name' => $user->user->first_name,
            'email' => $user->user->email,
            'message' => $request->reason,
        ];

        // Send OTP via email
        try {
            Mail::to($user->user->email)->send(new LandlordVerificationMail($messageContent));
        } catch (\Exception $e) {
            //respond to mail failure
            return response()->json(['message' => 'Failed to send Verification response Mail. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Verification status successfully updated'
        ], 200);

    }


    //Delete this after using
    public function migrateApartmentLocations(Request $request)
    {
        $user = $request->user();
        $estate = app('estateManager');

        // Only allow system admin or estate owner
        if ($user->user_type_id !== 1 || $user->estate_manager_id !== $estate->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $apartments = Apartment::where('estate_manager_id', $estate->id)
                                ->where('location_id', null)
                                ->get();

        $updated = 0;
        $notFound = [];

        foreach ($apartments as $apartment) {

            $location = Location::where('estate_manager_id', $estate->id)
                                ->where('name', $apartment->location)
                                ->first();

            if ($location) {
                $apartment->location_id = $location->id;
                $apartment->save();
                $updated++;
            } else {
                    $new_location = Location::create([
                        'name' => $apartment->location,
                        'estate_manager_id' => $estate->id
                    ]);

                $apartment->location_id = $new_location->id;
                $apartment->save();
                $updated++;
            }
        }

        return response()->json([
            'message' => 'Migration completed',
            'updated_records' => $updated,
            'locations_not_found_for_apartments' => $notFound
        ]);
    }

}
