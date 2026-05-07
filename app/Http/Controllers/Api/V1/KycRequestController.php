<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\Models\{CompletedKyc, User};

class KycRequestController extends Controller
{
    public function index(Request $request){
        $landlord = $request->user();
        $estate = app('estateManager');

        if($landlord->estate_manager_id != $estate->id ||!in_array($landlord->user_type_id, [1,2])){
            return response()->json(['message'=> 'You are not authorized to do this'], 403);
        }

        $kycs = Completedkyc::where('estate_manager_id', $estate->id)
                                ->with('tenant.documents', 'verifier', 'querier')
                                ->whereNull('verified_by')
                                ->whereNull('queried_by')
                                ->get();

        return response()->json($kycs);                    
    }

    public function view($slug, $type, $filename)
    {
        $path = "tenant/$type/$filename";

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $encrypted = Storage::disk('private')->get($path);
        $decrypted = Crypt::decrypt($encrypted);

        return response($decrypted, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function query(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if (
            $landlord->estate_manager_id != $estate->id ||
            !in_array($landlord->user_type_id, [1, 2])
        ) {
            return response()->json(['message' => 'You are not authorized to do this'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        try {
            DB::transaction(function () use ($uuid, $estate, $landlord, $validatedData, &$kyc) {

                $kyc = Completedkyc::where('uuid', $uuid)
                    ->where('estate_manager_id', $estate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $kyc->queried = 'yes';
                $kyc->queried_by = $landlord->id;
                $kyc->reason = $validatedData['reason'];
                $kyc->save();
            });

            return response()->json([
                'message' => 'Query sent successfully',
                'data' => $kyc
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong, try again',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $slug, $uuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if (
            $landlord->estate_manager_id != $estate->id ||
            !in_array($landlord->user_type_id, [1, 2])
        ) {
            return response()->json(['message' => 'You are not authorized to do this'], 403);
        }

        try {
            DB::transaction(function () use ($uuid, $estate, $landlord, &$kyc) {

                $kyc = Completedkyc::where('uuid', $uuid)
                    ->where('estate_manager_id', $estate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $kyc->verified = 'yes';
                $kyc->verified_by = $landlord->id;
                $kyc->queried = 'no';
                $kyc->queried_by = null;
                $kyc->reason = null;
                $kyc->save();

                $tenant = User::where('id', $kyc->tenant_id)
                    ->where('deactivated', 'yes')
                    ->lockForUpdate()
                    ->firstOrFail();

                $tenant->deactivated = 'no';
                $tenant->save();
            });

            return response()->json([
                'message' => 'KYC verified successfully',
                'data' => $kyc
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong, try again',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
