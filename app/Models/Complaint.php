<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'tenant_id', 'category_id', 'title', 'description', 'apartment_id',
        'status', 'priority', 'evidence', 'resolution_notes', 'landlord_agent_id', 'estate_manager_id'
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function category()
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function responses()
    {
        return $this->hasMany(ComplaintResponse::class);
    }
}
