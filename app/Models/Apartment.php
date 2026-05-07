<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ApartmentCategory;
use App\Models\EstateManager;
use App\Models\ApartmentLocation;

class Apartment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'number_item',
        'location',
        'location_id',
        'branch_id',
        'country_id',
        'address',
        'landlord_id',
        'uuid',
        'name',
        'estate_manager_id',
    ];

    protected $hidden = ['created_at', 'updated_at', 'id'];

    /**
     * Default eager loads.
     *
     * @var array
     */

    protected $with = ['estateManager'];

    /**
     * Get the category that owns the Apartment.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ApartmentCategory::class, 'category_id')
            ->select('id', 'name', 'uuid', 'description');
    }

    /**
     * Get the estate manager that owns the Apartment.
     */
    public function estateManager(): BelongsTo
    {
        return $this->belongsTo(EstateManager::class, 'estate_manager_id')
            ->select('id', 'estate_name', 'slug');
    }
    public function apartmentUnits(): HasMany
    {
        return $this->hasMany(ApartmentUnit::class, 'apartment_id')
            ->select('id', 'apartment_id', 'uuid as apartment_unit_uuid', 'apartment_unit_name','created_at', 'updated_at');
    }
        
        public function landLord():BelongsTo
        {
             return $this->belongsTo(Landlord::class, 'landlord_id')
            ->select('id', 'name', 'phone_number', 'bank_name', 'bank_account_number');
        } 
            
         
}
