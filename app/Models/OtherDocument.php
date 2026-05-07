<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherDocument extends Model
{
    protected $fillable = [
        'name',
        'filename',
        'tenant_id',
    ];

    public function tenant(){
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
