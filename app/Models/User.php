<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',

        //Personal Info
        'first_name',
        'middle_name',
        'last_name',
        'dob',
        'gender',

        //Basic Details
        'nationality',
        'state',
        'address',
        'identity_card',
        'passport_photo',

        //contact Details
        'phone',
        'other_phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_email',
        
        //Next Of Kin
        'next_of_kin_name',
        'next_of_kin_address',
        'next_of_kin_email',
        'next_of_kin_number',

        //Other
        'email_verified_at',
        'deactivated',
        'estate_manager_id',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'id',
    ];

    //automatically generate UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                // Generate a UUID and trim it to 10 characters
                $model->uuid = (string) Str::uuid();
            }
        });

    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }

    public function documents()
    {
        return $this->hasMany(OtherDocument::class, 'tenant_id');
    }

}
