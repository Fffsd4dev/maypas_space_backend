<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    protected $fillable = [
        'apartment_unit_id',
        'name',
        'charge_type',
        'fee_type',
        'value',
        'estate_manager_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'estate_manager_id',
        'apartment_unit_id'
    ];

    public static function getApartmentUnitCharges($apartmentUnitId)
    {
        return self::where('apartment_unit_id', $apartmentUnitId)->select(
            'charge_type',
            'name',
            'fee_type',
            'name',
            'value'
        )->get();
    }
}
