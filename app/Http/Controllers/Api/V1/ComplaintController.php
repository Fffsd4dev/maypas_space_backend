<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationsMail;

use App\Models\{Complaint, ApartmentUnit, Apartment, Notification, LandlordAgent, RentManager};

class ComplaintController extends Controller
{
    public function create(Request $request, $slug, $unitUuid)
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            // Validate request data
            $validator = Validator::make($request->all(), [
                //'category_id'  => 'required|exists:complaint_categories,id',
                'title'        => 'required|string|max:255',
                'description'  => 'required|string',
                'priority'     => 'in:low,medium,high,urgent',
                'evidence'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            $apartmentUnit = ApartmentUnit::where('uuid', $unitUuid)
                        ->select(['id', 'apartment_id', 'apartment_unit_name'])
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

            // Handle file upload
            if ($request->hasFile('evidence')) {
                $filename = time() .'_'.$tenant->uuid.'.'. $request->evidence->extension();
                $request->evidence->storeAs('tenant/complaints/evidence', $filename, 'public');
                $validated['evidence'] = $filename;
            }

            if(!$apartment->landlord_agent_id){
                $land = LandlordAgent::where('estate_manager_id', $estate->id)
                            ->select(['id','first_name','last_name','email'])
                            ->where('user_type_id', 1)
                            ->firstOrFail();

                $landlord = $land->id;
            }else{
                $landlord = $apartment->landlord_agent_id;

                $land = LandlordAgent::where('estate_manager_id', $estate->id)
                            ->select(['id','first_name','last_name','email'])
                            ->firstOrFail($landlord);
            }

            $validated['apartment_id'] = $apartmentUnit->id;
            $validated['tenant_id'] = $tenant->id;
            $validated['landlord_agent_id'] = $landlord;
            $validated['estate_manager_id'] = $estate->id;

            $complaint = Complaint::create($validated);

            // Saving in notifications table
            $notificationData =[];

            $notificationData['type'] = 'complaint';
            $notificationData['data'] = ['complainer_id'=>$tenant->id, 'message' => 'Sent a complain', 'complain_id' => $complaint->id];
            $notificationData['apartment_id'] = $apartmentUnit->id;
            $notificationData['for'] = $landlord;
            $notificationData['estate_manager_id'] = $estate->id;

            Notification::create($notificationData);
               
            // Notification modification Ended here 

            $messageContent = [
                'body' => $land->first_name.' '.$land->last_name.' in '.$apartment->name.', '.$apartmentUnit->apartment_unit_name.' sent a complaint request.',
                'estate_name' => $estate->estate_name,
                // 'tenant'   => $tenant->first_name.' '.$tenant->last_name,
                // 'apartment_name' => $apartment->name,
                // 'apartment_unit' => $apartmentUnit->apartment_unit_name,
                // 'type' => 'complaint',
            ];

            Mail::to($land->email)->queue(
                new NotificationsMail($messageContent)
            );

            return response()->json([
                'message' => 'Complaint filed successfully',
                'data'    => $complaint
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while filing the complaint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $slug, $id)
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            $complaint = Complaint::where('id', $id)
                ->where('tenant_id', $tenant->id)
                ->where('estate_manager_id', $estate->id)
                ->firstOrFail();

            // Validate request data
            $validator = Validator::make($request->all(), [
                'title'        => 'sometimes|string|max:255',
                'description'  => 'sometimes|string',
                'priority'     => 'sometimes|in:low,medium,high',
                'evidence'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            // Handle file upload
            if ($request->hasFile('evidence')) {
                // delete old file
                if ($complaint->evidence && \Storage::disk('public')->exists('tenant/complaints/evidence/'.$complaint->evidence)) {
                    \Storage::disk('public')->delete('tenant/complaints/evidence/'.$complaint->evidence);
                }

                $filename = time() .'_'.$tenant->uuid.'.'. $request->evidence->extension();
                $request->evidence->storeAs('tenant/complaints/evidence', $filename, 'public');
                $validated['evidence'] = $filename;
            }

            $complaint->update($validated);

            return response()->json([
                'message' => 'Complaint updated successfully',
                'data'    => $complaint
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while updating the complaint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $slug, $id)
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            $complaint = Complaint::where('id', $id)
                ->where('tenant_id', $tenant->id)
                ->where('estate_manager_id', $estate->id)
                ->firstOrFail();

            // Delete attachment file if exists
            if ($complaint->evidence && \Storage::disk('public')->exists('tenant/complaints/evidence/'.$complaint->evidence)) {
                \Storage::disk('public')->delete('tenant/complaints/evidence/'.$complaint->evidence);
            }

            $complaint->delete();

            return response()->json([
                'message' => 'Complaint deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while deleting the complaint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request) 
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            $complaints = DB::table('complaints')
                ->join('apartment_units', 'complaints.apartment_id', '=', 'apartment_units.id')
                ->leftJoin('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
                ->leftJoin('apartment_categories', 'apartments.category_id', '=', 'apartment_categories.id')
                ->select(
                    'complaints.id',
                    'complaints.title',
                    'complaints.description',
                    'complaints.priority',
                    'complaints.evidence',
                    'complaints.status',
                    'complaints.resolution_notes',
                    'complaints.created_at',
                    'complaints.updated_at',
                    'apartment_units.apartment_id',
                    'apartment_units.apartment_unit_name as unit_name',
                    'apartments.number_item',
                    'apartments.category_id',
                    'apartments.location',
                    'apartments.address',
                    'apartment_categories.name as apartment_category_name',
                    'apartment_categories.description as apartment_category_description'
                )
                ->where('complaints.tenant_id', $tenant->id)
                ->where('complaints.estate_manager_id', $estate->id)
                ->orderBy('complaints.created_at', 'desc')
                ->get();


            // Transform into nested structure
            $complaints = $complaints->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                    'priority'    => $item->priority,
                    'evidence'  => $item->evidence,
                    'status' => $item->status,
                    'resolution_notes'  => $item->resolution_notes,
                    'created_at'  => $item->created_at,
                    'updated_at'  => $item->updated_at,
                    'apartment' => [
                        'number_item' => $item->number_item,
                        'category_id' => $item->category_id,
                        'location'    => $item->location,
                        'address'     => $item->address,
                        'apartment_category' => [
                            'name'        => $item->apartment_category_name,
                            'description' => $item->apartment_category_description,
                        ],
                        'apartment_unit' => [
                            'name' => $item->unit_name
                        ]
                    ]
                ];
            });

            return response()->json([
                'message' => 'Complaints retrieved successfully',
                'data'    => $complaints
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while fetching complaints.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function viewOne(Request $request, $slug, $id)
    {
        try {
            $tenant = $request->user();
            $estate = app('estateManager');

            $complaint = DB::table('complaints')
            ->join('apartment_units', 'complaints.apartment_id', '=', 'apartment_units.id')
            ->leftJoin('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
            ->leftJoin('apartment_categories', 'apartments.category_id', '=', 'apartment_categories.id')
            ->leftJoin('complaint_responses', 'complaints.id', '=', 'complaint_responses.complaint_id') // join responses
            ->select(
                'complaints.id',
                'complaints.title',
                'complaints.description',
                'complaints.priority',
                'complaints.evidence',
                'complaints.status',
                'complaints.resolution_notes',
                'complaints.created_at',
                'complaints.updated_at',

                // apartment
                'apartments.number_item',
                'apartments.category_id',
                'apartments.location',
                'apartments.address',

                // apartment category
                'apartment_categories.name as apartment_category_name',
                'apartment_categories.description as apartment_category_description',

                // responses
                'complaint_responses.id as response_id',
                'complaint_responses.message as response_message',
                'complaint_responses.attachment as response_attachment',
                'complaint_responses.created_at as response_created_at'
            )
            ->where('complaints.tenant_id', $tenant->id)
            ->where('complaints.id', $id)
            ->where('complaints.estate_manager_id', $estate->id)
            ->orderBy('complaints.created_at', 'desc')
            ->get()
            ->groupBy('id') // group by complaint id
            ->map(function ($rows) {
                $complaint = $rows->first();

                return [
                    'id'          => $complaint->id,
                    'title'       => $complaint->title,
                    'description' => $complaint->description,
                    'priority'    => $complaint->priority,
                    'evidence'  => $complaint->evidence,
                    'status' => $complaint->status,
                    'resolution_notes'  => $complaint->resolution_notes,
                    'created_at'  => $complaint->created_at,
                    'updated_at'  => $complaint->updated_at,
                    'apartment' => [
                        'number_item' => $complaint->number_item,
                        'category_id' => $complaint->category_id,
                        'location'    => $complaint->location,
                        'address'     => $complaint->address,
                        'apartment_category' => [
                            'name'        => $complaint->apartment_category_name,
                            'description' => $complaint->apartment_category_description,
                        ],
                    ],

                    // collect all complaint_responses
                    'complaint_responses' => $rows->map(function ($r) {
                        return [
                            'id'         => $r->response_id,
                            'message'    => $r->response_message,
                            'attachment' => $r->response_attachment,
                            'created_at' => $r->response_created_at,
                        ];
                    })->filter(fn($r) => !is_null($r['id']))->values(),
                ];
            })
            ->first();


            return response()->json([
                'message' => 'Complaint retrieved successfully',
                'data'    => $complaint
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while fetching complaints.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function statusUpdate(Request $request, $slug, $id)
    {
        try {
            $estate = app('estateManager');

            $complaint = Complaint::where('id', $id)
                ->where('estate_manager_id', $estate->id)
                ->with('tenant:id,first_name,last_name,email')
                ->firstOrFail();

            // Validate request data
            $validator = Validator::make($request->all(), [
                'status'     => 'required|in:open,under_review,resolved,closed',
                'resolution_notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();            

            $complaint->update($validated);

            $messageContent = [
                'name' => $complaint->tenant->first_name.' '.$complaint->tenant->last_name,
                'body' =>'Your complaint status has been updated',
                'estate_name' => $estate->estate_name,
            ];

            Mail::to($complaint->tenant->email)->queue(
                new NotificationsMail($messageContent)
            );

            return response()->json([
                'message' => 'Status updated successfully',
                'data'    => $complaint
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while updating the complaint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function landlordDestroy($slug, $id)
    {
        try {
            $estate = app('estateManager');

            $complaint = Complaint::where('id', $id)
                ->where('estate_manager_id', $estate->id)
                ->firstOrFail();

            // Delete attachment file if exists
            if ($complaint->attachment && \Storage::disk('public')->exists('attachments/'.$complaint->attachment)) {
                \Storage::disk('public')->delete('attachments/'.$complaint->attachment);
            }

            $complaint->delete();

            return response()->json([
                'message' => 'Complaint deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while deleting the complaint.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function landlordIndex(Request $request)
    {
        try {
            $estate = app('estateManager');
            $loggedUser = $request->user();

            $complaints = DB::table('complaints')
            ->join('apartment_units', 'complaints.apartment_id', '=', 'apartment_units.id')
            ->leftJoin('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
            ->leftJoin('apartment_categories', 'apartments.category_id', '=', 'apartment_categories.id')
            ->select(
                'complaints.id',
                'complaints.title',
                'complaints.description',
                'complaints.priority',
                'complaints.evidence',
                'complaints.status',
                'complaints.resolution_notes',
                'complaints.created_at',
                'complaints.updated_at',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name as unit_name',
                'apartments.number_item',
                'apartments.category_id',
                'apartments.location',
                'apartments.address',
                'apartment_categories.name as apartment_category_name',
                'apartment_categories.description as apartment_category_description'
            )
            ->where(function($query) use ($loggedUser, $estate) {
                if ($loggedUser->user_type_id != 1) {
                    $query->where('complaints.landlord_agent_id', $loggedUser->id);
                } else {
                    $query->where('complaints.estate_manager_id', $estate->id);
                }
            })
            ->orderBy('complaints.created_at', 'desc')
            ->paginate(10)
            ->through(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                    'priority'    => $item->priority,
                    'evidence'    => $item->evidence,
                    'status' => $item->status,
                    'resolution_notes' => $item->resolution_notes,
                    'created_at'  => $item->created_at,
                    'updated_at'  => $item->updated_at,
                    'apartment' => [
                        'number_item' => $item->number_item,
                        'category_id' => $item->category_id,
                        'location'    => $item->location,
                        'address'     => $item->address,
                        'apartment_category' => [
                            'name'        => $item->apartment_category_name,
                            'description' => $item->apartment_category_description,
                        ],
                        'apartment_unit' => [
                            'name' => $item->unit_name
                        ]
                    ]
                ];
            });


            return response()->json([
                'message' => 'Complaints retrieved successfully',
                'data'    => $complaints
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while fetching complaints.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function landlordViewOne(Request $request, $slug, $id)
    {
        try {
            $estate = app('estateManager');
            $loggedUser = $request->user();

            $complaint = DB::table('complaints')
            ->join('apartment_units', 'complaints.apartment_id', '=', 'apartment_units.id')
            ->leftJoin('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
            ->leftJoin('apartment_categories', 'apartments.category_id', '=', 'apartment_categories.id')
            ->leftJoin('complaint_responses', 'complaints.id', '=', 'complaint_responses.complaint_id') // join responses
            ->select(
                'complaints.id',
                'complaints.title',
                'complaints.description',
                'complaints.priority',
                'complaints.evidence',
                'complaints.status',
                'complaints.resolution_notes',
                'complaints.created_at',
                'complaints.updated_at',

                // apartment
                'apartments.number_item',
                'apartments.category_id',
                'apartments.location',
                'apartments.address',

                // apartment category
                'apartment_categories.name as apartment_category_name',
                'apartment_categories.description as apartment_category_description',

                // responses
                'complaint_responses.id as response_id',
                'complaint_responses.message as response_message',
                'complaint_responses.attachment as response_attachment',
                'complaint_responses.created_at as response_created_at'
            )
            ->where('complaints.id', $id)
            // ->where('complaints.estate_manager_id', $estate->id)
            ->where(function ($query) use ($loggedUser, $estate) {
                if ($loggedUser->user_type_id != 1) {
                        $query->where('complaints.landlord_agent_id', $loggedUser->id);
                } else {
                    $query->where('complaints.estate_manager_id', $estate->id);
                }
            })
            ->orderBy('complaints.created_at', 'desc')
            ->get()
            ->groupBy('id') // group by complaint id
            ->map(function ($rows) {
                $complaint = $rows->first();

                return [
                    'id'          => $complaint->id,
                    'title'       => $complaint->title,
                    'description' => $complaint->description,
                    'priority'    => $complaint->priority,
                    'evidence'  => $complaint->evidence,
                    'status' => $complaint->status,
                    'resolution_notes'  => $complaint->resolution_notes,
                    'created_at'  => $complaint->created_at,
                    'updated_at'  => $complaint->updated_at,
                    'apartment' => [
                        'number_item' => $complaint->number_item,
                        'category_id' => $complaint->category_id,
                        'location'    => $complaint->location,
                        'address'     => $complaint->address,
                        'apartment_category' => [
                            'name'        => $complaint->apartment_category_name,
                            'description' => $complaint->apartment_category_description,
                        ],
                    ],

                    // collect all complaint_responses
                    'complaint_responses' => $rows->map(function ($r) {
                        return [
                            'id'         => $r->response_id,
                            'message'    => $r->response_message,
                            'attachment' => $r->response_attachment,
                            'created_at' => $r->response_created_at,
                        ];
                    })->filter(fn($r) => !is_null($r['id']))->values(),
                ];
            })
            ->first();

            return response()->json([
                'message' => 'Complaint retrieved successfully',
                'data'    => $complaint
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while fetching complaints.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
