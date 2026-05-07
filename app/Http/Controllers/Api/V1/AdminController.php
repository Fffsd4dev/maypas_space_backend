<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLinkMail;

use App\Models\Admin;

class AdminController extends Controller
{
    public function create(Request $request){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'You are not authorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role_id' => 'numeric|gte:2',
            'email' => 'required|email|unique:admins,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        $admin = Admin::create([
            'name' => htmlspecialchars($validatedData['name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
            'role_id' => $request->role_id,
            'password' => hash::make('testingPassword'),
        ]);

        if(!$admin){
            return response()->json(['message'=>'Something went wrong'], 500);
        }
        
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

        // Send OTP via email
        try {
            Mail::to($admin->email)->send(new PasswordResetLinkMail($messageContent));
        } catch (\Exception $e) {
            // Log and respond to mail failure
            return response()->json(['message' => 'Failed to send password setting email. Please try again.'], 500);
        }
        
        return response()->json(['message' => 'Admin added successfully! An email has been sent to new admin to complete registration.', 'admin' => $admin], 201);        
    }

    public function destroy(Request $request, $uuid){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $admin = Admin::where('uuid', $uuid)->select('id')->firstOrFail();

        if($admin->id === 1){
            return response()->json(['message'=> 'Cannot delete this Admin'], 403);
        }

        $response = $admin->delete();

        if(!$response){
            return response()->json(['message'=> 'Failed to delete, try again later'], 500);
        }

        return response()->json(['message'=> 'Admin deleted successfully' ],204);
    }

    public function viewAll(){
        // $admins = Admin::select('uuid','name','email','role_id')
        // ->with('role:id,name,manage_properties,manage_accounts,manage_estate_manager,manage_admins')
        // ->paginate(20);

        $admins = DB::table('admins')
                ->select(
                    'admins.id',
                    'admins.uuid',
                    'admins.name',
                    'admins.email',
                    'admins.role_id',
                    'admin_roles.name as role_name',
                    'admin_roles.manage_properties',
                    'admin_roles.manage_accounts',
                    'admin_roles.manage_estate_manager',
                    'admin_roles.manage_admins'
                )
                ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
                ->paginate(20);

                $admins->getCollection()->transform(function ($admin) {
            return [
                'id' => $admin->id,
                'uuid' => $admin->uuid,
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'role' => [
                    'id' => $admin->role_id,
                    'name' => $admin->role_name,
                    'manage_properties' => $admin->manage_properties,
                    'manage_accounts' => $admin->manage_accounts,
                    'manage_estate_manager' => $admin->manage_estate_manager,
                    'manage_admins' => $admin->manage_admins,
                ]
            ];
        });


        return response()->json(['data'=> $admins ],200);
    }

    public function viewOne($uuid){
        $admin = DB::table('admins')
        ->select('admins.uuid', 'admins.name', 'admins.email', 'admins.role_id', 
            'admin_roles.name as role_name', 
            'admin_roles.manage_properties', 
            'admin_roles.manage_accounts', 
            'admin_roles.manage_estate_manager', 
            'admin_roles.manage_admins'
        )
        ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
        ->where('admins.uuid', $uuid)
        ->first();

        if ($admin) {
            $admin = [
                'uuid' => $admin->uuid,
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'role' => [
                    'id' => $admin->role_id,
                    'name' => $admin->role_name,
                    'manage_properties' => $admin->manage_properties,
                    'manage_accounts' => $admin->manage_accounts,
                    'manage_estate_manager' => $admin->manage_estate_manager,
                    'manage_admins' => $admin->manage_admins,
                ]
            ];
        }

        return response()->json(['data'=> $admin],200);
    }

    public function update(Request $request, $uuid){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $admin = Admin::where('uuid', $uuid)->firstOrFail(); 


        if($admin->id === 1){
            return response()->json(['message'=> 'Cannot update this user'], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role_id' => 'numeric|gte:2',
            'email' => 'required|email|exists:admins,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin->update($request->all());

        $response = $admin->save();

        if(!$response){
            return response()->json(['message'=> 'Something went wrong'], 500);
        }

        return response()->json(['message' => 'Admin Updated successfully', 'data'=> $admin ],200);
    
    }

}
