<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    protected $fillable = [
        'name',
        'manage_properties',
        'manage_accounts',
        'manage_admins',
        'manage_estate_manager'
    ];

    public function admins(){
        return $this->hasMany(Admin::class, 'role_id');
    }
}
