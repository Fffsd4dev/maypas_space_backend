<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentSigningRequest extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'document_id',
        'signed',
        'estate_manager_id'
    ];

    protected $hidden = [
        'id'
    ];

    public function document()
    {
        return $this->belongsTo(UnsignedDocument::class, 'document_id');
    }

    public function estateManager()
    {
        return $this->belongsTo(EstateManager::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

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


