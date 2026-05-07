<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartmentCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'uuid',
    ];
    protected $hidden = ['id', 'created_at', 'updated_at'];    /**
     * Get the apartments for this category.
     */
    public function apartments()
    {
        return $this->hasMany(Apartment::class, 'category_id');
    }
}
