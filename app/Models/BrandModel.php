<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandModel extends Model
{
    //
    protected $table = 'brand_models';
    protected $fillable =[
        'name',
        'addresses',
        'phones',
        'social_links',
        'logo',
        'estate_manager_id',

    ];
}
