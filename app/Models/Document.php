<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'name', 'form_json', 'landlord_agent_id', 'estate_manager_id'
    ];

    protected $casts = [
        'form_json' => 'array',
    ];
}
