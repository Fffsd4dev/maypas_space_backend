<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $fillable = ['name'];

    public function technicians()
    {
        return $this->hasMany(Technician::class);
    }
}
