<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\{Guarantor, User};

class GuarantorController extends Controller
{
    public function store(Request $request, $slug)
    {
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        // $user = User::where('uuid', $uuid)
        //     ->where('estate_manager_id', $estateManager->id)
        //     ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'guarantor_full_name' => 'required|string|max:255',
            'guarantor_address' => 'required|string|max:255',
            'guarantor_email'  => 'required|email|max:225',
            'guarantor_number' => 'required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['user_id'] = $loggedUser->id;

        DB::beginTransaction();
        try {
            $guarantor = Guarantor::create($validatedData);
            DB::commit();

            return response()->json([
                'message' => 'Guarantor created successfully!',
                'guarantor' => $guarantor,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create guarantor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $slug, $guarantorId)
    {
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        $guarantor = Guarantor::where('id', $guarantorId)
            ->where('user_id', $loggedUser->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'guarantor_full_name' => 'sometimes|required|string|max:255',
            'guarantor_address' => 'sometimes|required|string|max:255',
            'guarantor_email'  => 'sometimes|required|email|max:225',
            'guarantor_number' => 'sometimes|required|numeric|regex:/^([0-9\s\-\+\(\)]*)$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();
        try {
            $guarantor->update($validatedData);
            DB::commit();

            return response()->json([
                'message' => 'Guarantor updated successfully!',
                'guarantor' => $guarantor
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update guarantor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $slug, $guarantorId)
    {
        $loggedUser = $request->user();
        $estateManager = app('estateManager');

        $guarantor = Guarantor::where('id', $guarantorId)
            ->where('user_id', $loggedUser->id)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $guarantor->delete();
            DB::commit();

            return response()->json(['message' => 'Guarantor deleted successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete guarantor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
