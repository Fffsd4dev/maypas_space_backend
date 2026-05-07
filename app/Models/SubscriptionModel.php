<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionModel extends Model
{
    protected $table = 'subscription_models';

    protected $fillable = [
        'name',
        'number_of_staff',
        'number_of_admins',
        'number_of_agents',
        'number_of_apartments',
        'number_of_branches',
        'number_of_locations',
        'fee',
        'discount',
        'created_by_admin_id',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Return pricing breakdown as array
    public function getPricingAttribute()
    {
        $priceBeforeDiscount = (float) $this->fee;
        $discountAmount = ($this->discount / 100) * $priceBeforeDiscount;
        $priceAfterDiscount = round($priceBeforeDiscount - $discountAmount, 2);

        return [
            'price_before_discount' => $priceBeforeDiscount,
            'discount_percentage' => (float) $this->discount,
            'discount_amount' => round($discountAmount, 2),
            'price_after_discount' => $priceAfterDiscount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}