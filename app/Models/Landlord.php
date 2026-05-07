<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landlord extends Model
{
    protected $fillable = [
        'name',
        'phone_number',
        'bank_name',
        'bank_account_number',
        'estate_manager_id'
    ];
}
