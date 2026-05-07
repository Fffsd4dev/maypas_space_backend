<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\BrandModel;

class BrandController extends Controller
{
    public function create(Request $request, $slug)
    {
         
        $request->validate([
            'name' => 'required|string',
            'addresses' => 'required|array',
            'addresses.*' => 'required|string',
            'phones' => 'required|array',
            'phones.*' => 'required|regex:/^\+?[0-9]\d{1,14}$/',
            'social_links' => 'required|array',
            'social_links.*' => 'required|string',
            'logo' => 'sometimes|image|max:1024|mimes:jpeg,png,jpg,gif,svg', // 1MB max
        ]);

       $estate = app('estateManager');

        // Check if brand record already exists for this estate manager
        $brand = BrandModel::where('estate_manager_id', $estate->id)->first();

        $logoPath = $brand?->logo; // Keep old logo by default

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($brand && $brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }

            // Upload new logo
            $logoPath = $request->file('logo')->store('brand_logos', 'public');
        }

        // Create or update record
        $updatedBrand = BrandModel::updateOrCreate(
            [
                'estate_manager_id' => $estate->id,
            ],
            [
                'name' => $request->name,
                'addresses' => json_encode($request->addresses),
                'phones' => json_encode($request->phones),
                'social_links' => json_encode($request->social_links),
                'logo' => $logoPath,
                'estate_manager_id' => $estate->id,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Brand information saved successfully.',
        ], 200);
    }
   public function getBrandData(Request $request)
{
    $estate = app('estateManager');
    $brand_data = BrandModel::where('estate_manager_id', $estate->id)->first();

    // If no record found, return error immediately
    if (!$brand_data) {
        return response()->json([
            'status'  => false,
            'message' => 'Brand not found.',
        ], 404);
    }

    // Decode JSON fields only if value is not null
    $brand_data->social_links = $brand_data->social_links
        ? json_decode($brand_data->social_links)
        : [];

    $brand_data->phones = $brand_data->phones
        ? json_decode($brand_data->phones)
        : [];
$brand_data->logo = asset('storage/' . $brand_data->logo);
    $brand_data->addresses = $brand_data->addresses
        ? json_decode($brand_data->addresses)
        : [];

    return response()->json([
        'status' => true,
        'data'   => $brand_data,
    ], 200);
}
}
