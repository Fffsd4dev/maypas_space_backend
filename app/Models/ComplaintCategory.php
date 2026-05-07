<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintCategory extends Model
{
    protected $fillable = ['name', 'description', 'estate_manager_id'];

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }
}
