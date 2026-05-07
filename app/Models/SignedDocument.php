<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SignedDocument extends Model
{
    protected $fillable = [
        'uuid',
        'document_id',
        'tenant_id',
        'filename',
        'signer_name',
        'ip_address',
        'estate_manager_id'
    ];

    protected $hidden = [
        'id'
    ];

    public function document()
    {
        return $this->belongsTo(UnsignedDocument::class, 'document_id');
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
