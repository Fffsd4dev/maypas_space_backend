<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'occupant_id',
        'estate_manager_id',
        'apartment_unit_id',
        'start_date',
        'rent_amount',
        'termination_date',
        'is_active',
        'uuid',
        'account_type'
    ];
protected $hidden = ['id', 'created_at', 'updated_at'];

    /**
     * Relationships
     */
    public function apartmentUnit()
    {
        return $this->belongsTo(ApartmentUnit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'occupant_id');
    }
  
}
