<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $fillable = [
        'price',
        'currency',
        'installmental_payment_id',
        'duration',
    ];

    public function installment()
    {
        return $this->hasOne(Installment::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
