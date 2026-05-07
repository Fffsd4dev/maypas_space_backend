<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetLinkMail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\Models\{EstateManager,LandlordAgent};

class EstateManagerController extends Controller
{
   

    
    public function create(Request $request)
    {
        $admin = $request->user();

        $raw = DB::table('admins')
        ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
        ->where('admins.uuid', $admin->uuid)
        ->select(
            'admins.id',
            'admins.uuid',
            'admins.role_id',
            'admin_roles.id as role_id_from_roles',
            'admin_roles.manage_estate_manager'
        )
        ->first();

        if (!$raw) {
            abort(404);
        }

        $role = [
            'id' => $raw->id,
            'role_id' => $raw->role_id,
            'admin_roles' => [
                'id' => $raw->role_id_from_roles,
                'manage_estate_manager' => $raw->manage_estate_manager,
            ]
        ];

        if ($role['admin_roles']['manage_estate_manager'] !== 'yes') {
            return response()->json(['message' => 'You are not authorized to do this'], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'estate_name' => 'required|string|max:255|unique:estate_managers,estate_name',
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email',
            'phone'       => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        //remove spaces and invalid characters from slug
        $sanitizedSlug = strtolower(
            preg_replace('/[^a-zA-Z]/', '', $request->estate_name)
        );

        //if system admin creates admin, let created_by be null else fill in the ID
        $createdby = $admin->role_id == 1 ? null : $admin->id;

        try {
            DB::beginTransaction(); // Start transaction

            // Create estate manager
            $estateManager = EstateManager::create([
                'estate_name'        => $validatedData['estate_name'],
                'slug'               => $sanitizedSlug,
                'created_by_admin_id'=> $createdby,
                'subscription_id'    => null,
            ]);

            if (!$estateManager) {
                throw new \Exception("Estate manager creation failed");
            }

            // Create landlord
            $landlord = LandlordAgent::create([
                'first_name'        => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
                'last_name'         => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
                'email'             => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
                'phone'             => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
                'user_type_id'      => 1,
                'password'          => Hash::make('TestingPassword'),
                'estate_manager_id' => $estateManager->id,
            ]);

            if (!$landlord) {
                throw new \Exception("Landlord creation failed");
            }

            // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($landlord->id);
            $signature   = hash_hmac('sha256', $encryptedId, config('app.key'));

            $resetUrl = config('app.frontend_url') . '/' . $estateManager->slug . '/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

            $messageContent = [
                'name'     => $landlord->first_name,
                'email'    => $landlord->email,
                'resetUrl' => $resetUrl,
            ];

            // Try sending email
            Mail::to($landlord->email)->send(new PasswordResetLinkMail($messageContent));

            DB::commit(); // Commit everything only if email succeeds

            return response()->json([
                'message' => 'Estate Manager added successfully! An email has been sent to new Landlord to complete registration.',
                'estate' => $estateManager,
                'landlord'    => $landlord
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); //  Rollback all DB inserts if something fails

            // Optionally log the error for debugging
            \Log::error('Create estate manager failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create Landlord and send email. Please try again.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $uuid){
        $admin = $request->user();

        $raw = DB::table('admins')
            ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
            ->where('admins.uuid', $admin->uuid)
            ->select(
                'admins.id',
                'admins.role_id',
                'admin_roles.id as role_id_from_roles',
                'admin_roles.manage_estate_manager'
            )
            ->first();

        if (!$raw) {
            abort(404);
        }

        $role = [
            'id' => $raw->id,
            'role_id' => $raw->role_id,
            'admin_roles' => [
                'id' => $raw->role_id_from_roles,
                'manage_estate_manager' => $raw->manage_estate_manager,
            ]
        ];

        if($role['admin_roles']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = EstateManager::where('uuid', $uuid)->firstOrFail();

       // Validate request data
        $validator = Validator::make($request->all(), [
            'estate_name' => [
                            'required',
                            'string',
                            Rule::unique('estate_managers', 'estate_name')->ignore($estateManager->id),
                            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }    

        // Retrieve validated data from the validator instance
        $validatedData = $validator->validated();

        //remove spaces and invalid characters from slug
        $sanitizedSlug = strtolower(
            preg_replace('/[^a-zA-Z]/', '', $request->estate_name)
        );

        $estateManager->estate_name = $validatedData['estate_name'];
        $estateManager->slug = $sanitizedSlug;

        $response = $estateManager->save();

        if(!$response){
            return response()->json(['message'=> 'something went wrong, please try again'],500);    
        }

        return response()->json(['message'=>'Estate Updated successfully', 'data'=>$estateManager], 200);
    }

    public function getEstateManager(Request $request, $uuid){
        $admin = $request->user();

        $raw = DB::table('admins')
            ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
            ->where('admins.uuid', $admin->uuid)
            ->select(
                'admins.id',
                'admins.role_id',
                'admin_roles.id as role_id_from_roles',
                'admin_roles.manage_estate_manager'
            )
            ->first();
          

        if (!$raw) {
            abort(404);
        }

        $role = [
            'id' => $raw->id,
            'role_id' => $raw->role_id,
            'admin_roles' => [
                'id' => $raw->role_id_from_roles,
                'manage_estate_manager' => $raw->manage_estate_manager,
            ]
        ];


        if($role['admin_roles']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = EstateManager::where('uuid', $uuid)
    ->with('landlordAgents')
    ->firstOrFail();


        return response()->json(['data'=> $estateManager],200);
    }

    public function getEstateManagers(Request $request){
        $admin = $request->user();

        $raw = DB::table('admins')
            ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
            ->where('admins.uuid', $admin->uuid)
            ->select(
                'admins.id',
                'admins.role_id',
                'admin_roles.id as role_id_from_roles',
                'admin_roles.manage_estate_manager'
            )
            ->first();

 
        if (!$raw) {
            abort(404);
        }

        $role = [
            'id' => $raw->id,
            'role_id' => $raw->role_id,
            'admin_roles' => [
                'id' => $raw->role_id_from_roles,
                'manage_estate_manager' => $raw->manage_estate_manager,
            ]
        ];


        if($role['admin_roles']['manage_estate_manager'] !== 'yes'){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = EstateManager::paginate(20);

        return response()->json(['data'=> $estateManager ],200);
    }

    public function destroy(Request $request, $uuid)
    {
        $admin = $request->user();

        $raw = DB::table('admins')
            ->join('admin_roles', 'admins.role_id', '=', 'admin_roles.id')
            ->where('admins.uuid', $admin->uuid)
            ->select(
                'admins.id',
                'admins.role_id',
                'admin_roles.id as role_id_from_roles',
                'admin_roles.manage_estate_manager'
            )
            ->first();

        if (!$raw) {
            abort(404);
        }

        $adminWithRole = [
            'id' => $raw->id,
            'role_id' => $raw->role_id,
            'admin_roles' => [
                'id' => $raw->role_id_from_roles,
                'manage_estate_manager' => $raw->manage_estate_manager,
            ]
        ];


        if (!$adminWithRole || $adminWithRole['admin_roles']['manage_estate_manager'] !== 'yes') {
            return response()->json(['message' => 'You are not authorized to do this'], 403);
        }

        // Attempt to delete estate manager and its users
        $estateManager = EstateManager::where('uuid', $uuid)->firstOrFail();

        if (!$estateManager) {
            return response()->json(['message' => 'Estate Manager not found'], 404);
        }

        LandlordAgent::where('estate_manager_id', $estateManager->id)->delete();

        if (!$estateManager->delete()) {
            return response()->json(['message' => 'Failed to delete, try again later'], 500);
        }

        return response()->json(['message' => 'Estate Manager deleted successfully'], 200);
    }

    public function signUp(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'estate_name' => 'required|string|max:255|unique:estate_managers,estate_name',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve validated data
        $validatedData = $validator->validated();

        //remove spaces and invalid characters from slug
        $sanitizedSlug = strtolower(
            preg_replace('/[^a-zA-Z]/', '', $request->estate_name)
        );

        try {
            DB::beginTransaction();

            $estateManager = EstateManager::create([
                'estate_name'=> $validatedData['estate_name'],
                'slug' => $sanitizedSlug,
            ]); 

            if(!$estateManager){
                throw new \Exception('Failed to create Estate Manager');
            }

            $landlordAgent = LandlordAgent::create([
                'first_name' => htmlspecialchars($validatedData['first_name'], ENT_QUOTES, 'UTF-8'),
                'last_name' => htmlspecialchars($validatedData['last_name'], ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
                'phone' => htmlspecialchars($validatedData['phone'], ENT_QUOTES, 'UTF-8'),
                'user_type_id' => 1,
                'password' => Hash::make('TestingPassword'),
                'estate_manager_id' => $estateManager->id,
            ]);

            if(!$landlordAgent){
                throw new \Exception('Failed to create Landlord Agent');
            }

            // Encrypt user ID for the token
            $encryptedId = Crypt::encryptString($landlordAgent->id);
            $signature = hash_hmac('sha256', $encryptedId, config('app.key'));

            $resetUrl = config('app.frontend_url').'/'.$estateManager->slug.'/reset-password?token=' . urlencode($encryptedId) . '&signature=' . urlencode($signature);

            $messageContent = [
                'name' => $landlordAgent->first_name,
                'email' => $landlordAgent->email,
                'resetUrl' => $resetUrl,
            ];

            // Send OTP via email
            Mail::to($landlordAgent->email)->send(new PasswordResetLinkMail($messageContent));

            DB::commit();

            return response()->json([
                'message' => 'Estate added successfully! An email has been sent to complete registration.',
                'landlordAgent' => $landlordAgent
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SignUp Failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
