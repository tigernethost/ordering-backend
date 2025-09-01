<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LalamoveMetaData extends Model
{
    protected $table = 'lalamove_metadata';

    protected $fillable = [
        'location_name',
        'locode',
        'vehicle_key',
        'description',
        'load',
        'icon',
        'dimensions',
        'special_requests',
        'delivery_item_specification'
    ];

    protected $casts = [
        'load' => 'array',
        'dimensions' => 'array',
        'special_requests' => 'array',
        'delivery_item_specification' => 'array',
    ];
    
    
    
}
