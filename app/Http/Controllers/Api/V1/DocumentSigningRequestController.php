<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\SendDocumentSigningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendDocumentSigningRequestMail;
use Illuminate\Support\Facades\DB;

use App\Models\{UnsignedDocument,
    User,
    DocumentSigningRequest,
    RentManager};

class DocumentSigningRequestController extends Controller
{
    public function store(Request $request)
    {
        $estate = app('estateManager');

        $validator = Validator::make($request->all(), [
            'document_uuid' => 'required|string|exists:unsigned_documents,uuid',
            'user_uuid'     => 'required|string|exists:users,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            // Resolve unsigned document
            $unsignedDocument = UnsignedDocument::where('uuid', $validated['document_uuid'])
                ->where('estate_manager_id', $estate->id)
                ->select(['id', 'uuid', 'name', 'apartment_id'])
                ->firstOrFail();

            // Resolve tenant
            $tenant = User::where('uuid', $validated['user_uuid'])
                ->where('estate_manager_id', $estate->id)
                ->select(['id', 'uuid', 'first_name', 'last_name', 'email'])
                ->firstOrFail();

            $checkTenant = RentManager::where('occupant_id', $tenant->id)
                            ->where('estate_manager_id', $estate->id)
                            ->whereHas('apartmentUnit', function ($query)use ($unsignedDocument) {
                                $query->where('apartment_id', $unsignedDocument->apartment_id);
                            })
                            ->first();

            if(!$checkTenant){
                return response()->json(['message' => 'This tenant is not allocated to this apartment'], 403);
            }


            // Create signing request
            $signingRequest = DocumentSigningRequest::create([
                'document_id'       => $unsignedDocument->id,
                'tenant_id'         => $tenant->id,
                'estate_manager_id' => $estate->id,
            ]);

            DB::commit();

            // Send email AFTER successful commit
            $messageContent = [
                'estate_name' => $estate->estate_name,
                'form_name'   => $unsignedDocument->name,
                'name'        => $tenant->first_name . ' ' . $tenant->last_name,
            ];

            Mail::to($tenant->email)
                ->send(new SendDocumentSigningRequestMail($messageContent));

            return response()->json([
                'message' => 'Document sent to tenant successfully',
                'estate'  => $estate->estate_name,
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' => 'Something went wrong while sending the document'
            ], 500);
        }
    }

    public function tenantViewAllUnsigned(Request $request){
        $tenant = $request->user();

        $estate = app('estateManager');

        $documents = DocumentSigningRequest::where('tenant_id', $tenant->id)
                        ->where('estate_manager_id', $estate->id)
                        ->where('signed', 'no')
                        ->select(['id','uuid','signed', 'document_id', 'tenant_id', 'estate_manager_id'])
                        ->with('document:id,uuid,name,filename')
                        ->with('tenant:id,uuid,first_name,last_name')
                        ->latest()
                        ->paginate(10);

        return response()->json([
            'message' => 'fetched all unsigned documents successfully',
            'data' => $documents
        ], 200);


    }

    public function tenantViewOneUnsigned(Request $request, $slug, string $uuid){
        $estate = app('estateManager');

        $tenant = $request->user();

        $document = DocumentSigningRequest::where('tenant_id', $tenant->id)
                        ->where('estate_manager_id', $estate->id)
                        ->where('signed', 'no')
                        ->where('uuid', $uuid)
                        ->select(['id', 'signed', 'document_id', 'tenant_id', 'estate_manager_id'])
                        ->with('document:id,uuid,name,filename')
                        ->with('tenant:id,uuid,first_name,last_name')
                        ->firstOrFail();

        return response()->json($document);
    }

    public function viewAllUnsigned(){

        $estate = app('estateManager');

        $documents = DocumentSigningRequest::where('estate_manager_id', $estate->id)                        
                        ->where('signed', 'no')
                        ->select(['id','uuid','signed', 'document_id', 'tenant_id', 'estate_manager_id'])
                        ->with('document:id,uuid,name,filename')
                        ->with('tenant:id,uuid,first_name,last_name')
                        ->latest()
                        ->paginate(10);

        return response()->json([
            'message' => 'fetched all unsigned documents successfully',
            'data' => $documents
        ], 200);


    }

    public function viewOneUnsigned($slug, string $uuid){
        $estate = app('estateManager');

        $document = DocumentSigningRequest::where('estate_manager_id', $estate->id)                        
                        ->where('signed', 'no')
                        ->where('uuid', $uuid)
                        ->select(['id', 'signed', 'document_id', 'tenant_id', 'estate_manager_id'])
                        ->with('document:id,uuid,name,filename')
                        ->with('tenant:id,uuid,first_name,last_name')
                        ->firstOrFail();

        return response()->json($document);
    }

}
