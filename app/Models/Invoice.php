<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Invoice extends Model
{
    protected $fillable = [
        'amount',
        'user_id',
        'apartment_unit_id',
        'rent_cycle_id',
        'invoice_uuid',
    ];
    //
    protected $hidden = [
        'created_at',
        'updated_at',
        'id',
        'user_id',
        'apartment_unit_id',
        'rent_cycle_id',
    ];
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                // Generate a UUID and trim it to 10 characters
                $model->uuid = (string) Str::uuid();
            }
        });


    }
    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'first_name', 'last_name', 'email', 'uuid');
    }
    public function apartmentUnit()
    {
        return $this->belongsTo(ApartmentUnit::class, 'apartment_unit_id', 'id')->select('apartment_unit_name', 'apartment_id', 'updated_at', 'uuid','id');
    }
    public function paymentInfos()
    {
        return $this->hasMany(PaymentInfo::class,'invoice_id','id')->select('apartment_unit_id','payment_name','payment_fee','created_at','invoice_id') ;
    }

}
