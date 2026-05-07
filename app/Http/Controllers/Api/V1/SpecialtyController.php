<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

use App\Models\Specialty;

class SpecialtyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:specialties,name'],
        ]);

        $specialty = Specialty::create($validated);

        return response()->json($specialty, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $specialist = Specialty::where('id', $id)->first();

        if (!$specialist) {
            return response()->json(
                ['message' => 'Specialist not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                Rule::unique('specialties', 'name')->ignore($id)
            ]
        ]);

        $specialist->update($validated);

        return response()->json($specialist);
    }

    public function index(): JsonResponse
    {
        $specialists = Specialty::all();
        
        return response()->json($specialists);
    }

    public function show($id): JsonResponse
    {
        $specialist = Specialty::where('id', $id)->first();

        if (!$specialist) {
            return response()->json(
                ['message' => 'Specialist not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json($specialist);
    }

    public function destroy($id): JsonResponse
    {
        $specialist =  Specialty::where('id', $id)->first();

        if (!$specialist) {
            return response()->json(
                ['message' => 'Specialist not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $specialist->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
