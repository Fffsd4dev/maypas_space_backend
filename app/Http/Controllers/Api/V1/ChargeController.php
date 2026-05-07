<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\{Charge, ApartmentUnit};

class ChargeController extends Controller
{
    public function store(Request $request, $slug, $unitUuid)
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        $landlord = $request->user();
        $estate = app('estateManager');

       if ( (int) $landlord->user_type_id !== 1 &&(int) $landlord->estate_manager_id !== (int) $estate->id
) {
    return response()->json(['message' => 'You are not authorized'], 403);
}



        $apartmentUnit = ApartmentUnit::select('id')
                        ->where('uuid', $unitUuid)
                        ->first();

        if(!$apartmentUnit){
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }

        $validator = Validator::make($request->all(), [
                'charge_type'     => 'in:one_off,recurrent',
                'name' => 'required|string|max:255',
                'fee_type'     => 'in:fixed,percentage',
                'value'  => 'required|numeric|gte:1',
                'name'=>'required|string',
        ]);
        

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        

        $validated = $validator->validated();

        $checkCharge = Charge::where('name', $validated['name'])
                        ->where('estate_manager_id', $estate->id)
                        ->first();

        if($checkCharge){
            return response()->json(['message'=>'You already have a charge with this name'], 404);
        }

        $validated['estate_manager_id'] = $estate->id;
        $validated['apartment_unit_id'] = $apartmentUnit->id;

        $Charge = Charge::create($validated);

        return response()->json($Charge, Response::HTTP_CREATED);
    }

    public function index(Request $request, $slug, $unitUuid)
    {
        $unitUuid = strip_tags($unitUuid);
        
         $landlord = $request->user();
        $estate = app('estateManager');

       if ( (int) $landlord->user_type_id !== 1 &&(int) $landlord->estate_manager_id !== (int) $estate->id
) {
    return response()->json(['message' => 'You are not authorized'], 403);
}

        $apartmentUnit = ApartmentUnit::select('id')
            ->where('uuid', $unitUuid)
            ->first();

        if (!$apartmentUnit) {
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }

        $charges = Charge::where('apartment_unit_id', $apartmentUnit->id)
            ->where('estate_manager_id', $estate->id)
            ->get();

        return response()->json($charges);
    }

    public function show(Request $request, $slug, $unitUuid, $chargeId)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

       $landlord = $request->user();
        $estate = app('estateManager');

        if ( (int) $landlord->user_type_id !== 1 &&(int) $landlord->estate_manager_id !== (int) $estate->id
) {
    return response()->json(['message' => 'You are not authorized'], 403);
}


        $apartmentUnit = ApartmentUnit::select('id')
            ->where('uuid', $unitUuid)
            ->first();

        if (!$apartmentUnit) {
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }

        $charge = Charge::where('id', $chargeId)
            ->where('apartment_unit_id', $apartmentUnit->id)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$charge) {
            return response()->json(['message' => 'Charge not found'], 404);
        }

        return response()->json($charge);
    }

    public function update(Request $request, $slug, $unitUuid, $chargeId)
    {
        $landlord = $request->user();
        $estate = app('estateManager');
        

        if ( (int) $landlord->user_type_id !== 1 &&(int) $landlord->estate_manager_id !== (int) $estate->id
) {
    return response()->json(['message' => 'You are not authorized'], 403);
}

        $apartmentUnit = ApartmentUnit::select('id')
            ->where('uuid', $unitUuid)
            ->first();

        if (!$apartmentUnit) {
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }

        $charge = Charge::where('id', $chargeId)
            ->where('apartment_unit_id', $apartmentUnit->id)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$charge) {
            return response()->json(['message' => 'Charge not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'charge_type' => 'in:one_off,recurrent',
            'name' => 'sometimes|string|max:255',
            'fee_type' => 'in:fixed,percentage',
            'value' => 'numeric|gte:1',
            'name'=>'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $checkCharge = Charge::where('name', $request->name)
                        ->where('estate_manager_id', $estate->id)
                        ->first();

        if($checkCharge && $charge->id != $checkCharge->id){
            return response()->json(['message'=>'You already have a charge with this name'], 404);
        }

        $charge->update($validator->validated());

        return response()->json(['message' => 'Charge updated successfully', 'charge' => $charge]);
    }

    public function destroy(Request $request, $slug, $unitUuid, $chargeId)
    {
        $landlord = $request->user();
       
        $estate = app('estateManager');
    

      if ( (int) $landlord->user_type_id !== 1 &&(int) $landlord->estate_manager_id !== (int) $estate->id
) {
    return response()->json(['message' => 'You are not authorized'], 403);
}
        $apartmentUnit = ApartmentUnit::select('id')
            ->where('uuid', $unitUuid)
            ->first();

        if (!$apartmentUnit) {
            return response()->json(['message' => 'Apartment Unit not found'], 404);
        }

        $charge = Charge::where('id', $chargeId)
            ->where('apartment_unit_id', $apartmentUnit->id)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$charge) {
            return response()->json(['message' => 'Charge not found'], 404);
        }

        $charge->delete();

        return response()->json(['message' => 'Charge deleted successfully'], 200);
    }
}
