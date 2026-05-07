<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\{Document, TenantDocument, ApartmentUnit, Notification, User};

class TenantDocumentController extends Controller
{
    public function send(Request $request, $slug, $apartmentUuid, $tenantUuid)
    {
        $landlord = $request->user();
        $estate = app('estateManager');

        if ($landlord->user_type_id !== 1 || $landlord->estate_manager_id !== $estate->id) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

        $checkApartment = ApartmentUnit::select('id')
            ->where('uuid', $apartmentUuid)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$checkApartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        $checkTenant = User::select('id')
            ->where('uuid', $tenantUuid)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$checkTenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'document' => 'required|numeric|gte:1|exists:documents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $validated['for'] = $checkTenant->id;
        $validated['apartment_id'] = $checkApartment->id;
        $validated['landlord_agent_id'] = $landlord->id;
        $validated['estate_manager_id'] = $estate->id;

        DB::beginTransaction();

        try {
            // Create tenant document
            $tenantDocument = TenantDocument::create($validated);

            // Create notification
            $notificationData = [
                'type' => 'document',
                'data' => [
                    'landlord' => $landlord->id,
                    'message' => 'Sent a Document'
                ],
                'apartment_id' => $checkApartment->id,
                'for' => $checkTenant->id,
                'estate_manager_id' => $estate->id,
            ];

            Notification::create($notificationData);

            DB::commit();

            return response()->json([
                'message' => 'Document sent successfully',
                'data' => $tenantDocument
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request, $slug, $apartmentUuid){
        $tenant = $request->user();
        $estate = app('estateManager');  
        
        $checkApartment = ApartmentUnit::select('id')
            ->where('uuid', $apartmentUuid)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$checkApartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        $checkTenant = User::select('id')
            ->where('id', $tenant->id)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$checkTenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $documents = DB::table('tenant_documents')
        ->join('documents', 'tenant_documents.document', '=', 'documents.id')
        ->select(
            'tenant_documents.uuid',
            'tenant_documents.status',
            'tenant_documents.signed_document',
            'tenant_documents.submitted_at',
            'documents.name',
            'document.form_json'
        )
        ->where('tenant_document.apartment_id', $checkApartment->id)
        ->where('tenant_document.for', $tenant->id)
        ->where('estate_manager_id', $estate->id)
        ->get();

        if(!$documents){
            return response()->json(['message' => 'No record found'], 404);
        }

        return response()->json(['message' => 'Fetch Successful'], 200);
    }

    public function show(Request $request, $slug, $documentUuid){
        $tenant = $request->user();
        $estate = app('estateManager');  

        $checkTenant = User::select('id')
            ->where('id', $tenant->id)
            ->where('estate_manager_id', $estate->id)
            ->first();

        if (!$checkTenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $document = DB::table('tenant_documents')
        ->join('documents', 'tenant_documents.document', '=', 'documents.id')
        ->select(
            'tenant_documents.uuid',
            'tenant_documents.status',
            'tenant_documents.signed_document',
            'tenant_documents.submitted_at',
            'documents.name',
            'document.form_json'
        )
        ->where('tenant_document.uuid', $documentUuid)
        ->where('tenant_document.for', $tenant->id)
        ->where('estate_manager_id', $estate->id)
        ->first();

        if(!$document){
            return response()->json(['message' => 'No record found'], 404);
        }

        return response()->json(['message' => 'Fetch Successful', 'data' => $document], 200);
    }

    public function submitSigned(Request $request, $slug, $tenantDocumentUuid){
        $tenant = $request->user();
        $estate = app('estateManager');

        $document = TenantDocument::where('uuid', $tenantDocumentUuid)
                    ->where('for', $tenant->id)
                    ->where('estate_manager_id', $estate->id)
                    ->first();
        
        if($tenant->id != $document->for){
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'signed_document_json' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $document->signed_document_json = $validated['signed_document_json'];
        $document->submitted_at = now();
        $document->status = 'complete';

        $response = $document->save();

        if(!$response){
            return response()->json(['message' => 'Update failed'], 500);
        }

        return response()->json(['message' => 'Updated successfully', 'data' => $document], 200);
    }
}
