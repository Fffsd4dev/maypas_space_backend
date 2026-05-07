<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Location;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $nameCheck = Location::where('estate_manager_id', $estate->id)
                                ->where('name', $validated['name'])
                                ->first();
        
        if($nameCheck){
            return response()->json(['message'=>'You have already created a location with this name'], 422);
        }

        $validated['estate_manager_id'] = $estate->id;

        $location = Location::create($validated);

        return response()->json($location, 201);
    }

    public function index(Request $request)
    {
        $estate = app('estateManager');

        $locations = Location::where('estate_manager_id', $estate->id)->get();

        return response()->json($locations, 200);
    }

    public function show($slug, $uuid)
    {
        $estate = app('estateManager');

        $location = Location::where('estate_manager_id', $estate->id)
                            ->where('uuid', $uuid)
                            ->first();

        if (!$location) {
            return response()->json(['message' => "Location not found"], 404);
        }

        return response()->json(['message'=>'Location loaded successfully', 'data'=>$location],200);
    }

    public function update(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $location = Location::where('estate_manager_id', $estate->id)
                            ->where('uuid', $uuid)
                            ->first();

        if (!$location) {
            return response()->json(['message' => "Location not found"], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $nameCheck = Location::where('estate_manager_id', $estate->id)
                                ->where('name', $validated['name'])
                                ->where('uuid', '!=',$uuid)
                                ->first();

        $location->update($validated);

        return response()->json([
            'message' => 'Location updated successfully',
            'data' => $location
        ], 200);
    }

    public function destroy(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $location = Location::where('estate_manager_id', $estate->id)
                            ->where('uuid', $uuid)
                            ->first();

        if (!$location) {
            return response()->json(['message' => "Location not found"], 404);
        }

        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully'
        ], 200);
    }
}
