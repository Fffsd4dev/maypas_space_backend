<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RentManager;
use App\Models\RentCycle;
use App\Models\Charge;
use App\Models\Landlord;
use App\Models\ApartmentUnit;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Models\User;
use App\Models\BrandModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentManagerController extends Controller
{
    protected $rentCycleController;

    public function __construct(RentCycleController $rentCycleController)
    {
        $this->rentCycleController = $rentCycleController;
    }

    public function index(Request $request)
    {
        $estateManagerId = (int) $request->user()->estate_manager_id;
        $query = RentManager::query()
            ->select([
                'rent_managers.id',
                'rent_managers.start_date',
                'rent_managers.termination_date',
                'rent_managers.is_active',
                'rent_managers.occupant_id',
                'rent_managers.estate_manager_id',
                'rent_managers.apartment_unit_id',
                'rent_managers.uuid as rent_account_uuid',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.uuid as user_uuid',
                'estate_managers.estate_name as estate_manager_name',
                'apartment_categories.name as apartment_category_name',
                'apartments.uuid as apartment_uuid',
                'apartments.uuid as apartment_uuid',
                'apartments.address as apartment_address',
                'apartment_units.apartment_unit_name as apartment_unit_name',
                'apartment_units.id as apartment_unit_id',
                'apartment_units.uuid as apartment_unit_uuid',
            ])
            ->join('users', 'users.id', '=', 'rent_managers.occupant_id')
            ->join('estate_managers', 'estate_managers.id', '=', 'rent_managers.estate_manager_id')
            ->join('apartment_units', 'apartment_units.id', '=', 'rent_managers.apartment_unit_id')
            ->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
            ->join('apartment_categories', 'apartment_categories.id', '=', 'apartments.category_id')
            ->where('users.estate_manager_id', $estateManagerId)
            ->when($request->has('is_active'), fn($q) => $q->where('rent_managers.is_active', $request->boolean('is_active')))
            ->orderByDesc('rent_managers.created_at');
        
        

        $paginated = $query->paginate(20)->through(fn($row) => [
            'id' => $row->id,
            'start_date' => $row->start_date,
            'termination_date' => $row->termination_date,
            'is_active' => (bool) $row->is_active,
        
            'user' => [
                'uuid' => $row->user_uuid,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'email' => $row->email,
            ],
            'estate_manager' => [
                'id' => $row->estate_manager_id,
                'name' => $row->estate_manager_name,
            ],
            'rent_account' => [
                'uuid' => $row->rent_account_uuid,
            ],
            'estate_manager' => [
                'id' => $row->estate_manager_id,
                'name' => $row->estate_manager_name,
            ],
            'apartment_units' => [
                'unit_name' => $row->apartment_unit_name,
                'uuid' => $row->apartment_unit_uuid,
            ],
            'apartment' => [
                'category' => $row->apartment_category_name,
                'address' => $row->apartment_address,
                'uuid'=>$row->apartment_uuid,
            ],
        ]);

        return response()->json($paginated);
    }
public function store(Request $request)
{
    $data = $request->validate([
        'occupant_uuid'       => 'required|uuid',
        'apartment_unit_uuid' => 'required|uuid',
        'start_date'          => 'required|date_format:Y-m-d H:i:s',
        'termination_date'    => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:start_date',
        'is_active'           => 'boolean',
        'first_rent'          => 'required|numeric|min:0',
        'first_rent_expiry'   => 'required|date_format:Y-m-d H:i:s|after:start_date',
        'first_payment_paid'  => 'required|boolean',
        'account_type'        => 'required|string|in:one-off,recurrent',
    ]);

    $estateManagerId = (int) $request->user()->estate_manager_id;
   $rent_cycle_uuid =null;
    // Fetch related records in one go
$brand_data = BrandModel::where('estate_manager_id',$request->user()->estate_manager_id)->select('name', 'addresses', 'phones', 'social_links', 'logo')->first();
 $apartmentUnit = ApartmentUnit::with(['apartment:id,name,location,category_id,landlord_id', 'apartment.category:id,name,description,uuid'])
        ->where('uuid',  $data['apartment_unit_uuid'])->first();

    $occupant = User::select('id', 'uuid', 'email', 'first_name', 'last_name','email')
        ->where('uuid', $data['occupant_uuid'])
        ->where('estate_manager_id', $estateManagerId)
        ->first();

    if (! $apartmentUnit || ! $occupant || 
        ! $this->checkApartmentAgent($apartmentUnit->uuid, $estateManagerId, $apartmentUnit->apartment_id)) {
        return response()->json(['message' => 'Apartment unit or occupant not found'], 404);
    }

    if($this->checkApartmentAvailability($apartmentUnit->id)){
         return response()->json(['message' => 'Apartment currently occupied'], 404);

    }
    return DB::transaction(function () use ($data, $apartmentUnit, $occupant, $estateManagerId, $brand_data) {
        
        $rentManager = RentManager::create([
            'uuid'              => (string) Str::uuid(),
            'estate_manager_id' => $estateManagerId,
            'occupant_id'       => $occupant->id,
            'apartment_unit_id' => $apartmentUnit->id,
            'start_date'        => $data['start_date'],
            'termination_date'  => $data['termination_date'] ?? null,
            'is_active'         => $data['is_active'] ?? true,
            'first_rent'        => $data['first_rent'],
            'first_rent_expiry' => $data['first_rent_expiry'],
            'first_payment_paid'=> $data['first_payment_paid'],
            'account_type'      => $data['account_type'],
        ]);
        $rent_cycle_uuid = (string) Str::uuid();
        // Create initial rent cycle
        $rent_cycle = $this->rentCycleController->store([
            'rent_manager_id'   => $rentManager->id,
            'occupant_id'       => $rentManager->occupant_id,
            'cycle_start_date'  => $data['start_date'],
            'cycle_end_date'    => $data['first_rent_expiry'],
            'fee'               => $data['first_rent'],
            'uuid'              => (string) $rent_cycle_uuid,
            'is_paid'           => $data['first_payment_paid'],
        ]);
        $charges = Charge::getApartmentUnitCharges($apartmentUnit->id);
        $rentManager->user = $occupant;
        $rentManager->apartment_unit = $apartmentUnit;
        
        $rentManager->rent_value = $data['first_rent'];
        unset($data);
        
        //process rent invoice
        InvoiceController::generateRentInvoice($rentManager, $charges, $rent_cycle->id,$brand_data);
        

        return response()->json(['message' => 'Rent registered successfully'], 201);
    });
}

public function showAllCycles(Request $request, $slug,$rent_account_uuid)
{
     $estateManagerId = (int) $request->user()->estate_manager_id;
    $records = RentManager::query()
        ->select([
            'rent_managers.uuid as rent_manager_uuid',
            'rent_managers.start_date',
            'rent_managers.termination_date',
            'rent_managers.is_active',
            'rent_managers.account_type',
            'users.first_name as occupant_first_name',
            'users.last_name as occupant_last_name',
            'users.email as occupant_email',
            'users.uuid as occupant_uuid',
            'apartment_units.apartment_unit_name',
            'apartment_units.uuid as apartment_unit_uuid',
            'rent_cycles.uuid as cycle_uuid',
            'rent_cycles.cycle_start_date',
            'rent_cycles.cycle_end_date',
            'rent_cycles.fee',
            'rent_cycles.is_paid',
            'estate_managers.estate_name as estate_manager_name',
        ])
        ->join('users', 'rent_managers.occupant_id', '=', 'users.id')
        ->join('estate_managers', 'rent_managers.estate_manager_id', '=', 'estate_managers.id')
        ->join('apartment_units', 'rent_managers.apartment_unit_id', '=', 'apartment_units.id')
        ->join('rent_cycles', 'rent_managers.id', '=', 'rent_cycles.rent_manager_id')
        ->where('rent_managers.uuid', $rent_account_uuid)
        ->where('rent_managers.estate_manager_id',$estateManagerId)
        ->orderBy('rent_cycles.cycle_start_date')
        ->get();
    

    if ($records->isEmpty()) {
        return response()->json(['message' => 'Record not found'], 404);
    }

    $first = $records->first();

    $cycles = $records->map(fn($row) => [
        'uuid'             => $row->cycle_uuid,
        'cycle_start_date' => $row->cycle_start_date,
        'cycle_end_date'   => $row->cycle_end_date,
        'fee'              => $row->fee,
        'is_paid'          => $row->is_paid,
    ]);

    return response()->json([
        'rent_manager' => [
            'uuid'                 => $first->rent_manager_uuid,
            'start_date'           => $first->start_date,
            'termination_date'     => $first->termination_date,
            'account_type'         => $first->account_type,
            'is_active'            => (bool) $first->is_active,
            'occupant' => [
                'first_name'       => $first->occupant_first_name,
                'last_name'        => $first->occupant_last_name,
                'email'            => $first->occupant_email,
                'uuid'              =>$first->occupant_uuid,
            ],
            'estate_manager_name'  => $first->estate_manager_name,
            'apartment_unit_uuid'   => $first->apartment_unit_uuid,
            'apartment_unit'       => $first->apartment_unit_name,
            'rent_cycles'          => $cycles,
        ]
    ]);
}

public function terminateAccount(Request $request, $slug, $apartment_unit_uuid)
{
    $data = $request->validate([
        'occupant_uuid'     => 'required|uuid',
        'termination_date'  => 'required|date_format:Y-m-d H:i:s',
    ]);

    $estateManagerId = (int) $request->user()->estate_manager_id;

    // Fetch occupant id
    $occupantId = User::where('uuid', $data['occupant_uuid'])
        ->where('estate_manager_id', $estateManagerId)
        ->value('id');

    if (! $occupantId) {
        return response()->json(['message' => 'Occupant not found'], 404);
    }

    // Get rent manager with joins
    $rentManager = RentManager::join('apartment_units', 'apartment_units.id', '=', 'rent_managers.apartment_unit_id')
        ->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
        ->where('apartment_units.uuid', $apartment_unit_uuid)
        ->where('apartments.estate_manager_id', $estateManagerId)
        ->where('rent_managers.occupant_id', $occupantId)
        ->where('rent_managers.is_active', true)
        ->select('rent_managers.*')
        ->first();

    if (! $rentManager) {
        return response()->json(['message' => 'Active rent account not found for this apartment unit'], 404);
    }

    DB::transaction(function () use ($rentManager, $data) {
        // Terminate rent manager
        $rentManager->update([
            'is_active'        => false,
            'termination_date' => $data['termination_date'],
        ]);

        // Update only the latest rent cycle
        $latestCycle = RentCycle::where('rent_manager_id', $rentManager->id)
            ->orderByDesc('cycle_start_date')
            ->first();

        if ($latestCycle) {
            $latestCycle->update(['cycle_end_date' => $data['termination_date']]);
        }
    });

    return response()->json(['message' => 'Rent account terminated successfully']);
}


 

     

        

    public function destroy($id)
    {
        $rentManager = RentManager::findOrFail($id);

        return DB::transaction(function () use ($rentManager) {
            $this->rentCycleController->eraseAll($rentManager->id);
            $rentManager->delete();

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }
    private function checkApartmentAgent($apartmentUnitUuid, $agentId, $apartmentId)
{
    return DB::table('apartment_units')
        ->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
        ->where('apartments.id', $apartmentId)
        ->where('apartment_units.uuid', $apartmentUnitUuid)
        ->where('apartments.estate_manager_id', $agentId)
        ->exists();
}
private function checkApartmentAvailability($apartment_unit_id){

     return DB::table('rent_managers')
        ->where('apartment_unit_id', $apartment_unit_id)
        ->where('is_active', true)
        ->where('termination_date','=',null)
        ->exists();

}

}