<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationsMail;

use App\Models\{MaintenanceLog, MaintenanceRequest, Apartment};

class MaintenanceLogController extends Controller
{
    public function create(Request $request, $slug, $id)
    {
        try {
            $landlord = $request->user();
            $estate = app('estateManager');


            // Get related maintenance request and apartment
            $maintenanceRequest = MaintenanceRequest::select(['id', 'apartment_id', 'landlord_agent_id','tenant_id'])
                                    ->with('tenant:id,first_name,last_name,email')
                                    ->find($id);

            if($landlord->id != $maintenanceRequest->landlord_agent_id && $landlord->user_type_id != 1){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'technician_id'  => 'nullable|exists:technicians,id',
                'log_message'  => 'required|string',
                'status_update' => 'required|in:open,in_progress,on_hold,resolved,closed',
                'visit_date' => 'nullable|date|date_format:Y-m-d',
                'next_expected_visit_date' => 'nullable|date|date_format:Y-m-d|after:visit_date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            $validated['estate_manager_id'] = $estate->id;
            $validated['maintenance_id'] = $id;

            $maintenanceLog = MaintenanceLog::create($validated);

            $messageContent = [
                'name' => $maintenanceRequest->tenant->first_name.' '.$maintenanceRequest->tenant->last_name,
                'body' => 'You just received a response to your maintenance request',
                'estate_name' => $estate->estate_name,
            ];

            Mail::to($maintenanceRequest->tenant->email)->queue(
                new NotificationsMail($messageContent)
            );

            return response()->json([
                'message' => 'Maintenance log sent',
                'data'    => $maintenanceLog,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while logging a response.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $slug, $logId)
    {
        try {
            $landlord = $request->user();
            $estate   = app('estateManager');

            $maintenanceLog = MaintenanceLog::findOrFail($logId);

            // Get related maintenance request and apartment
            $maintenanceRequest = MaintenanceRequest::select(['id', 'apartment_id', 'landlord_agent_id'])
                ->findOrFail($maintenanceLog->maintenance_id);

            $apartment = Apartment::select(['id', 'landlord_agent_id'])
                ->findOrFail($maintenanceRequest->apartment_id);

            // Authorization: only estate manager or assigned landlord/agent can update
            if ($landlord->user_type_id != 1 && $landlord->id != $apartment->landlord_agent_id) {
                return response()->json(['message' => 'You are not Authorized'], 403);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'technician_id'  => 'nullable|exists:technicians,id',
                'log_message'    => 'sometimes|required|string',
                'status_update'  => 'sometimes|required|in:open,in_progress,on_hold,resolved,closed',
                'visit_date'     => 'nullable|date|date_format:Y-m-d',
                'next_expected_visit_date' => 'nullable|date|date_format:Y-m-d|after:visit_date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
            $maintenanceLog->update($validated);

            return response()->json([
                'message' => 'Maintenance log updated successfully',
                'data'    => $maintenanceLog,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while updating the log.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(Request $request, $slug, $id)
    {
        try {
            $landlord = $request->user();
            $estate   = app('estateManager');

            $maintenanceLog = MaintenanceLog::findOrFail($id);

            // Get related maintenance request and apartment
            $maintenanceRequest = MaintenanceRequest::select(['id', 'apartment_id', 'landlord_agent_id'])
                ->findOrFail($maintenanceLog->maintenance_id);

            $apartment = Apartment::select(['id', 'landlord_agent_id'])
                ->findOrFail($maintenanceRequest->apartment_id);

            // Authorization: only estate manager or assigned landlord/agent can delete
            if ($landlord->user_type_id != 1 && $landlord->id != $apartment->landlord_agent_id) {
                return response()->json(['message' => 'You are not Authorized'], 403);
            }

            $maintenanceLog->delete();

            return response()->json([
                'message' => 'Maintenance log deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while deleting the log.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
