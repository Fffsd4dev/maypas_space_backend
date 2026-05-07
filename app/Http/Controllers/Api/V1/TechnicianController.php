<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

use App\Models\Technician;


class TechnicianController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'specialty_id' => 'required|numeric|gte:1|exists:specialties,id'
        ]);

        $validated['estate_manager_id'] = $estate->id;

        $technician = Technician::create($validated);

        return response()->json($technician, Response::HTTP_CREATED);
    }

    public function update(Request $request, $slug, $id): JsonResponse
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $technician = Technician::where('id', $id)->where('estate_manager_id', $estate->id)->first();

        if (!$technician) {
            return response()->json(
                ['message' => 'Technician not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
            'specialty_id' => 'sometimes|required|numeric|gte:1|exists:specialties,id'
        ]);

        $technician->update($validated);

        return response()->json($technician);
    }

    public function index(Request $request): JsonResponse
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $technicians = Technician::with('specialist:id,name')->where('estate_manager_id', $estate->id)->get();
        
        return response()->json($technicians);
    }

    public function show(Request $request, $slug, $id): JsonResponse
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $technician = Technician::where('id', $id)->first();

        if (!$technician) {
            return response()->json(
                ['message' => 'Technician not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json($technician);
    }

    public function destroy(Request $request, $slug, $id): JsonResponse
    {
        $landlord = $request->user();

        $estate = app('estateManager');

        if (!$landlord->user_type_id === 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $technician =  Technician::where('id', $id)->first();

        if (!$technician) {
            return response()->json(
                ['message' => 'Technician not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $technician->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
