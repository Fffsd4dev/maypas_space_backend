<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationsMail;

use App\Models\{Apartment, MaintenanceRequest, Notification, ApartmentUnit, RentManager, LandlordAgent};

class MaintenanceRequestController extends Controller
{
    public function create(Request $request, $slug, $unitUuid)
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            // Validate request data
            $validator = Validator::make($request->all(), [
                'title'        => 'required|string|max:255',
                'description'  => 'required|string',
                'priority'     => 'in:low,medium,high',
                'attachment'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            $apartmentUnit = ApartmentUnit::where('uuid', $unitUuid)
                        ->select(['id', 'apartment_id','apartment_unit_name'])
                        ->firstOrFail();

            $checkComplainer = RentManager::where('occupant_id', $tenant->id)
                                ->where('apartment_unit_id', $apartmentUnit->id)
                                ->first();

            if(!$checkComplainer){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            $apartment = Apartment::where('id', $apartmentUnit->apartment_id)
                        ->select(['id','name','landlord_agent_id'])
                        ->firstOrFail();

            $validated['landlord_agent_id'] = $apartment->landlord_agent_id;

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $filename = time() .'_'.$tenant->uuid.'.'. $request->attachment->extension();
                $request->attachment->storeAs('tenant/maintenance/attachment', $filename, 'public');
                $validated['attachment'] = $filename;
            }

            $validated['apartment_id'] = $apartment->id;
            $validated['tenant_id'] = $tenant->id;
            $validated['estate_manager_id'] = $estate->id;

            $maintainanceRequest = MaintenanceRequest::create($validated);

            // Saving in notifications table
            $notificationData =[];

            $notificationData['type'] = 'maintenance';
            $notificationData['data'] = ['maintenance_requester_id'=>$tenant->id, 'message' => 'Sent a Maintenance request', 'maintainance_id' => $maintainanceRequest->id];
            $notificationData['apartment_id'] = $apartment->id;

            if($apartment->landlord_agent_id){
                $notificationData['for'] = $apartment->landlord_agent_id;

                $owner = LandlordAgent::where('estate_manager_id', $estate->id)
                            ->select(['id','first_name','last_name','email'])
                            ->firstOrFail($notificationData['for']);
            }else{
                $owner = LandlordAgent::where('user_type_id', 1)->where('estate_manager_id', $estate->id)->firstOrFail(); 
                $notificationData['for'] = $owner->id;
            }

            $notificationData['estate_manager_id'] = $estate->id;

            Notification::create($notificationData);
               
            // Notification modification Ended here

            $messageContent = [
                'body' => $owner->first_name.' '.$owner->last_name.' in '.$apartment->name.', '.$apartmentUnit->apartment_unit_name.' sent a maintenance request.',
                'estate_name' => $estate->estate_name,
                // 'tenant'   => $tenant->first_name.' '.$tenant->last_name,
                // 'apartment_name' => $apartment->name,
                // 'apartment_unit' => $apartmentUnit->apartment_unit_name,
                // 'type' => 'complaint',
            ];

            Mail::to($owner->email)->queue(
                new NotificationsMail($messageContent)
            );

            return response()->json([
                'message' => 'Maintenance request sent successfully',
                'data'    => $maintainanceRequest
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while sending this Request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $slug, $id)
    {
        try {
            $tenant = $request->user();

            $maintenance = MaintenanceRequest::where('id', $id)
                            ->where('tenant_id', $tenant->id)
                            ->first();

            $validator = Validator::make($request->all(), [
                'title'        => 'sometimes|string|max:255',
                'description'  => 'sometimes|string',
                'priority'     => 'sometimes|in:low,medium,high',
                'status'       => 'sometimes|in:open,in_progress,on_hold,resolved,closed',
                'attachment'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            if ($request->hasFile('attachment')) {
                // delete old file
                if ($maintenance->attachment && \Storage::disk('public')->exists('tenant/maintenance/attachment/'.$maintenance->attachment)) {
                    \Storage::disk('public')->delete('tenant/maintenance/attachment/'.$maintenance->attachment);
                }

                $filename = time() .'_'.$request->user()->uuid.'.'. $request->attachment->extension();
                $request->attachment->storeAs('tenant/maintenance/attachment', $filename, 'public');
                $validated['attachment'] = $filename;
            }

            $maintenance->update($validated);

            return response()->json([
                'message' => 'Maintenance request updated successfully',
                'data'    => $maintenance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update maintenance request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $slug, $id)
    {
        try {
            $loggedUser = $request->user();
            $estate = app('estateManager');

            $maintenance = MaintenanceRequest::select(['id', 'attachment'])
                            ->where('id', $id)
                            ->where('estate_manager_id', $estate->id)
                            ->firstOrFail();

            if($loggedUser->id != $maintenance->landlord_agent_id && $loggedUser->user_type_id != 1){
                return response()->json(['message' => 'You are not authorized'], 403);
            }

            
            if ($maintenance->attachment && \Storage::disk('public')->exists('tenant/maintenance/attachment/'.$maintenance->attachment)) {
                \Storage::disk('public')->delete('tenant/maintenance/attachment/'.$maintenance->attachment);
            }

            $maintenance->delete();

            return response()->json([
                'message' => 'Maintenance request deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete maintenance request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $slug, $id)
    {
        try {
                $loggedUser = $request->user();
                $estate = app('estateManager');

                $maintenance = MaintenanceRequest::query()
                    ->select(
                        'maintenance_requests.id as maintenance_id',
                        'maintenance_requests.title as maintenance_title',
                        'maintenance_requests.description as maintenance_description',
                        'maintenance_requests.status as maintenance_status',
                        'maintenance_requests.priority as maintenance_priority',
                        'maintenance_requests.attachment as maintenance_attachment',
                        'maintenance_requests.expected_visit_date as maintenance_expected_visit_date',
                        'users.id as tenant_id',
                        'users.first_name as tenant_first_name',
                        'users.last_name as tenant_last_name',
                        'apartments.address as apartment_address'
                    )
                    ->join('users', 'users.id', '=', 'maintenance_requests.tenant_id')
                    ->join('apartments', 'apartments.id', '=', 'maintenance_requests.apartment_id')
                    ->where('maintenance_requests.id', $id)
                    ->where(function ($query) use ($loggedUser, $estate) {
                        if (!$loggedUser->user_type_id) {
                            $query->where('maintenance_requests.tenant_id', $loggedUser->id);
                        } else {
                            if ($loggedUser->user_type_id != 1) {
                                $query->where('maintenance_requests.landlord_agent_id', $loggedUser->id);
                            } else {
                                $query->where('maintenance_requests.estate_manager_id', $estate->id);
                            }
                        }
                    })
                    ->firstOrFail();

                //Paginate the logs separately
                $logs = DB::table('maintenance_logs')
                    ->select(
                        'maintenance_logs.id',
                        'maintenance_logs.log_message',
                        'maintenance_logs.status_update as status',
                        'maintenance_logs.visit_date',
                        'maintenance_logs.next_expected_visit_date',
                        'maintenance_logs.created_at',
                        'technicians.id as technician_id',
                        'technicians.name as technician_name',
                        'technicians.phone as technician_phone'
                    )
                    ->leftJoin('technicians', 'technicians.id', '=', 'maintenance_logs.technician_id')
                    ->where('maintenance_logs.maintenance_id', $id)
                    ->orderBy('maintenance_logs.created_at', 'desc')
                    ->paginate(10);

                return response()->json([
                    'maintenance' => $maintenance,
                    'logs' => $logs
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Maintenance request not found.',
                'error'   => $e->getMessage()
            ], 404);
        }
    }

    public function index(Request $request)
    {
        try {
                $loggedUser = $request->user();
                $estate = app('estateManager');


            $perPage = 10; // maintenance per page
            $logsPerPage = 5; // logs per maintenance request

            //  Paginate maintenance requests
            $raw = MaintenanceRequest::query()
                ->select(
                    'maintenance_requests.id as maintenance_id',
                    'maintenance_requests.title as maintenance_title',
                    'maintenance_requests.description as maintenance_description',
                    'maintenance_requests.status as maintenance_status',
                    'maintenance_requests.priority as maintenance_priority',
                    'maintenance_requests.attachment as maintenance_attachment',
                    'maintenance_requests.expected_visit_date as maintenance_expected_visit_date',
                    'users.id as tenant_id',
                    'users.first_name as tenant_first_name',
                    'users.last_name as tenant_last_name',
                    'apartments.address as apartment_address'
                )
                ->join('users', 'users.id', '=', 'maintenance_requests.tenant_id')
                ->join('apartments', 'apartments.id', '=', 'maintenance_requests.apartment_id')
                ->where(function($query) use ($loggedUser, $estate) {
                    if(!$loggedUser->user_type_id){
                        $query->where('maintenance_requests.tenant_id', $loggedUser->id);
                    } else {
                        if($loggedUser->user_type_id != 1){
                            $query->where('maintenance_requests.landlord_agent_id', $loggedUser->id);
                        } else {
                            $query->where('maintenance_requests.estate_manager_id', $estate->id);
                        }
                    }
                })
                ->paginate($perPage);

            //Map results & add paginated logs
            $maintenance = $raw->getCollection()->map(function($item) use ($logsPerPage) {
                // paginate logs for this maintenance
                $logs = DB::table('maintenance_logs')
                    ->select(
                        'maintenance_logs.log_message',
                        'maintenance_logs.status_update as status',
                        'maintenance_logs.visit_date',
                        'maintenance_logs.next_expected_visit_date',
                        'maintenance_logs.created_at as log_created_at',
                        'technicians.name as technician_name',
                        'technicians.phone as technician_phone'
                    )
                    ->leftJoin('technicians', 'technicians.id', '=', 'maintenance_logs.technician_id')
                    ->where('maintenance_logs.maintenance_id', $item->maintenance_id)
                    ->orderBy('maintenance_logs.created_at', 'desc')
                    ->paginate($logsPerPage);

                return [
                    'maintenance_id' => $item->maintenance_id,
                    'maintenance_title' => $item->maintenance_title,
                    'maintenance_description' => $item->maintenance_description,
                    'maintenance_status' => $item->maintenance_status,
                    'maintenance_priority' => $item->maintenance_priority,
                    'maintenance_attachment' => $item->maintenance_attachment,
                    'maintenance_expected_visit_date' => $item->maintenance_expected_visit_date,
                    'maintenance_requester_id' => $item->tenant_id,
                    'maintenance_requester_first_name' => $item->tenant_first_name,
                    'maintenance_requester_last_name' => $item->tenant_last_name,
                    'maintenance_address' => $item->apartment_address,
                    'maintenance_logs' => $logs
                ];
            });

            //Replace paginator collection with transformed data
            $paginatedMaintenance = new \Illuminate\Pagination\LengthAwarePaginator(
                $maintenance,
                $raw->total(),
                $raw->perPage(),
                $raw->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return response()->json([
                'data' => $paginatedMaintenance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Maintenance request not found.',
                'error'   => $e->getMessage()
            ], 404);
        }
    }

    public function statusUpdate(Request $request, $slug, $id)
    {
        try {
            $landlord = $request->user();
            $estate = app('estateManager');

            if($landlord->user_type_id == 1){
                $maintenance = MaintenanceRequest::where('id', $id)
                                ->with('tenant:id,first_name,last_name,email')
                                ->firstOrFail();  
            }else{
                $maintenance = MaintenanceRequest::where('id', $id)
                            ->where('landlord_agent_id', $landlord->id)
                            ->with('tenant:id,first_name,last_name,email')
                            ->firstOrFail();
            }

            $validator = Validator::make($request->all(), [
                'status'       => 'required|in:open,in_progress,on_hold,resolved,closed',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            $maintenance->update($validated);

            $messageContent = [
                'name' => $maintenance->tenant->first_name.' '.$maintenance->tenant->last_name,
                'body' => 'Your maintenance status has been updated',
                'estate_name' => $estate->estate_name,
            ];

            Mail::to($maintenance->tenant->email)->queue(
                new NotificationsMail($messageContent)
            );

            return response()->json([
                'message' => 'Maintenance request status updated successfully',
                'data'    => $maintenance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update maintenance request.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
