<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    protected $fillable = [
        'initial_payment',
        'monthly_payment',
        'payment_duration', // stored as number of months
    ];

    public function pricing()
    {
        return $this->belongsTo(Pricing::class, 'pricing_id');
    }
}
