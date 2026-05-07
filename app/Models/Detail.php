<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    protected $fillable = [
        'no_rooms',
        'no_bathrooms',
        'no_toilets',
        'area_size',
        'furnished',
        'serviced',
        'newly_built',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
