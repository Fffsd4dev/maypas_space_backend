<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

use App\Models\AdminRole as Role;

class AdminRolesController extends Controller
{
    public function create(Request $request){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'Only Super Admin is authorized to do this'], 403);
        }

       // Validate request data
       $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:admin_roles,name',
        'manage_properties' => [Rule::in(['yes', 'no'])],
        'manage_accounts' => [Rule::in(['yes', 'no'])],
        'manage_admins' => [Rule::in(['yes', 'no'])],
        'manage_estate_manager' => [Rule::in(['yes', 'no'])],
       ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $role = Role::create([
            'name' => $request->name,
            'manage_properties' => $request->manage_properties,
            'manage_accounts' => $request->manage_accounts,
            'manage_admins' => $request->manage_admins,
            'manage_estate_manager' => $request->manage_estate_manager,
        ]);

        if(!$role){
            return response()->json(['message'=> 'Something went wrong, try again'], 500);
        }

        return response()->json([   'message'=> 'Role created successfully', 'data'=> $role ],201);
    
    }

    public function update(Request $request, $id){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'Only Super Admin is authorized to do this'], 403);
        }

        $role = Role::findOrFail($id);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                Rule::unique('admin_roles', 'name')->ignore($id),
                ],
            'manage_properties' => [Rule::in(['yes', 'no'])],
            'manage_accounts' => [Rule::in(['yes', 'no'])],
            'manage_admins' => [Rule::in(['yes', 'no'])],
            'manage_estate_manager' => [Rule::in(['yes', 'no'])],
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->update($request->all());

        $response = $role->save();

        if(!$response){
            return response()->json(['message'=> 'Something went wrong'], 500);
        }

        return response()->json(['message' => 'Role Updated successfully', 'data'=> $role ],200);
    }

    public function destroy(Request $request){
        $admin = $request->user();

        if($admin->role_id != 1){
            return response()->json(['message'=> 'Only Super Admin is authorized to do this'], 403);
        }

       // Validate request data
       $validator = Validator::make($request->all(), [
        'id' => 'required|numeric|gte:1',
       ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if($request->id == 1){
            return response()->json(['message'=> 'Super Admin role cannot be deleted'], 403);
        }

        $role = Role::findOrFail($request->id);

        $response = $role->delete();

        if(!$response){
            return response()->json(['message'=> 'Failed to delete, try again later'], 500);
        }

        return response()->json(['message'=> 'Role deleted successfully','data'=> $role ],204);
    }

    public function viewAll(){
        
        $roles = Role::where('id', '!=', 1)->get();

        return response()->json(['data'=> $roles ],200);
    }

    public function viewOne($id){
        if($id == 1){
            return response()->json(['message'=> 'This role does not exist'], 403);  
        }
        $role = Role::where('id', $id)->get();

        return response()->json(['data'=> $role],200);
    }
}
