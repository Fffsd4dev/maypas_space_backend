<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLinkMail;

use App\Models\{User, TenantDocument, CompletedKyc, OtherDocument};

class TenantController extends Controller
{
    public function create(Request $request){
        $agent = $request->user();

        $estateManager = app('estateManager');

        if($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1,2])){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }
       // Validate request data
       $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'email' => 'required|email',
            'phone' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
       ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }        

        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $user = User::create([
            'first_name' => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
            'middle_name' => htmlspecialchars($validatedData['middle_name'], ENT_QUOTES, 'UTF-8'),
            'dob' => $validatedData['dob'],
            'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
            'password' => hash::make('TestingPassword'),
            'deactivated' => 'yes',
            'estate_manager_id' => $estateManager->id,
        ]);

       if(!$user){
            return response()->json(['message'=>'Something went wrong'], 500);
        }
        
        // Encrypt user ID for the token
        $encryptedId = Crypt::encryptString($user->id);

        $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

        // Generate signed URL that expires in 30 minutes
        $resetUrl = config('app.frontend_url').'/'.$estateManager->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

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
        
        return response()->json(['message' => 'Tenant added successfully! An email has been sent to new tenant to complete registration.', 'tenant' => $user], 201);
    }
    public function update(Request $request, $slug, $uuid)
    {
        // Get the logged in user
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        // Authorization check
        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (!in_array($loggedUser->user_type_id, [1, 2]) && $loggedUser->uuid != $uuid)
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        // Validation (only validate provided fields)
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'      => 'sometimes|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'gender' => 'sometimes|string|in:male,female',
            'nationality' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'other_phone' => 'nullable|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_number' => 'sometimes|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'emergency_contact_email'  => 'sometimes|email|max:225',
            'next_of_kin_name' => 'sometimes|string|max:255',
            'next_of_kin_number' => 'sometimes|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'next_of_kin_address' => 'sometimes|string|max:255',
            'next_of_kin_email'  => 'sometimes|email|max:225',
            'id_card' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'passport_photo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Handle file uploads (store only filenames)
        if ($request->hasFile('id_card')) {
            $filename = time().'_' .$loggedUser->uuid.'.' . $request->id_card->extension();
            $request->id_card->storeAs('tenant/id_cards', $filename, 'public');
            $validatedData['id_card'] = $filename;
        }

        if ($request->hasFile('passport_photo')) {
            $filename = time().'_'.$loggedUser->uuid.'.' . $request->passport_photo->extension();
            $request->passport_photo->storeAs('tenant/passport', $filename, 'public');
            $validatedData['passport_photo'] = $filename;
        }

        // Update only the provided fields
        $user->update($validatedData);

            return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
    }

    public function updatePersonal(Request $request)
    {
        // Get the logged in user
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        // Authorization check
        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (in_array($loggedUser->user_type_id, [1, 2]))
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $loggedUser->uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        // Validation (only validate provided fields)
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'      => 'sometimes|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'gender' => 'sometimes|string|in:male,female',
            'nationality' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'other_phone' => 'nullable|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Update only the provided fields
        $user->update($validatedData);

            return response()->json(['message' => 'Tenant personal details updated successfully!', 'user' => $user], 200);
    }

    public function createEmergency(Request $request)
    {
        // Get the logged in user
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        // Authorization check
        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (in_array($loggedUser->user_type_id, [1, 2]))
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $loggedUser->uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        // Validation
        $validator = Validator::make($request->all(), [
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_number' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'emergency_contact_email'  => 'required|email|max:225',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Update only the provided fields
        $user->update($validatedData);

        return response()->json(['message' => 'Emergency contact added successfully!', 'user' => $user], 200);
    }

    public function createKin(Request $request)
    {
        // Get the logged in user
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        // Authorization check
        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (in_array($loggedUser->user_type_id, [1, 2]))
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $loggedUser->uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        //Check if Emergency contact has been provided by Tenant
        if(!$user->emergency_contact_name){
            return response()->json(['message' => 'You need to fill up the emergency contact form'], 403);
        }

        // Validation (only validate provided fields)
        $validator = Validator::make($request->all(), [
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_number' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'next_of_kin_address' => 'required|string|max:255',
            'next_of_kin_email'  => 'required|email|max:225',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Update only the provided fields
        $user->update($validatedData);

        return response()->json(['message' => 'Next of kin details updated successfully!', 'user' => $user], 200);
    }

    public function createDocument(Request $request){
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (in_array($loggedUser->user_type_id, [1, 2]))
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $loggedUser->uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        //Check if next of kin has been provided by Tenant
        if(!$user->next_of_kin_name){
            return response()->json(['message' => 'You need to fill up the next of kin form'], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_card' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'passport_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();

        try {

            //Encrypt & Store ID Card
            if ($request->hasFile('id_card')) {
                $file = $request->file('id_card');
                $filename = time().'_'.$loggedUser->uuid.'.'.$file->extension();

                $encryptedContent = Crypt::encrypt(
                    file_get_contents($file->getRealPath())
                );

                Storage::disk('private')->put(
                    'tenant/id_cards/'.$filename,
                    $encryptedContent
                );

                $validatedData['identity_card'] = $filename;
            }

            // 🔐 Encrypt & Store Passport Photo
            if ($request->hasFile('passport_photo')) {
                $file = $request->file('passport_photo');
                $filename = time().'_'.$loggedUser->uuid.'.'.$file->extension();

                $encryptedContent = Crypt::encrypt(
                    file_get_contents($file->getRealPath())
                );

                Storage::disk('private')->put(
                    'tenant/passport/'.$filename,
                    $encryptedContent
                );

                $validatedData['passport_photo'] = $filename;
            }

            // Update user
            $user->update($validatedData);

            // Create KYC record
            CompletedKyc::create([
                'estate_manager_id' => $estateManager->id,
                'tenant_id' => $loggedUser->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Documents uploaded successfully!',
                'user' => $user
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            // Optional: delete uploaded files if something fails
            if (!empty($validatedData['identity_card'])) {
                Storage::disk('private')->delete(
                    'tenant/id_cards/'.$validatedData['identity_card']
                );
            }

            if (!empty($validatedData['passport_photo'])) {
                Storage::disk('private')->delete(
                    'tenant/passport/'.$validatedData['passport_photo']
                );
            }

            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage() // remove in production if needed
            ], 500);
        }
    }

    // public function createDocument(Request $request)
    // {
    //     $loggedUser = $request->user();
    //     $estateManager = app('estateManager');

    //     if (
    //         $loggedUser->estate_manager_id != $estateManager->id ||
    //         (in_array($loggedUser->user_type_id, [1, 2]))
    //     ) {
    //         return response()->json(['message'=> 'You are not authorized to do this'], 403);
    //     }

    //     $user = User::where('uuid', $loggedUser->uuid)
    //         ->where('estate_manager_id', $estateManager->id)
    //         ->firstOrFail();

    //     $validator = Validator::make($request->all(), [
    //         'id_card' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //         'passport_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $validatedData = [];

    //     // 🔐 Encrypt & Store ID Card
    //     if ($request->hasFile('id_card')) {
    //         $file = $request->file('id_card');
    //         $filename = time().'_'.$loggedUser->uuid.'.'.$file->extension();

    //         $encryptedContent = Crypt::encrypt(
    //             file_get_contents($file->getRealPath())
    //         );

    //         Storage::disk('private')->put(
    //             'tenant/id_cards/'.$filename,
    //             $encryptedContent
    //         );

    //         $validatedData['identity_card'] = $filename;
    //     }

    //     // 🔐 Encrypt & Store Passport Photo
    //     if ($request->hasFile('passport_photo')) {
    //         $file = $request->file('passport_photo');
    //         $filename = time().'_'.$loggedUser->uuid.'.'.$file->extension();

    //         $encryptedContent = Crypt::encrypt(
    //             file_get_contents($file->getRealPath())
    //         );

    //         Storage::disk('private')->put(
    //             'tenant/passport/'.$filename,
    //             $encryptedContent
    //         );

    //         $validatedData['passport_photo'] = $filename;
    //     }

    //     $user->update($validatedData);

    //     CompletedKyc::create([
    //         'estate_manager_id' => $estateManager->id,
    //         'tenant_id' => $loggedUser->id,
    //     ]);

    //     return response()->json([
    //         'message' => 'User updated securely!',
    //         'user' => $user
    //     ], 200);
    // }

    // public function createDocument2(Request $request, $slug, $uuid)
    // {
    //     // Get the logged in user
    //     $loggedUser = $request->user();
    //     $estateManager = app('estateManager');

    //     // Authorization check
    //     if (
    //         $loggedUser->estate_manager_id != $estateManager->id ||
    //         (!in_array($loggedUser->user_type_id, [1, 2]) && $loggedUser->uuid != $uuid)
    //     ) {
    //         return response()->json(['message'=> 'You are not authorized to do this'], 403);
    //     }

    //     $user = User::where('uuid', $uuid)
    //         ->where('estate_manager_id', $estateManager->id)
    //         ->firstOrFail();

    //     // Validation (only validate provided fields)
    //     $validator = Validator::make($request->all(), [
    //         'id_card' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //         'passport_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $validatedData = $validator->validated();

    //     // Handle file uploads (store only filenames)
    //     if ($request->hasFile('id_card')) {
    //         $filename = time().'_' .$loggedUser->uuid.'.' . $request->id_card->extension();
    //         $request->id_card->storeAs('tenant/id_cards', $filename, 'public');
    //         $validatedData['id_card'] = $filename;
    //     }

    //     if ($request->hasFile('passport_photo')) {
    //         $filename = time().'_'.$loggedUser->uuid.'.' . $request->passport_photo->extension();
    //         $request->passport_photo->storeAs('tenant/passport', $filename, 'public');
    //         $validatedData['passport_photo'] = $filename;
    //     }

    //     // Update only the provided fields
    //     $user->update($validatedData);

    //     return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
    // }
    
    public function show(Request $request, $slug, $uuid)
    {
        $loggedUser = $request->user();
    
        $estateManager = app('estateManager');

        $user = User::where('uuid', $uuid)->where('estate_manager_id', $estateManager->id)->firstOrFail();
    
        //This method will be used to update by all usertypes. Landlord and agent can update while the user who owns the profile can edit too
        if ($loggedUser->estate_manager_id != $estateManager->id || !in_array($loggedUser->user_type_id, [1, 2]) && $loggedUser->id != $user->id) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }   
    
        return response()->json(['user' => $user], 200);
    }

    public function index(Request $request)
    {
        $agent = $request->user();

        $estateManager = app('estateManager');

        if ($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1, 2])) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $users = User::where('estate_manager_id', $estateManager->id)
            ->paginate(10);

        return response()->json(['users' => $users], 200);
    }

    public function destroy(Request $request, $slug, $uuid)
    {
        $agent = $request->user();

        $estateManager = app('estateManager');

        if ($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1, 2])) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $uuid)->where('estate_manager_id', $estateManager->id)->firstOrFail();

        $user->delete();

        return response()->json(['message' => 'Tenant deleted successfully!'], 200);
    }

    public function addOther(Request $request){
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        if (
            $loggedUser->estate_manager_id != $estateManager->id ||
            (in_array($loggedUser->user_type_id, [1, 2]))
        ) {
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user = User::where('uuid', $loggedUser->uuid)
            ->where('estate_manager_id', $estateManager->id)
            ->firstOrFail();

        //Check if next of kin has been provided by Tenant
        if(!$user->next_of_kin_name){
            return response()->json(['message' => 'You need to fill up the next of kin form'], 403);
        }  
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'filename' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();

        try {

            // Encrypt & Store
            if ($request->hasFile('filename')) {
                $file = $request->file('filename');
                $filename = time().'_'.$loggedUser->uuid.'.'.$file->extension();

                $encryptedContent = Crypt::encrypt(
                    file_get_contents($file->getRealPath())
                );

                Storage::disk('private')->put(
                    'tenant/other_documents/'.$filename,
                    $encryptedContent
                );

                $validatedData['filename'] = $filename;
                $validatedData['estate_manager_id'] = $loggedUser->estate_manager_id;
            }

            $validatedData['tenant_id'] = $loggedUser->id;

            OtherDocument::create($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Document uploaded successfully!',
                'user' => $user
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            // Optional: delete uploaded files if something fails
            if (!empty($validatedData['filename'])) {
                Storage::disk('private')->delete(
                    'tenant/other_documents/'.$validatedData['filename']
                );
            }

            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage() // remove in production if needed
            ], 500);
        }
    }

    //For tenants to create their accounts themselves
    public function selfCreate(Request $request){

        $estateManager = app('estateManager');

       // Validate request data
       $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'dob' => 'required|date|before:today',
            'phone' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'password' => 'required|string|confirmed',
       ]);

       $userCheck = User::where('email', $request->email)->where('estate_manager_id', $estateManager->id)->first();

       if($userCheck){
            return response()->json(['message' => 'You are already a registered user'], 422);
       }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }        

        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $user = User::create([
            'first_name' => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
            'middle_name' => htmlspecialchars($validatedData['middle_name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
            'dob' => htmlspecialchars($validatedData['dob'], ENT_QUOTES, 'UTF-8'),
            'password' => hash::make($validatedData['password']),
            'deactivated' => 'yes',
            'email_verified_at' => now(),
            'estate_manager_id' => $estateManager->id,
        ]);

       if(!$user){
            return response()->json(['message'=>'Something went wrong'], 500);
        }
        
        // Encrypt user ID for the token
        $encryptedId = Crypt::encryptString($user->id);

        $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

        // Generate signed URL that expires in 30 minutes
        $resetUrl = config('app.frontend_url').'/'.$estateManager->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

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
        
        return response()->json(['message' => 'User added successfully! An email has been sent to new user to complete registration.', 'user' => $user], 201);
    }



  
}
