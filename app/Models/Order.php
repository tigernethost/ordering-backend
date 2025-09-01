<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'orders';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['order_id', 'customer_id', 'payment_id', 'paynamics_payment_id', 'branch_id', 'branch_order_interval_id', 'total_amount', 'delivery_fee', 'status', 'order_type', 'order_note'];
    protected $appends = ['order_payment_status', 'for_reservation', 'customer_name'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            $order->order_id = self::generateOrderId($order->id);
            $order->save();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function generateOrderId($orderId)
    {
        $randomChars = strtoupper(Str::random(6));
        return $orderId . '-' . $randomChars;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddress::class);
    }

    public function multisysPayment()
    {
        return $this->belongsTo(MultisysPayment::class, 'payment_id');
    }

    public function paynamicsPayment()
    {
        return $this->belongsTo(PaynamicsPayment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderInterval()
    {
        return $this->belongsTo(BranchOrderIntervals::class, 'branch_order_interval_id');
    }

    public function reservation()
    {
        return $this->hasOne(OrderReservation::class, 'order_id');
    }

    public function orderQuotation()
    {
        return $this->hasOne(OrderQoutation::class);
    }

    public function lalamovePlacedOrder()
    {
        return $this->hasOne(LalamovePlaceOrder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getOrderPaymentStatusAttribute()
    {
        $multisys = $this->multisysPayment()->first();

        return $multisys ? $multisys->status : 'Pending';
        
    }

    public function getForReservationAttribute()
    {
        $reservation = $this->reservation()->first();

        return $reservation ? 1 : 0;
    }

    /**
     * Accessor for the `customer_name` attribute.
     * Combines first_name and last_name to return the full name of the customer.
     *
     * @return string
     */
    public function getCustomerNameAttribute()
    {
        if ($this->customer) {
            return "{$this->customer->first_name} {$this->customer->last_name}";
        }

        return 'Guest';
    }

    public function getBranchNameAttribute()
    {
        return $this->branch ? $this->branch->name : 'Unknown Branch';
    }
    


    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
