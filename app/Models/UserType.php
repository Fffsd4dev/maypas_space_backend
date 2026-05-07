<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    protected $fillable = [
        'name',
        'estate_manager_id',
        'admin_management',
        'user_management',
        'complaint_management'
    ];

    public function landlordAgent()
    {
        return $this->hasMany(User::class, 'user_type_id');
    }
}
