<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Landlord;

class LandlordController extends Controller
{
    public function create(Request $request){

       // Validate request data
       $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'phone_number' => 'required|string|max:255',
        'bank_name' => 'nullable|string|max:255',
        'bank_account_number' => 'nullable|string|max:255',
       ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estate = app('estateManager');
        
        $landlord = Landlord::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'estate_manager_id' => $estate->id,
        ]);

        if(!$landlord){
            return response()->json(['message'=> 'Something went wrong, try again'], 500);
        }

        return response()->json([   'message'=> 'Landlord created successfully', 'data'=> $landlord ],201);
    
    }

    public function update(Request $request, $slug, $id){

        $estate = app('estateManager');

        $landlord = Landlord::where('estate_manager_id', $estate->id)->where('id', $id)->firstOrFail();

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $landlord->update($request->all());

        $response = $landlord->save();

        if(!$response){
            return response()->json(['message'=> 'Something went wrong'], 500);
        }

        return response()->json(['message' => 'Landlord Updated successfully', 'data'=> $landlord ],200);
    }

    public function destroy(Request $request){

       // Validate request data
       $validator = Validator::make($request->all(), [
        'id' => 'required|numeric|gte:1',
       ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estate = app('estateManager');

        $landlord = Landlord::where('id', $request->id)->where('estate_manager_id', $estate->id)->firstOrFail();

        $response = $landlord->delete();
        

        if(!$response){
            return response()->json(['message'=> 'Failed to delete, try again later'], 500);
        }

        return response()->json(['message'=> 'Landlord deleted successfully','data'=> $landlord ],204);
    }

    public function viewAll(){
        $estate = app('estateManager');

        $landlords = Landlord::where('estate_manager_id', $estate->id)
                    ->select('id', 'name', 'phone_number', 'bank_name', 'bank_account_number', 'estate_manager_id')
                    ->get();

        return response()->json(['data'=> $landlords ],200);
    }

    public function viewOne($slug, $id){
        $estate = app('estateManager');

        $landlord = Landlord::where('id', $id)
                    ->where('estate_manager_id', $estate->id)
                    ->select('id', 'name', 'phone_number', 'bank_name', 'bank_account_number', 'estate_manager_id')
                    ->get();

        return response()->json(['data'=> $landlord],200);
    }
}
