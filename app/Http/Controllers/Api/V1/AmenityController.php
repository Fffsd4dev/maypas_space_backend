<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AmenityController extends Controller
{
    // List all amenities
    public function index(Request $request)
    {
        
        if(!$request->user()){
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        return response()->json(Amenity::all(), Response::HTTP_OK);
    }

    // Store new amenity
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:amenities,name',
    ]);

    $amenity = Amenity::create($validated);

    return response()->json($amenity, Response::HTTP_CREATED);
}


    // Show a single amenity
    public function show(Request $request, $id){
    
        if(!$request->user()){
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $amenity = Amenity::where('id', $id)->firstOrFail();

        return response()->json($amenity, Response::HTTP_OK);
    }

    // Update amenity
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
        ]);

        $amenity = Amenity::where('id', $id)->firstOrFail();
        $amenity->update($validated);

        return response()->json($amenity, Response::HTTP_OK);
    }

    // Delete amenity
    public function destroy($id)
    {
        $amenity = Amenity::where('id', $id)->firstOrFail();
        $amenity->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    
}
