<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionTracker extends Model
{
    use SoftDeletes; // enables deleted_at

    // Table name (optional if follows Laravel convention)
    protected $table = 'subscription_trackers';

    // Fillable fields for mass assignment
    protected $fillable = [
        'plan_id',
        'estate_manager_id',
        'created_by_admin_id',
        'status'
,        'start_date',
        'end_date',
    ];

    // Dates automatically cast to Carbon instances
    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships

    /**
     * SubscriptionTracker belongs to a Plan
     */
    public function SubscriptionPlan()
    {
        return $this->belongsTo(SubscriptionModel::class, 'plan_id');
    }

    /**
     * SubscriptionTracker belongs to an Estate Manager
     */
    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class, 'estate_manager_id');
    }

    /**
     * SubscriptionTracker was created by a user
     */
    public function createdByUser()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}