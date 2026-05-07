<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RentCycle;
use Illuminate\Support\Facades\Validator;
use App\Models\ApartmentUnit;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RentCycleController extends Controller
{
    /**
     * Display a listing of rent cycles.
     */
    private string $serverTimezone;

    public function __construct()
    {
        $this->serverTimezone = config('app.timezone', 'UTC');
    }
    public function index(int $rent_id, int $tenant_id, bool $is_paid)
    {

        $cycles = RentCycle::select('cycle_start_date', 'cycle_end_date', 'fee', 'is_paid')
                    ->where('rent_manager_id', $rent_id)
                    ->where('tenant_id', $tenant_id)
                    ->orderByDesc('created_at')
                    ->get();
        return response()->json($cycles);
    }

    /**
     * Store a newly created rent cycle.
     */
 public function store(array $data)
{

    $validator = Validator::make($data, [
        'occupant_id'      => 'required|exists:users,id',
        'rent_manager_id'  => 'required|exists:rent_managers,id',
        'cycle_start_date' => 'required|date',
        'cycle_end_date'   => 'required|date|after:cycle_start_date',
        'fee'              => 'required|numeric|min:0',
        'is_paid'          => 'boolean',
        'uuid'             => 'required|string|unique:rent_cycles,uuid',
    ]);

    if ($validator->fails()) {
        return [
            'errors' => $validator->errors(),
            'status_code' => 422,
        ];
    }
    $validated = $validator->validated();
    $validated['cycle_start_date_server_time'] = Carbon::parse($validated['cycle_start_date'], 'UTC')->setTimezone($this->serverTimezone);
    $validated['cycle_end_date_server_time']   = Carbon::parse($validated['cycle_end_date'], 'UTC')->setTimezone($this->serverTimezone);

    $cycle = RentCycle::create($validated);

    return $cycle;
}
public function update(Request $request, $slug, $rent_cycle_uuid)
{
    $estateManagerId = $request->user()->estate_manager_id;

    // Validate request
    $validator = Validator::make($request->all(), [
        'cycle_start_date'    => 'sometimes|date_format:Y-m-d H:i:s',
        'cycle_end_date'      => 'sometimes|date_format:Y-m-d H:i:s|after:cycle_start_date',
        'fee'                 => 'sometimes|numeric|min:0',
        'is_paid'             => 'sometimes|boolean',
        'apartment_unit_uuid' => 'required|uuid',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }





    $validated = $validator->validated();


$validated['cycle_start_date_server_time'] = Carbon::parse($validated['cycle_start_date'], 'UTC')->setTimezone($this->serverTimezone);
$validated['cycle_end_date_server_time']   = Carbon::parse($validated['cycle_end_date'], 'UTC')->setTimezone($this->serverTimezone);
    // Find apartment unit and ensure it belongs to this estate manager
    $apartmentUnit = ApartmentUnit::where('apartment_units.uuid', $validated['apartment_unit_uuid'])
        ->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
        ->where('apartments.estate_manager_id', $estateManagerId)
        ->select('apartment_units.id') // only need ID for further checks
        ->first();

    if (! $apartmentUnit) {
        return response()->json(['message' => 'Apartment unit not found or unauthorized'], 404);
    }

    // Find rent cycle by UUID and ensure it belongs to the apartment unit
    $cycle = RentCycle::where('rent_cycles.uuid', $rent_cycle_uuid)
        ->join('rent_managers', 'rent_managers.id', '=', 'rent_cycles.rent_manager_id')
        ->where('rent_managers.apartment_unit_id', $apartmentUnit->id)
        ->where('rent_managers.is_active', true)
        ->select('rent_cycles.*') // ensure RentCycle model fields
        ->first();


    if (! $cycle) {
        return response()->json(['message' => 'Rent cycle not found'], 404);
    }

    // Update only validated fields
    $cycle->update($validated);

    return response()->json(['message' => 'Rent cycle updated successfully'], 200);
}



    /**
     * Display a specific rent cycle.
     */
    public function show($id)
    {
        $cycle = RentCycle::with(['tenant', 'rentManager'])->where('uuid', $id)->first();

        if (!$cycle) {
            return response()->json(['message' => 'Rent cycle not found'], 404);
        }

        return response()->json($cycle);
    }

    /**
     * Update a specific rent cycle.
     */


    /**
     * Remove a specific rent cycle.
     */
    public function destroy($id)
    {
        $cycle = RentCycle::find($id);

        if (!$cycle) {
            return response()->json(['message' => 'Rent cycle not found'], 404);
        }

        $cycle->delete();

        return response()->json(['message' => 'Rent cycle deleted successfully']);
    }
    public function eraseAll($rentManagerId)
    {
        $cycles = RentCycle::where('rent_manager_id', $rentManagerId)->get();

        if ($cycles->isEmpty()) {
            return;
        }

        foreach ($cycles as $cycle) {
            $cycle->delete();
        }

        return response()->json(['message' => 'All rent cycles deleted successfully']);
    }   
    
}
