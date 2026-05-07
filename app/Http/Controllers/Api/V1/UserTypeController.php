<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\UserType;

class UserTypeController extends Controller
{
    public function create(Request $request){
        $agent = $request->user();

        $estateManager = app('estateManager');

        if($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1,2])){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

         // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admin_management' => [Rule::in(['yes', 'no'])],
            'user_management' => [Rule::in(['yes', 'no'])],
            'complaint_management' => [Rule::in(['yes', 'no'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userTypeNameCheck = UserType::where('name', $request->name)->where('estate_manager_id', $estateManager->id)->get();

        $reservedUserType = ['Landlord', 'Agent', 'Tenant']; 

        //Check if user type has already been created by Tenant or If the user type being created has the name of any of the reserved usertypes.
        if(!$userTypeNameCheck->isEmpty() || in_array(ucFirst($request->name), $reservedUserType)){
            return response()->json(['error' => 'Usertype already exists'], 422);
        }

        $response = UserType::create([
            'name' => $request->name,
            'estate_manager_id' => $estateManager->id,
            'admin_management' => $request->admin_management,
            'user_management' => $request->user_management,
            'complaint_management' => $request->complaint_management,
        ]);
        
        if($response){
            return response()->json(['message' => 'User Type added successfully!', 'data'=>$response], 201); 
        }

        return response()->json(['message'=> 'Something went wrong'],500);
    }

    public function update(Request $request, $slug, $id){
        $agent = $request->user();

        if($request->id == 1){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $estateManager = app('estateManager');

        if($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1,2])){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $user_type = UserType::findOrFail($id);

          // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admin_management' => [Rule::in(['yes', 'no'])],
            'user_management' => [Rule::in(['yes', 'no'])],
            'complaint_management' => [Rule::in(['yes', 'no'])],
        ]);

        $userTypeNameCheck = UserType::where('name', $request->name)->where('estate_manager_id', $estateManager->id)->whereNot('id', $id)->get();

        $reservedUserType = ['Landlord', 'Agent', 'Tenant']; 

        //Check if user type has already been created by Tenant or If the user type being created has the name of any of the reserved usertypes.
        if(!$userTypeNameCheck->isEmpty() || in_array(ucFirst($request->name), $reservedUserType)){
            return response()->json(['error' => 'Usertype already exists'], 422);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user_type->update($request->all());

        $response = $user_type->save();

        if(!$response){
            return response()->json(['message'=> 'Something went wrong'], 500);
        }

        return response()->json(['message' => 'User Type Updated successfully', 'data'=> $user_type ],200);
    }

    public function viewAll(Request $request){
        $user = $request->user();

        $estateManager = app('estateManager');

        if((int)$user->estate_manager_id != $estateManager->id){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $usertypes = UserType::where('estate_manager_id', $user->estate_manager_id)->orWhere('estate_manager_id', null)->paginate(20); 

        return response()->json(['data'=> $usertypes], 200);
        
    }

    public function viewOne(Request $request, $slug, $id){
        $user = $request->user();

        $estateManager = app('estateManager');

        $typeToView = UserType::where('id', $id)->where('estate_manager_id', $estateManager->id)->firstOrFail();

        if((int)$typeToView->estate_manager_id != $user->estate_manager_id){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }        

        return response()->json(['data'=> $typeToView], 200);
        
    }

    public function destroy(Request $request, $tenant_slug, $id){
        
        $agent = $request->user();

        $estateManager = app('estateManager');

        if($agent->estate_manager_id != $estateManager->id ||!in_array($agent->user_type_id, [1,2])){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        //this ensures an owner cannot be deleted
        if($id == 1){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $userType = UserType::findOrFail($id);

        //this ensures a tenant can only delete user types they created themselves
        if((int)$userType->estate_manager_id != $estateManager->id){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $response = $userType->delete();

        if(!$response){
            return response()->json(['message'=> 'Failed to delete, try again later'], 500);
        }

        return response()->json($response,204);
    }
}
