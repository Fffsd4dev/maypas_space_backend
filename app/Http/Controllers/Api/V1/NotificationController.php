<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\{User, Notification, Complaint, MaintenanceRequest};
class NotificationController extends Controller
{
    public function index(Request $request){
        $admin = $request->user();
        $estate = app('estateManager');

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.for', $admin->id)
            ->where('notifications.estate_manager_id', $estate->id)
            ->orderBy('notifications.created_at', 'desc')
        ->paginate(15);

        $notifications = $raw->map(function($item){
            if($item->type === 'complaint'){
                return $this->getComplaintNotifications($item);       
            }else{
                return $this->getMaintenanceNotifications($item); 
            }
            
        });

        return response()->json($notifications);
    }

    public function viewOne(Request $request, $slug, $id){
        $admin = $request->user();
        $estate = app('estateManager');

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.for', $admin->id)
            ->where('notifications.id', $id)
            ->where('notifications.estate_manager_id', $estate->id)
        ->first();

        if ($raw->is_read === "no") {
            DB::table('notifications')
                ->where('id', $raw->id)
                ->update(['is_read' => 'yes']);
            $raw->is_read = "yes"; // update the in-memory object too
        }
        
        if($raw->type === 'complaint'){
            $notification = $this->getComplaintNotification($raw);
        }else{
            $notification = $this->getMaintenanceNotification($raw);
        }
      

        return response()->json($notification);
    }

    public function unread(Request $request){
        $admin = $request->user();
        $estate = app('estateManager');

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.for', $admin->id)
            ->where('notifications.estate_manager_id', $estate->id)
            ->where('is_read', 'no')
            ->orderBy('notifications.created_at', 'desc')
        ->paginate(15);

        $notifications = $raw->map(function($item){
            if($item->type === 'complaint'){
                return $this->getComplaintNotifications($item);       
            }else{
                return $this->getMaintenanceNotifications($item); 
            }
            
        });

        return response()->json($notifications);
    }

    public function landlordViewOne(Request $request, $slug, $id){
        $admin = $request->user();

        if($admin->user_type_id != 1){
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.id', $id)
            ->where('notifications.estate_manager_id', $admin->estate_manager_id)
        ->first();

        if($raw->type === 'complaint'){
            $notification = $this->getComplaintNotification($raw);
        }else{
            $notification = $this->getMaintenanceNotification($raw);
        }
    
        return response()->json($notification);
    }

    public function landlordUnread(Request $request){
        $admin = $request->user();

        if($admin->user_type_id != 1){
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.estate_manager_id', $admin->estate_manager_id)
            ->where('is_read', 'no')
            ->orderBy('notifications.created_at', 'desc')
        ->paginate(15);

        $notifications = $raw->map(function($item){
            if($item->type === 'complaint'){
                return $this->getComplaintNotifications($item);       
            }else{
                return $this->getMaintenanceNotifications($item); 
            }
            
        });

        return response()->json($notifications);
    }

    public function landlordRead(Request $request){
        $admin = $request->user();

        if($admin->user_type_id != 1){
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        $raw = DB::table('notifications')
            ->join('apartment_units', 'notifications.apartment_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_id',
                'apartment_units.apartment_unit_name',

                // Apartment fields
                'apartments.uuid as apartment_uuid',
                'apartments.address',
                'apartments.location'
            )
            ->where('notifications.estate_manager_id', $admin->estate_manager_id)
            ->where('is_read', 'yes')
            ->orderBy('notifications.created_at', 'desc')
        ->paginate(15);

        $notifications = $raw->map(function($item){
            if($item->type === 'complaint'){
                return $this->getComplaintNotifications($item);       
            }else{
                return $this->getMaintenanceNotifications($item); 
            }
            
        });

        return response()->json($notifications);
    }

    private function getComplaintNotifications($item){
        $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

        $complainer = User::where('id', $data['complainer_id'])
                        ->select(['id', 'uuid', 'first_name', 'last_name'])
                        ->first();
        return [
            'id' => $item->id,
            'type' => $item->type,
            'is_read' => $item->is_read,
            'apartment' => [
                'location' => $item->location,
                'address' => $item->address,
                'uuid' => $item->apartment_uuid,
            ],
            'apartment_unit' => [
                'uuid' => $item->apartment_unit_uuid,
                'name' => $item->apartment_unit_name
            ],
            'data' => [
                'message' => $data['message'],
                'complainer_name' => $complainer->first_name .' '. $complainer->last_name,
                'complainer_uuid' => $complainer->uuid,
            ]
        ];
    }

    private function getMaintenanceNotifications($item){
        $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

        $maintenance = User::where('id', $data['maintenance_requester_id'])
                        ->select(['id', 'uuid', 'first_name', 'last_name'])
                        ->first();
        return [
            'id' => $item->id,
            'type' => $item->type,
            'is_read' => $item->is_read,
            'apartment' => [
                'location' => $item->location,
                'address' => $item->address,
                'uuid' => $item->apartment_uuid,
            ],
            'apartment_unit' => [
                'uuid' => $item->apartment_unit_uuid,
                'name' => $item->apartment_unit_name
            ],
            'data' => [
                'message' => $data['message'],
                'requester_name' => $maintenance->first_name .' '. $maintenance->last_name,
                'requester_uuid' => $maintenance->uuid,
            ]
        ];
    }

    private function getComplaintNotification($item){
        $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

        $complainer = User::where('id', $data['complainer_id'])
                        ->select(['id', 'uuid', 'first_name', 'last_name'])
                        ->first();

        $complain = Complaint::where('id', $data['complain_id'])
                    ->select(['title','description','status','priority','evidence','resolution_notes'])
                    ->first();
        return [
            'id' => $item->id,
            'type' => $item->type,
            'is_read' => $item->is_read,
            'apartment' => [
                'location' => $item->location,
                'address' => $item->address,
                'uuid' => $item->apartment_uuid,
            ],
            'apartment_unit' => [
                'uuid' => $item->apartment_unit_uuid,
                'name' => $item->apartment_unit_name
            ],
            'data' => [
                'message' => $data['message'],
                'complainer_name' => $complainer->first_name .' '. $complainer->last_name,
                'complainer_uuid' => $complainer->uuid,
            ],
            'complain' => $complain
        ];
    }

    private function getMaintenanceNotification($item){
        $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

        $maintenance = User::where('id', $data['maintenance_requester_id'])
                        ->select(['id', 'uuid', 'first_name', 'last_name'])
                        ->first();

        $maintenance = MaintenanceRequest::where('id', $data['maintainance_id'])
                    ->select(['title','description','status','priority','attachment','expected_visit_date'])
                    ->first();

        return [
            'id' => $item->id,
            'type' => $item->type,
            'is_read' => $item->is_read,
            'apartment' => [
                'location' => $item->location,
                'address' => $item->address,
                'uuid' => $item->apartment_uuid,
            ],
            'apartment_unit' => [
                'uuid' => $item->apartment_unit_uuid,
                'name' => $item->apartment_unit_name
            ],
            'data' => [
                'message' => $data['message'],
                'requester_name' => $maintenance->first_name .' '. $maintenance->last_name,
                'requester_uuid' => $maintenance->uuid,
            ],
            'maintenance' => $maintenance
        ];
    }
}
