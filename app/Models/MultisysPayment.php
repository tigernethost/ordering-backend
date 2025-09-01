<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultisysPayment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'payment_method_id',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'description',
        'billing_address_line1',
        'billing_address_city',
        'billing_address_state',
        'billing_address_zip_code',
        'billing_address_country_code',
        'txnid',
        'amount',
        'digest',
        'callback_url',
        'initial_amount',
        'url',
        'refno',
        'mpay_refno',
        'transaction_date',
        'payment_channel',
        'payment_channel_branch',
        'raw_data',
        'initial_response',
        'response',
        'response_code',
        'response_message',
        'mail_sent',
        'status',
    ];

    // protected $appends = ['payment_method_name'];


    // Relations



    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'payment_id');
    }

    // accessors

    public function getPaymentMethodNameAttribute()
    {
        //dd($this->paymentMethod);
        return $this->paymentMethod !== null ? $this->paymentMethod->name : ' - ';
    }
}
