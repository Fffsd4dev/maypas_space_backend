<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInfo extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'payment_name',
        'payment_fee',
        'rent_managers_id',
        'rent_cycle_id',
        'user_id',
        'apartment_unit_id',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
        'invoice_id',
        'rent_managers_id',
        'rent_cycle_id',
        'user_id',
        'apartment_unit_id',
    ];
}
