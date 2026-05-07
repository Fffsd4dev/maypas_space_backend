<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    protected $fillable = [
        'user_id',
        'guarantor_full_name',
        'guarantor_address',
        'guarantor_email',
        'guarantor_number',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
