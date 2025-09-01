<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaynamicsPayment extends Model
{
    use CrudTrait;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'paynamics_payments';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = [
        'payment_method_id',
        
        'firstname',
        'lastname',
        'email',
        'mobile',
        'address',
        'description',

        'amount',
        'fee',
        'tnh_fee',
        'is_inclusive',

        'pay_reference',
        'raw_data',
        'initial_response',

        'request_id',
        'response_id',
        'merchant_id',
        'expiry_limit',
        'direct_otc_info',
        'payment_action_info',
        'response',

        'timestamp',
        'rebill_id',
        'signature',
        'response_code',
        'response_message',
        'response_advise',
        'settlement_info_details',

        'mail_sent',
        'status'
    ];
    // protected $hidden = [];
    protected $dates = ['datetime', 'created_at'];
    protected $appends = ['payment_method_name', 'payment_method_logo', 'payment_from', 'total_amount', 'total_fee', 'received_amount'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function paymentMethod()
    {
        return $this->belongsTo(PaynamicsPaymentMethod::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'paynamics_payment_id');
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
    public function getPaymentMethodNameAttribute()
    {
        $paymentMethod = $this->paymentMethod()->first();
        return $paymentMethod ? $paymentMethod->name : null;
    }

    public function getPaymentMethodLogoAttribute()
    {
        $paymentMethod = $this->paymentMethod()->first();
        return $paymentMethod ? $paymentMethod->logo : null;
    }


    public function getPaymentFromAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getTotalAmountAttribute()
    {
        if( ! $this->is_inclusive ) {
            return (double)$this->amount;
        } else {
            return (double)$this->amount - (double)$this->fee - (double)$this->tnh_fee;
        }
    }

    public function getTotalFeeAttribute()
    {
        return (double)$this->fee + (double)$this->tnh_fee;
    }

    public function getReceivedAmountAttribute()
    {
        if( $this->is_inclusive == "0" ) {
            return (double)$this->amount;
        } else {
            return (double)$this->amount - (double)$this->fee - (double)$this->tnh_fee;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
