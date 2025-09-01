<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderReservation extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'order_reservations';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['order_id', 'branch_id', 'order_interval_id', 'reservation_date', 'status', 'is_cancelled', 'cancelled_at'];
    // protected $hidden = [];
    protected $casts = [
        'reservation_date' => 'datetime',
    ];
    protected $appends = ['customer_name'];



    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItems()
    {
        // Access `order_items` via the `order` relationship
        return $this->hasManyThrough(OrderItem::class, Order::class, 'id', 'order_id', 'order_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderInterval()
    {
        return $this->belongsTo(BranchOrderIntervals::class);
    }


    public function getCustomerNameAttribute()
    {
        if ($this->order && $this->order->customer) {
            return "{$this->order->customer->first_name} {$this->order->customer->last_name}";
        }

        return 'Guest'; // Default if no customer is associated
    }
}
