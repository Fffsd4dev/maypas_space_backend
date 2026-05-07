<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantDocument extends Model
{
    protected $fillable = [
        'uuid', 'document', 'for', 'apartment_id', 'status', 'signed_document_json', 'submitted_at', 'landlord_agent_id', 'estate_manager_id'
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
}
