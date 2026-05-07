<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\LandlordAgent;

class UserController extends Controller
{
    public function viewOwn(Request $request){
        $user = $request->user();

        $user = User::where('id', $user->id)
        ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'user_type_id', 'created_at']) // fix: created_at not create_at
        ->with([
            'landlordAgent:id,user_id,business_name,business_state,business_lga,about_business,business_services,business_address,logo,verified',
            'user_type:id,name'
        ])
        ->first();

        if(!$user){
            return response()->json(['message'=>'Not found'], 404);
        }

        return response()->json(['data'=> $user], 200);
    }

    public function update(Request $request){
        $userToView = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($userToView->id),
                ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($userToView->id);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;

        $response = $user->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong. Try again later'], 500);
        }

        return response()->json(['message' =>'Profile updated successfully'], 201);

    }

    public function deactivate(Request $request){
        $userToView = $request->user();

        $user = User::findOrFail($userToView->id);

        $user->deactivated = 'yes';

        $response = $user->update();

        if(!$response){
            return response()->json(['message'=>'Something went wrong.Try again later'], 500);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' =>'Profile deactivated'], 201);

    }


}
