<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;


class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'role_id',
        'password',
    ];

    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    protected $hidden = [
        'id',
        'password',
        'remember_taken',
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
