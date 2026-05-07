<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'title',
        'purpose',
        'type_id',
        'sub_type_id',
        'pricing_id',
        'details_id',
        'country',
        'state',
        'locality',
        'area',
        'street',
        'youtube_video_link',
        'instagram_video_link',
        'description',
    ];

    // Relationships

    public function type()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function subType()
    {
        return $this->belongsTo(PropertySubType::class);
    }

    public function detail()
    {
        return $this->hasOne(Detail::class);
    }

    public function pricing()
    {
        return $this->hasOne(Pricing::class);
    }


    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'property_feature');
    }

}
