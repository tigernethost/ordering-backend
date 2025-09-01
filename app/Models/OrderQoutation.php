<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderQoutation extends Model
{
    use SoftDeletes;

    protected $table = 'order_qoutations';
    protected $fillable = [
        'order_id',
        'quotation',
        'sender_stop_id',
        'recipient_stop_id',
        'service_type',
        'distance_value',
        'distance_unit',
        'price_total',
        'currency',
        'schedule_at',
        'expires_at',
        'status',
        'response_payload',
        'raw_body'
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }


}
