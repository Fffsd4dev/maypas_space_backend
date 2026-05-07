<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ApartmentUnit extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'apartment_id','apartment_unit_name','estate_manager_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
        'apartment_id',
        'estate_manager_id'
    ];

    /**
     * Get the apartment that owns the location.
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'apartment_id')
            ->select('id', 'name', 'location', 'uuid');   
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class, 'amenity_id')
            ->select('id', 'name');
    }
}