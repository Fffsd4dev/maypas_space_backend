<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentCategory;
use App\Models\ApartmentUnit;
use App\Models\ApartmentAmenity;
use App\Models\Amenity;
use App\Models\LandlordAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\{RentManager,Location, Branch};
use Carbon\Carbon;

class ApartmentController extends Controller
{
    /**
     * Display a listing of apartments for the authenticated user's estate manager.
     */


public function index(Request $request): JsonResponse
{
    $estateManagerId = $request->user()->estate_manager_id;

    // sort amenities by apartment_unit_uuid for quick lookup
    $apartmentAmenities = ApartmentAmenity::where('estate_manager_id', $estateManagerId)
        ->join('amenities', 'apartment_amenities.amenity_id', '=', 'amenities.id')
        ->select(
            'apartment_amenities.id as apartment_amenity_id',
            'apartment_amenities.apartment_unit_uuid',
            'apartment_amenities.amenity_number',
            'amenities.name as amenity_name'
        )
        ->get()
        ->groupBy('apartment_unit_uuid')
        ->map(function ($group) {
            //return in an array with apartment_unit_uuid as key
            return $group->map(fn ($item) => [
                'id'             => $item->apartment_amenity_id,
                'name'           => $item->amenity_name,
                'amenity_number' => $item->amenity_number,
            ]);
        });

    // Fetch categories with apartments and units
    $categories = ApartmentCategory::whereHas('apartments', function ($query) use ($estateManagerId) {
            $query->where('estate_manager_id', $estateManagerId);
        })
        ->with([
            'apartments' => function ($query) use ($estateManagerId) {
                $query->where('estate_manager_id', $estateManagerId)
                    ->select(
                        'id',
                        'uuid',
                        'name',
                        'category_id',
                        'estate_manager_id',
                        'landlord_id',
                        'address',
                        'location'
                    );
            },
            'apartments.apartmentUnits',
            'apartments.landLord',
        ])
        ->select('id', 'name', 'description')
        ->get();

    // Attach amenities to each apartment unit (safe lookup with ->get())
    $categories->each(function ($category) use ($apartmentAmenities) {
        $category->apartments->each(function ($apartment) use ($apartmentAmenities) {
            $apartment->apartmentUnits->each(function ($unit) use ($apartmentAmenities) {
                $unit->amenities = $apartmentAmenities->get($unit->apartment_unit_uuid, collect());
            });
        });
    });

    return response()->json($categories);
}



    /**
     * Store a new apartment.
     */
// public function store(Request $request, string $slug): JsonResponse
// {
//     $user = $request->user();
//     $validated = $request->validate([
//         'category_uuid'  => ['required', 'exists:apartment_categories,uuid'],
//         'number_item'    => ['required', 'integer', 'min:1'],
//         // 'location_uuid'  => ['required', 'string', 'exists:locations,uuid'],
//         // 'branch_uuid'  => ['required', 'string', 'exists:branches,uuid'],
//         'address'        => ['required', 'string'],
//         'name'           => ['required', 'string', 'max:255'],
//         'landlord_id'  => ['nullable', 'exists:landlords,id'],
//     ]);

//     $estateManagerId = $user->estate_manager_id;
    

//     // if($this->checkApartmentNameExists($validated['name'], $estateManagerId, $validated['location'])) {
//     //     return response()->json([
//     //         'message' => 'Apartment name already exists for this estate manager and this location.'
//     //     ], Response::HTTP_CONFLICT);

//     // }

//     // $location = Location::where('uuid', $validated['location_uuid'])
//     //                         ->where('estate_manager_id', $estateManagerId)
//     //                         ->firstOrFail();

//     // $branch = Branch::where('uuid', $validated['branch_uuid'])
//     //                         ->where('estate_manager_id', $estateManagerId)
//     //                         ->firstOrFail();

//     $categoryId = ApartmentCategory::where('uuid', $validated['category_uuid'])->value('id');

//     return DB::transaction(function () use ($validated, $estateManagerId, $categoryId, $user, $location, $branch) {
//         $apartment = Apartment::create([
//             'category_id'       => $categoryId,
//             'estate_manager_id' => $estateManagerId,
//             'uuid'              => (string) Str::uuid(), 
//             // 'location_id'          => $location->id,
//             // 'branch_id'          => $branch->id,
//             'name'              => $validated['name'],
//             'address'           => $validated['address'],
//             'number_item'       => $validated['number_item'],
//             'landlord_id'     => $validated['landlord_id'] ?? null,
//         ]);

//         // Bulk insertion of apartment units
//         $locations = [];
//         for ($i = 1; $i <= $apartment->number_item; $i++) {
//             $locations[] = [
//                 'apartment_id'        => $apartment->id,
//                 'uuid'                => (string) Str::uuid(),
//                 'apartment_unit_name' => 'Unit '.$i,
//                 'created_at'          => now(),
//                 'updated_at'          => now(),
//             ];
//         }

//         // Example: if you have a model ApartmentUnit
//         ApartmentUnit::insert($locations);

//         return response()->json([
//             'message'   => 'Apartment created successfully',
//             'apartment' => $apartment,
//         ]);
//     });
// }

public function store(Request $request, string $slug): JsonResponse
{
    $user = $request->user();
    $validated = $request->validate([
        'category_uuid'  => ['required', 'exists:apartment_categories,uuid'],
        'number_item'    => ['required', 'integer', 'min:1'],
        // 'location_uuid'  => ['required', 'string', 'exists:locations,uuid'],
        // 'branch_uuid'  => ['required', 'string', 'exists:branches,uuid'],
        'location' => ['required', 'string'],
        'address'        => ['required', 'string'],
        'name'           => ['required', 'string', 'max:255'],
        'landlord_id'  => ['nullable', 'exists:landlords,id'],
    ]);

    $estateManagerId = $user->estate_manager_id;
    

    // if($this->checkApartmentNameExists($validated['name'], $estateManagerId, $validated['location'])) {
    //     return response()->json([
    //         'message' => 'Apartment name already exists for this estate manager and this location.'
    //     ], Response::HTTP_CONFLICT);

    // }

    // $location = Location::where('uuid', $validated['location_uuid'])
    //                         ->where('estate_manager_id', $estateManagerId)
    //                         ->firstOrFail();

    // $branch = Branch::where('uuid', $validated['branch_uuid'])
    //                         ->where('estate_manager_id', $estateManagerId)
    //                         ->firstOrFail();

    $categoryId = ApartmentCategory::where('uuid', $validated['category_uuid'])->value('id');

    return DB::transaction(function () use ($validated, $estateManagerId, $categoryId, $user) {
        $apartment = Apartment::create([
            'category_id'       => $categoryId,
            'estate_manager_id' => $estateManagerId,
            'uuid'              => (string) Str::uuid(), 
            // 'location_id'          => $location->id,
            // 'branch_id'          => $branch->id,
            'location' => $validated['location'],
            'name'              => $validated['name'],
            'address'           => $validated['address'],
            'number_item'       => $validated['number_item'],
            'landlord_id'     => $validated['landlord_id'] ?? null,
        ]);

        // Bulk insertion of apartment units
        $locations = [];
        for ($i = 1; $i <= $apartment->number_item; $i++) {
            $locations[] = [
                'apartment_id'        => $apartment->id,
                'uuid'                => (string) Str::uuid(),
                'apartment_unit_name' => 'Unit '.$i,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        // Example: if you have a model ApartmentUnit
        ApartmentUnit::insert($locations);

        return response()->json([
            'message'   => 'Apartment created successfully',
            'apartment' => $apartment,
        ]);
    });
}



    /**
     * Display a specific apartment.
     */
public function show(Request $request, string $slug, string $apartment_unit_uuid): JsonResponse
{
    $estateManagerId = auth()->user()->estate_manager_id;


   $apartmentUnit = ApartmentUnit::select(
    'apartment_units.uuid as apartment_unit_uuid', 
    'apartments.name as apartment_name',
    'apartments.location',
    'apartments.address',
    'apartment_categories.name as category_name',
    'apartment_categories.uuid as category_uuid'
)
->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
->join('apartment_categories', 'apartment_categories.id', '=', 'apartments.category_id')
->where('apartment_units.uuid', $apartment_unit_uuid)
->where('apartments.estate_manager_id', $estateManagerId)
->first();
$apartmentAmenities = ApartmentAmenity::where('estate_manager_id', $estateManagerId)
    ->where('apartment_unit_uuid', $apartment_unit_uuid)
    ->join('amenities', 'apartment_amenities.amenity_id', '=', 'amenities.id')
    ->select(
        'amenities.id as amenity_id',
        'amenities.name as amenity_name',
        'apartment_amenities.amenity_number'
    )
    ->get();
    

if (!$apartmentUnit) {
    return response()->json(
        ['message' => 'Apartment unit not found'], 
        Response::HTTP_NOT_FOUND
    );
}
$apartmentUnit->amenities = $apartmentAmenities;
return response()->json($apartmentUnit);
}

    /**
     * Update an apartment.
     */
public function updateApartmentUnit(Request $request, string $slug): JsonResponse
{
    // 1) Auth + scope to estate manager
    $user = $request->user();
    if (! $user) {
        return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
    $estateManagerId = $user->estate_manager_id;


    // 2) Find the apartment unit and ensure it's owned by this estate manager.
   

    // 3) Validate input
    $validated = $request->validate([
        // update only supplied fields
        'apartment_unit_name' => ['sometimes', 'string', 'max:255'],
        'apartment_uuid'      => ['sometimes', 'uuid', 'exists:apartments,uuid'],
        'apartment_unit_uuid'=>['sometimes','uuid','exists:apartment_units,uuid'],

        // optional amenities array of objects { id, number }
        'amenities'           => ['sometimes', 'array'],
        'amenities.*.id'      => ['required_with:amenities', 'integer'],
        'amenities.*.number'  => ['required_with:amenities', 'integer', 'min:1'],
    ]);

    // 4) Resolve apartment_uuid -> apartment_id if provided and ensure it belongs to estate manager
    $apartmentId = null;
     
    
 $apartmentUnit = ApartmentUnit::where('uuid', strip_tags($validated['apartment_unit_uuid']))
        ->whereHas('apartment', fn($q) => $q->where('estate_manager_id', $estateManagerId))
        ->firstOrFail();
    if (! $apartmentUnit) {
        return response()->json(['message' => 'Apartment unit not found or not owned by you'], Response::HTTP_NOT_FOUND);
    }
    $validated['apartment_id'] = $apartmentUnit->apartment_id;
    unset($validated['apartment_unit_uuid']);

    // 5) Prepare amenity upsert if provided
    $amenitiesPayload = $validated['amenities'] ?? null;
    unset($validated['amenities']); // keep $validated only for updating the unit

    DB::beginTransaction();

    try {
        // 6) Update the apartment unit with only supplied fields
        if (!empty($validated)) {
            $apartmentUnit->update($validated);
        }

        // 7) If amenities given, validate IDs in bulk and upsert them
        if ($amenitiesPayload !== null) {
            $amenityIds = array_column($amenitiesPayload, 'id');

            // fetch existing amenity ids in one query
            $existingAmenityIds = Amenity::whereIn('id', $amenityIds)
                ->pluck('id')
                ->all();

            // if some IDs are invalid, fail fast
            $missing = array_diff($amenityIds, $existingAmenityIds);
            if (!empty($missing)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Some amenities were not found',
                    'missing_ids' => array_values($missing)
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Build rows for bulk upsert
            $now = now();
            $upsertRows = [];
            foreach ($amenitiesPayload as $a) {
                $upsertRows[] = [
                    'apartment_unit_uuid' => $apartmentUnit->uuid,
                    'amenity_id'          => $a['id'],
                    // prefer provided apartment_id or fall back to the unit's apartment_id
                    'apartment_id'        => $apartmentId ?? $apartmentUnit->apartment_id,
                    'amenity_number'      => (int) $a['number'],
                    'created_at'          => $now,
                    'updated_at'          => $now,
                    'estate_manager_id'   => $estateManagerId,
                ];
            }

            // Upsert: unique keys are apartment_unit_uuid + amenity_id
            // Update amenity_number, apartment_id, updated_at on conflict
            DB::table('apartment_amenities')->upsert(
                $upsertRows,
                ['apartment_unit_uuid', 'amenity_id'],
                ['amenity_number', 'apartment_id', 'updated_at']
            );

            // Optionally remove any amenities that were previously attached but not provided now.
            // If you want to keep old ones, remove this block.
            $incomingAmenityIds = $existingAmenityIds; // same as $amenityIds validated
            DB::table('apartment_amenities')
                ->where('apartment_unit_uuid', $apartmentUnit->uuid)
                ->whereNotIn('amenity_id', $incomingAmenityIds)
                ->delete();
        }

        DB::commit();

        return response()->json([
            'status'  => true,
            'message' => 'Updated successfully'
        ], Response::HTTP_OK);
    } catch (\Throwable $e) {
        DB::rollBack();
        // log the exception in production (omitted here)
        return response()->json([
            'status' => false,
            'message' => 'An error occurred',
            'error'   => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

/**
 * Delete an apartment.
 */
public function destroy(string $uuid): JsonResponse
{
    $apartment = Apartment::where('uuid', strip_tags($uuid))->first();

    if (!$apartment) {
        return response()->json(
            ['message' => 'Apartment not found'],
            Response::HTTP_NOT_FOUND
        );
    }

    $apartment->delete();

    return response()->json(null, Response::HTTP_NO_CONTENT);
}


    /**
     * Display a listing of apartment categories.
     */
    public function categoryIndex(): JsonResponse
    {
        $categories = ApartmentCategory::all();
        
        return response()->json($categories);
    }

    /**
     * Store a new apartment category.
     */
    public function categoryStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:apartment_categories,name'],
            'description' => ['nullable', 'string']
        ]);
        $validated['uuid'] = (string) Str::uuid();
        $category = ApartmentCategory::create($validated);

        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * Display a specific apartment category.
     */
    public function categoryShow(string $uuid)
    {
    
        $category = ApartmentCategory::where('uuid', $uuid)->first();

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json($category);
    }

    /**
     * Update an apartment category.
     */
    public function categoryUpdate(Request $request, string $uuid)
    {
        $category = ApartmentCategory::where('uuid', $uuid)->first();

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                Rule::unique('apartment_categories', 'name')->ignore($id)
            ],
            'description' => ['nullable', 'string']
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Delete an apartment category.
     */
    public function categoryDestroy(string $uuid)
    {
        $category =  ApartmentCategory::where('uuid', $uuid)->first();

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $category->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
    public function getApartmentUnits(Request $request, string $apartmentId)
    {
        $units = ApartmentUnit::select('id', 'uuid', 'apartment_id', 
        'apartment_unit_name', 'apartments.location','apartments.address','apartments.uuid as apartment_uuid',
        'apartment_categories.name as category_name','apartment_categories.description as category_description')
            ->join('apartments', 'apartments.id', '=', 'apartment_units.apartment_id')
            ->join('apartment_categories', 'apartment_categories.id', '=', 'apartments.category_id')
            ->where('apartments.uuid', $apartmentId)->where('apartments.estate_manager_id', $request->user()->estate_manager_id)
            ->get();

        return response()->json($units);
    }
    private function checkApartmentNameExists(string $name, int $estateManagerId,$location): bool
    {
        return Apartment::where('name', $name)
                        ->where('estate_manager_id', $estateManagerId)
                        ->where('location', $location)
                        ->exists();
    }   
//  public function updateApartment(Request $request)
// {
//     $user = $request->user();
//     $estateManagerId = $user->estate_manager_id;

//     $validated = $request->validate([
//         'category_uuid'   => ['sometimes', 'exists:apartment_categories,uuid'],
//         // 'location_uuid'  => ['required', 'string', 'exists:locations,uuid'],
//         // 'branch_uuid'  => ['required', 'string', 'exists:branches,uuid'],
//         'location' => ['required', 'string'],
//         'address'         => ['sometimes', 'string'],
//         'name'            => ['required', 'string', 'max:255'],
//         'landlord_id'     => ['nullable', 'exists:landlords,id'],
//         'apartment_uuid'  => ['required', 'exists:apartments,uuid'],
//     ]);

//     $apartment = Apartment::where('uuid', $validated['apartment_uuid'])
//         ->where('estate_manager_id', $estateManagerId)
//         ->first();
//     $category = ApartmentCategory::where('uuid', $validated['category_uuid'])->first();
//     $validated['category_id'] = $category->id;

//     $location = Location::where('uuid', $validated['location_uuid'])
//                             ->where('estate_manager_id', $estateManagerId)
//                             ->firstOrFail();

//     $branch = Branch::where('uuid', $validated['branch_uuid'])
//                             ->where('estate_manager_id', $estateManagerId)
//                             ->firstOrFail();

//     $validated['location_id'] = $location->id;
//     $validated['branch_id'] = $branch->id;

        
//     if (! $apartment) {
//         return response()->json([
//             'message' => 'Apartment not found or unauthorized',
//         ], 404);
//     }

//     // Remove apartment_uuid so it won’t be updat

//     $apartment->update($validated);

//     return response()->json([
//         'data' =>'',
//         'message' => 'Apartment information updated successfully',
//     ], 200);
// }
// public function deleteApartmentUnit(Request $request): JsonResponse
// {
//     $user = $request->user();
//     $estateManagerId = $user->estate_manager_id;

//     $validated = $request->validate([
//         'apartment_unit_uuid'  => ['required', 'exists:apartment_units,uuid'],
//     ]);

//     $apartmentUnit = ApartmentUnit::where('uuid', $validated['apartment_unit_uuid'])
//         ->whereHas('apartment', function ($query) use ($estateManagerId) {
//             $query->where('estate_manager_id', $estateManagerId);
//         })
//         ->first();


//     if (! $apartmentUnit) {
//         return response()->json([
//             'message' => 'Apartment unit not found or unauthorized',
//         ], 404);
//     }

//     $apartmentUnit->delete();

//     return response()->json([
//         'data' =>'',
//         'message' => 'Apartment unit deleted successfully',
//     ], 200);    

// }

 public function updateApartment(Request $request)
{
    $user = $request->user();
    $estateManagerId = $user->estate_manager_id;

    $validated = $request->validate([
        'category_uuid'   => ['sometimes', 'exists:apartment_categories,uuid'],
        // 'location_uuid'  => ['required', 'string', 'exists:locations,uuid'],
        // 'branch_uuid'  => ['required', 'string', 'exists:branches,uuid'],
        'location' => ['required', 'string'],
        'address'         => ['sometimes', 'string'],
        'name'            => ['required', 'string', 'max:255'],
        'landlord_id'     => ['nullable', 'exists:landlords,id'],
        'apartment_uuid'  => ['required', 'exists:apartments,uuid'],
    ]);

    $apartment = Apartment::where('uuid', $validated['apartment_uuid'])
        ->where('estate_manager_id', $estateManagerId)
        ->first();
    $category = ApartmentCategory::where('uuid', $validated['category_uuid'])->first();
    $validated['category_id'] = $category->id;

    // $location = Location::where('uuid', $validated['location_uuid'])
    //                         ->where('estate_manager_id', $estateManagerId)
    //                         ->firstOrFail();

    // $branch = Branch::where('uuid', $validated['branch_uuid'])
    //                         ->where('estate_manager_id', $estateManagerId)
    //                         ->firstOrFail();

    // $validated['location_id'] = $location->id;
    // $validated['branch_id'] = $branch->id;

        
    if (! $apartment) {
        return response()->json([
            'message' => 'Apartment not found or unauthorized',
        ], 404);
    }

    // Remove apartment_uuid so it won’t be updat

    $apartment->update($validated);

    return response()->json([
        'data' =>'',
        'message' => 'Apartment information updated successfully',
    ], 200);
}
public function deleteApartmentUnit(Request $request): JsonResponse
{
    $user = $request->user();
    $estateManagerId = $user->estate_manager_id;
    $validated = $request->validate([
        'apartment_unit_uuid'  => ['required', 'exists:apartment_units,uuid'],
    ]);
    

    $apartmentUnit = ApartmentUnit::where('uuid', $validated['apartment_unit_uuid'])
        ->whereHas('apartment', function ($query) use ($estateManagerId) {
            $query->where('estate_manager_id', $estateManagerId);
        })
        ->first();


    if (! $apartmentUnit) {
        return response()->json([
            'message' => 'Apartment unit not found or unauthorized',
        ], 404);
    }

    $apartmentUnit->delete();

    return response()->json([
        'data' =>'',
        'message' => 'Apartment unit deleted successfully',
    ], 200);    

}


    public function assignAdminApartment(Request $request, $slug, $apartmentUuid)
    {
        try {
            $landlord = $request->user();
              $estate = app('estateManager');

            // Try to find apartment
           

            // Authorization check
             if (!((int)$landlord->user_type_id === 1 && (int)$landlord->estate_manager_id ===(int) $estate->id)) {
            return response()->json(['message' => "You are not authorized"], 403);
        }

 $apartment = Apartment::where('uuid', $apartmentUuid)
                        ->select(['id', 'landlord_agent_id'])
                        ->firstOrFail();
            // Validate input
            $validator = Validator::make($request->all(), [
                'landlord_agent_uuid' => 'required|string|exists:landlord_agents,uuid',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }


            $validatedData = $validator->validated();

            $assignedLandlord = LandlordAgent::where('uuid', $validatedData['landlord_agent_uuid'])
                                ->where('estate_manager_id', $landlord->estate_manager_id)
                                ->select(['id', 'uuid'])
                                ->firstOrFail();

            // Update
            $apartment->landlord_agent_id = $assignedLandlord->id;

            $updated = $apartment->save();

            if (!$updated) {
                return response()->json([
                    'message' => 'Something went wrong. Please try again later'
                ], 500);
            }

            return response()->json([
                'message' => 'Apartment assigned successfully',
                'data'    => $apartment->fresh() // show latest
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Apartment not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error'   => $e->getMessage(), // hide in production if sensitive
            ], 500);
        }
    }
    public function getTenantApartments(Request $request): JsonResponse
    {
        $user = $request->user();
        $estateManagerId = $user->estate_manager_id ?? null;

        if (!$estateManagerId) {
            return response()->json([
                'message' => 'You are not authorized to view this resource'
            ], 403);
        }

        $apartmentData = RentManager::where('rent_managers.occupant_id', $user->id)
            ->where('rent_managers.estate_manager_id', $estateManagerId)
            ->join('apartment_units', 'rent_managers.apartment_unit_id', '=', 'apartment_units.id')
            ->join('apartments', 'apartment_units.apartment_id', '=', 'apartments.id')
            ->join('apartment_categories', 'apartments.category_id', '=', 'apartment_categories.id')
            ->join('landlords', 'apartments.landlord_id', '=', 'landlords.id')
            ->select(
                'apartment_units.uuid as apartment_unit_uuid',
                'apartment_units.apartment_unit_name',
                'apartments.uuid as apartment_uuid',
                'apartments.location as apartment_location',
                'apartments.address as apartment_address',
                'apartment_categories.name as apartment_category_name',
                'apartment_categories.description as apartment_category_description',
                'landlords.name as landlord_name',
            )
            ->get()
            ->toArray();

        return response()->json([
            'data' => $apartmentData,
            'message' => 'Apartments retrieved successfully'
        ], 200);  
    }




}