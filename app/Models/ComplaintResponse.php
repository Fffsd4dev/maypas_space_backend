<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintResponse extends Model
{
    protected $fillable = ['complaint_id', 'landlord_id', 'message', 'attachment', 'estate_manager_id'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function landlord()
    {
        return $this->belongsTo(LandlordAgent::class, 'landlord_id');
    }
}
