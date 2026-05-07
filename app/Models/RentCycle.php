<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'occupant_id',
        'rent_manager_id',
        'cycle_start_date',
        'cycle_end_date',
        'cycle_start_date_server_time',
        'cycle_end_date_server_time',
        'fee',
        'is_paid',
        'uuid',
        'status',
    ];
    protected $hidden = ['id', 'created_at', 'updated_at'];

}
