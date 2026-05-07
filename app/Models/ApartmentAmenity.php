<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApartmentAmenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_unit_uuid',
        'amenity_id',
        'apartment_id',
        'amenity_number',
        'estate_manager_id',
    ];

    // ---------------- Relationships ----------------

    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id')->withDefault();
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }

    public function apartmentUnit()
    {
        return $this->belongsTo(ApartmentUnit::class, 'apartment_unit_uuid', 'uuid');
    }
}
