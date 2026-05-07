<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySubType extends Model
{
    protected $fillable = [
        'property_sub_type',
        'property_type_id',
    ];

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }
}
