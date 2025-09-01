<?php

namespace App\Http\Controllers;

use App\Models\PaymentCategory;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::active()->get();
        $categories = PaymentCategory::active()->get();

        // Pass the data to the view
        return view('checkout.index', compact('paymentMethods', 'categories'));
    }

    public function submit(Request $request)
    {      
        $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)->active()->first();

        /** 
         * CREATE MULTISYS PAYMENT
        */

        $data = [
            'payment_method_id' => $request->payment_method_id,
            'customer_id' => 1,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'formatted_mobile' => $request->mobile,
            'description' => $request->description,
            'amount' => $request->amount,
            "billing_address_line1" => $request->billing_address_line1,
            "billing_address_city" => $request->billing_address_city,
            "billing_address_state" => $request->billing_address_state,
            "billing_address_zip_code" => $request->billing_address_zip_code,
            "billing_address_country_code" => 'PH',
            "redirect_url" => $request->redirect_url
        ];

        //dd($data);

        $multisys = new MultisysPaymentController();
        $txnid = $this->generateTxnid();
        $multisysUrl = env('MULTISYS_URL') . $paymentMethod->url;
        
        //$multisysUrl = env('MULTISYS_URL') . 'generate';

        $payment = $multisys->createPayment($data, $txnid, $multisysUrl); // pass data and txnid


        // dd($payment, $multisysUrl);
        return $payment;
    }


    private function generateTxnid()
    {
        $date = Carbon::now()->format('ymd');
        $randomNumber = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    
        $txnid = 'MAKIMURA' . '-' . $date . '-' . $randomNumber;
    
        return $txnid;
    }

    public function getPaymentMethod($id)
    {
        $paymentMethod = PaymentMethod::active()->findOrFail($id);
        return $paymentMethod;
    }

}
