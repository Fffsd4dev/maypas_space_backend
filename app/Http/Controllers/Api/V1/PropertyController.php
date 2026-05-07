<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Property;
use App\Services\PropertyService;


class PropertyController extends Controller
{
    public function create(Request $request, PropertyService $service){
        $user = $request->user();

        if($user->user_type_id === 3){
            return response()->json(['message' => 'You are not a landlord or agent so you cannot create a property'], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            //Properties Table
            'title' => 'required|string|max:255',
            'purpose' => ['required', Rule::in(['rent', 'sale', 'short-let'])],
            'country' => 'required|numeric|gte:2',
            'state' => 'required|string|max:255',
            'locality' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'youtube_video_link' => 'nullable|url',
            'instagram_video_link' => 'nullable|url',
            'type_id' => 'required|numeric|gte:2|exists:property_types,id', //gte 2 for now until i decide on the Airbnb logic
            'sub_type_id' => 'required|numeric|gte:1|exists:property_sub_types,id',
            'description' => 'nullable|string|max:255',

            //features Pivot table
            'features' => 'required|array',
            'features.*' => ['integer', Rule::exists('features', 'id')],

            //Pricing Table
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'duration' => [
                'nullable',
                Rule::in(['yearly', 'monthly', 'year', 'day', 'sqm']),
            ],

            //installment table
            'initial_payment' => 'nullable|numeric|min:0',
            'monthly_payment' => 'nullable|numeric|min:0',
            'payment_duration' => 'nullable|integer|min:1',

            //Details table
            'no_rooms' => 'required|string|max:20',
            'no_bathrooms' => 'required|string|max:20',
            'no_toilets' => 'required|string|max:20',

            'area_size' => 'nullable|numeric|min:0',

            'furnished' => ['required', Rule::in(['yes', 'no'])],
            'serviced' => ['required', Rule::in(['yes', 'no'])],
            'newly_built' => ['required', Rule::in(['yes', 'no'])],

            //Media Table
            'images' => 'required|array',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Retrieve validated data from the validator instance
        $validated = $validator->validated();

        $property = $service->create($validated);

        return response()->json(['message' => 'Property created', 'property' => $property], 201);
    }
}
