<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\{Branch,Location};

class BranchController extends Controller
{
    public function store(Request $request)
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location_uuid' => 'required|string|exists:locations,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $location = Location::where('uuid', $validated['location_uuid'])
                            ->where('estate_manager_id', $estate->id)
                            ->firstOrFail();

        $validated['estate_manager_id'] = $estate->id;
        $validated['location_id'] = $location->id;

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    public function index(Request $request, $slug, $locationUuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $location = Location::where('estate_manager_id', $estate->id)
                            ->where('uuid', $locationUuid)
                            ->select('uuid','id')
                            ->firstOrFail();

        $branches = Branch::where('estate_manager_id', $estate->id)
                            ->where('location_id', $location->id)
                            ->with('location')
                            ->get();

        return response()->json($branches, 200);
    }

    public function show(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $branch = Branch::where('estate_manager_id', $estate->id)
                        ->where('uuid', $uuid)
                        ->with('location')
                        ->first();

        if (!$branch) {
            return response()->json(['message' => "Branch not found"], 404);
        }

        return response()->json($branch, 200);
    }

    public function update(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $branch = Branch::where('estate_manager_id', $estate->id)
                        ->where('uuid', $uuid)
                        ->first();

        if (!$branch) {
            return response()->json(['message' => "Branch not found"], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location_uuid' => 'required|string|exists:locations,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        //Ensure location belongs to same estate
        $location = Location::where('estate_manager_id', $estate->id)
                            ->where('uuid', $validated['location_uuid'])
                            ->first();

        if (!$location) {
            return response()->json(['message' => "Invalid location for this estate"], 403);
        }

        $validated['location_id'] = $location->id;

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $branch
        ], 200);
    }

    public function destroy(Request $request, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $branch = Branch::where('estate_manager_id', $estate->id)
                        ->where('uuid', $uuid)
                        ->first();

        if (!$branch) {
            return response()->json(['message' => "Branch not found"], 404);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ], 200);
    }
}
