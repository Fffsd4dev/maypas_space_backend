<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertyService
{
    public function create(array $data): Property
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the Property
            $property = Property::create([
                'title' => $data['title'],
                'purpose' => $data['purpose'],
                'country' => $data['country'],
                'state' => $data['state'],
                'locality' => $data['locality'],
                'area' => $data['area'] ?? null,
                'street' => $data['street'] ?? null,
                'youtube_video_link' => $data['youtube_video_link'] ?? null,
                'instagram_video_link' => $data['instagram_video_link'] ?? null,
                'type_id' => $data['type_id'],
                'sub_type_id' => $data['sub_type_id'],
                'description' => htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') ?? null,
                'user_id' => auth()->id(),
            ]);

            // 2. Create Pricing
            $pricing = $property->pricing()->create([
                'price' => $data['price'],
                'currency' => $data['currency'],
                'duration' => $data['duration'],
                'property_id' => $property->id,
            ]);

            // 3. Create Installment (if applicable)
            if (!empty($data['initial_payment']) && !empty($data['monthly_payment']) && !empty($data['payment_duration'])) {
                $installment = $pricing->installment()->create([
                    'initial_payment' => $data['initial_payment'],
                    'monthly_payment' => $data['monthly_payment'],
                    'payment_duration' => $data['payment_duration'],
                    'pricing_id' => $pricing->id,
                ]);

                // $pricing->installmental_payment_id = $installment->id;
                // $pricing->save();
            }

            // 4. Create Details
            $property->details()->create([
                'no_rooms' => $data['no_rooms'],
                'no_bathrooms' => $data['no_bathrooms'],
                'no_toilets' => $data['no_toilets'],
                'area_size' => $data['area_size'] ?? null,
                'furnished' => $data['furnished'],
                'serviced' => $data['serviced'],
                'newly_built' => $data['newly_built'],
                'property_id' => $property->id,
            ]);

            // 5. Attach Features (Many-to-Many)
            $property->features()->sync($data['features']);

            // 6. Upload Media (Multiple Images)
            foreach ($data['images'] as $image) {
                // Store file and get full path (e.g. "properties/media/abcd1234.jpg")
                $path = $image->store('properties/media', 'public');

                // Extract filename from the path
                $filename = basename($path);

                $property->media()->create([
                    'filename' => $filename,
                ]);
            }

            return $property;
        });
    }

    public function update(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            // 1. Update the property itself
            $property->update([
                'title' => $data['title'],
                'purpose' => $data['purpose'],
                'country' => $data['country'],
                'state' => $data['state'],
                'locality' => $data['locality'],
                'area' => $data['area'] ?? null,
                'street' => $data['street'] ?? null,
                'youtube_video_link' => $data['youtube_video_link'] ?? null,
                'instagram_video_link' => $data['instagram_video_link'] ?? null,
                'type_id' => $data['type_id'],
                'sub_type_id' => $data['sub_type_id'],
                'description' => $data['description'] ?? null,
            ]);

            // 2. Update Pricing
            $property->pricing->update([
                'price' => $data['price'],
                'currency' => $data['currency'],
                'duration' => $data['duration'],
            ]);

            // 3. Update/Create Installment
            if ($data['initial_payment'] || $data['monthly_payment'] || $data['payment_duration']) {
                $installmentData = [
                    'initial_payment' => $data['initial_payment'],
                    'monthly_payment' => $data['monthly_payment'],
                    'payment_duration' => $data['payment_duration'],
                ];

                if ($property->pricing->installment) {
                    $property->pricing->installment->update($installmentData);
                } else {
                    $installment = $property->pricing->installment()->create($installmentData);
                    $property->pricing->update(['installmental_payment_id' => $installment->id]);
                }
            }

            // 4. Update Details
            if ($property->details) {
                $property->details->update([
                    'no_rooms' => $data['no_rooms'],
                    'no_bathrooms' => $data['no_bathrooms'],
                    'no_toilets' => $data['no_toilets'],
                    'area_size' => $data['area_size'] ?? null,
                    'furnished' => $data['furnished'],
                    'serviced' => $data['serviced'],
                    'newly_built' => $data['newly_built'],
                ]);
            }

            // 5. Sync Features
            $property->features()->sync($data['features']);

            // 6. Replace Images if new ones are sent
            if (!empty($data['images'])) {
                // delete old images
                foreach ($property->media as $media) {
                    Storage::disk('public')->delete('properties/media/' . $media->filename); // ensure correct path
                    $media->delete();
                }

                foreach ($data['images'] as $image) {
                    // Store file and get full path (e.g. "properties/media/abcd1234.jpg")
                    $path = $image->store('properties/media', 'public');

                    // Extract filename from the path
                    $filename = basename($path);

                    $property->media()->create([
                        'filename' => $filename,
                    ]);
                }

            }

            return $property;
        });
    }
}
