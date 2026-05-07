<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordOtp extends Model
{
    protected $fillable = ['landlord_agent_id', 'code', 'type', 'expires_at'];

    protected $dates = ['expires_at'];

    public function user()
    {
        return $this->belongsTo(LandlordAgent::class);
    }

    public function isExpired()
    {
        return now()->greaterThan($this->expires_at);
    }
}
