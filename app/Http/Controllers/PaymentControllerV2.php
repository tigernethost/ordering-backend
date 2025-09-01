<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaynamicsController;
use App\Models\PaynamicsPayment;
use App\Models\PaynamicsPaymentCategory;
use App\Models\PaynamicsPaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentControllerV2 extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET PAYMENT CATEGORIES
    |--------------------------------------------------------------------------
    */
    public function getPaymentCategories()
    {
        $paymentCategories = PaynamicsPaymentCategory::with(['paymentMethods' => function ($query) {
                $query->active();
                // $query->active()->select('id', 'payment_category_id', 'active');
            }])->whereRelation('paymentMethods', 'active', 1)
            ->active()
            ->get();

        return response()->json(['paymentCategories' => $paymentCategories]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENT METHOD
    |--------------------------------------------------------------------------
    */
    public function getPaymentMethod($id)
    {
        $paymentMethod = PaynamicsPaymentMethod::active()->where('id', $id)->first();
        return $paymentMethod ? response()->json($paymentMethod) : response()->json(['message' => 'Payment Method Not found'], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENT HISTORY
    |--------------------------------------------------------------------------
    */
    public function paymentHistory()
    {
        $user               =   auth()->user();
        $payment_histories  =   PaynamicsPayment::where('email', $user->email)->get(); 
        return response()->json(['payment_histories' => $payment_histories]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE PAYMENT QR
    |--------------------------------------------------------------------------
    */
    public function validatePaymentQR($slug)
    {
        $business = Business::where('slug', $slug)->published()->active()->first();
        if(! $business) { 
            return response()->json(['message' => 'Business Not Found.'], 404); 
        }

        $paybiz_wallet = $business->paybizWallet;
        if(! $paybiz_wallet) { 
            return response()->json(['message' => 'Business Online Payment Not Set'], 404); 
        }

        return response()->json($business);
        // return Business::generatePaymentQr($business->slug);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENT FEE
    |--------------------------------------------------------------------------
    */
    public function getFee(Request $request, $business_slug)
    {
        // Business
        $business = Business::where('slug', $business_slug)->published()->active()->first();
        if(! $business) { 
            return response()->json(['message' => 'Business Not Found.'], 404); 
        }

        // Paybiz Wallet
        $paybiz_wallet = $business->paybizWallet;
        if(! $paybiz_wallet) { 
            return response()->json(['message' => 'Business Online Payment Not Set'], 404); 
        }
        
        // Payment Method
        $paymentMethod  = PaynamicsPaymentMethod::where('id', $request->payment_method_id)->active()->first();
        if(! $paymentMethod) { 
            return response()->json(['message' => 'Payment Method Not Found.'], 404); 
        }

        $computeFee = $this->computeFee($request->amount, $paybiz_wallet->is_inclusive, $paymentMethod);

        return response()->json($computeFee);
    }

    public function computeFee($amount, $inclusive, $paymentMethod)
    {
        /** 
         * TOTAL AMOUNT COMPUTATION AND FEES ( EXCLUSIVE )
         */
        $tnh_fee_v2       = $amount * (env('TNH_MARKUP_PERCENT')/100);
                                
        $full_settlement        = $amount + $tnh_fee_v2;
        
        $total_fee        = number_format($this->getTotalTaxAndFee($full_settlement, $paymentMethod), 2, '.', '');

        if ($total_fee <= $paymentMethod->minimum_fee) {
            $final_fee              =  22.40;
            $amount_final_for_fee   =  5;

        }
        else {
            $final_fee              = $total_fee;
            $amount_final_for_fee   =  $amount * (env('TNH_MARKUP_PERCENT')/100);
        }

        $final_amount     = $amount + $amount_final_for_fee;
        $paynamics_fee    = $final_fee;
        $tnh_fee          = $amount_final_for_fee;
        $settlement_amount  = (double)$amount;
        $total_amount       = $final_amount + $final_fee;
        $total_fee          = $paynamics_fee;

        $data = [
            'total_amount'  => (double)$total_amount,
            'total_fee'     => (double)$paynamics_fee + (double)$tnh_fee,
        ];

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Compute Total Tax and Fee
    |--------------------------------------------------------------------------
    */
    private function getTotalTaxAndFee($full_settlement, $paymentMethod)
    {

        $fee_percent    = (double)$paymentMethod->fee * (12/100); //.27

        

        $total_tax      = (double)$paymentMethod->fee + $fee_percent; // 2.25 + .27 = 2.52
        // number_format((double)$this->amount + $this->total_fee, 2, '.', '')
        $total_with_tax = (double)($total_tax / 100);

       

        // $amount  =  ((double)$full_settlement / ($total_with_tax/100)) - (double)$full_settlement;
        $amount  =  (double)$full_settlement;
        
        $percent_total = (100 - $total_tax) / 100;
        
        $settlement_value = $amount / $percent_total;

        $pti_fee = $settlement_value - $amount;
    
        $min_fee = ((double)$paymentMethod->minimum_fee * (12/100)) + (double)$paymentMethod->minimum_fee;
        
        // return $pti_fee > (double)$this->paymentMethod->minimum_fee ? $pti_fee :  $min_fee ;
        return $pti_fee ;
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMIT PAYMENT
    |--------------------------------------------------------------------------
    */
    public function submitPayment($paymentData)
    {
        // Get The Selected Payment Method
        $paymentMethod  = PaynamicsPaymentMethod::where('id', $paymentData['payment_method_id'])->active()->first();

        //dd($paymentMethod->paynamicsPaymentCategory);

        if(!$paymentMethod) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors'  => ['payment_method_id' => 'The selected payment method is invalid.']
            ];

            return response()->json($response, 422);
        }

        if(!$paymentMethod->paynamicsPaymentCategory) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors'  => ['payment_method_id' => 'The selected payment method is invalid.']
            ];

            return $response;
        }

        if(! $paymentMethod->paynamicsPaymentCategory->active) {
            $response = [
                'message' => 'The given data was invalid.',
                'errors'  => ['payment_method_id' => 'The selected payment method is invalid.']
            ];
            return $response;
        }

        /** 
         * INITIALIZE THE PAYNAMICS DATA
         */

        $paynamics      = new PaynamicsController();
        $payment_data   = $paynamics->initialize($paymentMethod, $paymentData);


        //dd($payment_data);
        // Error Initialization
        if($payment_data['status'] != 'success') {
            if(! $payment_data['message'] ) {
                $response = [
                    'message' => 'Something went wrong, please reload the page.'
                ];
                return $response;
            }
            $response = [
                'message' => $payment_data['message']
            ];
            return $response;
        }


        /** 
         * CREATE PAYNAMICS PAYMENT
         */
        $createPayment = $paynamics->createPayment($payment_data['data']);

        //dd($createPayment);

        // Error Paynamics Payment Creation
        if($createPayment['status'] != 'success') {
            if(! $createPayment['message'] ) {
                $response = [
                    'http_code' => $createPayment['http_code'],
                    'message' => 'Something went wrong, please reload the page.'
                ];
                return $response;
            }
            $response = [
                'http_code' => $createPayment['http_code'],
                'status' => 'error',
                'message' => $createPayment['message']
            ];
            return $response;
        }

        $decoded_data = json_decode($createPayment['data']);
        

        /** 
         * PAYMENT INSTRUCTION // Check If Direct OTC Info is Array
         */
        if(isset($decoded_data->direct_otc_info)) {
            if(is_array($decoded_data->direct_otc_info)) {
                $payment_instructions = $decoded_data->direct_otc_info[0];

                $data = [
                    'paymentMethod'  => $paymentMethod,
                    'amount'         => $payment_data['amount'],
                    'paynamicsPayment'      => $decoded_data,
                    'payment_instructions'  => $payment_instructions
                ];

                try {
                    // Mail To Payer Email
                    ini_set('max_execution_time', 300);
                    //Mail::to($request->email)->send(new PaymentInstructionMail($data));
                }  catch (\Exception $e) {
                    \Log::error([
                        'title' => 'Payment Instruction Mail Error',
                        'email' => $request->email,
                        'date'  => Carbon::now()->format('F j, Y - h:i A'),
                        'error' => $e
                    ]);
                }
                $response = [
                    'status'        => 'success',
                    'http_code' => $createPayment['http_code'],
                    'message'       => $decoded_data->response_message,
                    'amount'        => $payment_data['amount'],
                    'instruction'   => $payment_instructions,
                    'request_id'    => $decoded_data->request_id,
                    'web_url'       => route('online_payment.instructions', ['request_id' => $decoded_data->request_id]),
                    'payment_id'    => $createPayment['payment_id']
                ];
                return $response;
                // return Redirect::route('online_payment.instructions', ['request_id' => $decoded_data->request_id]);
            }
            $response = [
                'status'        => 'success',
                'http_code' => $createPayment['http_code'],
                'message'       => $decoded_data->response_message,
                'amount'        => $payment_data['amount'],
                'request_id'    => $decoded_data->request_id,
                'web_url'       => $decoded_data->direct_otc_info,
                'payment_id'    => $createPayment['payment_id']
            ];

            return $response;
            // return redirect()->to($decoded_data->direct_otc_info);
        }

        if(isset($decoded_data->payment_action_info)) {
            if(is_array($decoded_data->payment_action_info)) {
                $payment_instructions = $decoded_data->payment_action_info[0];

                $data = [
                    'paymentMethod'  => $paymentMethod,
                    'amount'         => $payment_data['amount'],
                    'payment_instructions'  => $payment_instructions
                ];

                try {
                    // Mail To Payer Email
                    ini_set('max_execution_time', 300);
                    //Mail::to($request->email)->send(new PaymentInstructionMail($data));
                }  catch (\Exception $e) {
                    \Log::error([
                        'title' => 'Payment Instruction Mail Error',
                        'email' => $request->email,
                        'date'  => Carbon::now()->format('F j, Y - h:i A'),
                        'error' => $e
                    ]);
                }

                $response = [
                    'status'        => 'success',
                    'http_code' => $createPayment['http_code'],
                    'message'       => $decoded_data->response_message,
                    'amount'        => $payment_data['amount'],
                    'instruction'   => $payment_instructions,
                    'request_id'    => $decoded_data->request_id,
                    'web_url'       => route('online_payment.instructions', ['request_id' => $decoded_data->request_id]),
                    'payment_id'    => $createPayment['payment_id']
                ];

                return $response;
                // return Redirect::route('online_payment.instructions', ['request_id' => $decoded_data->request_id]);
            }
        }

        
        $response = [
            'status'        => 'success',
            'http_code' => $createPayment['http_code'],
            'message'       => $decoded_data->response_message,
            'amount'        => $payment_data['amount'],
            'web_url'       => $decoded_data->direct_otc_info ?? $decoded_data->payment_action_info,
            'request_id'    => $decoded_data->request_id,
            'payment_id'    => $createPayment['payment_id']
        ];

        return $response;
    }


    public function showInstructions($request_id)
    {

        $paynamicsPayment = PaynamicsPayment::where('request_id', $request_id)
                                ->where('response_code', 'GR033')
                                ->first();

        if(! $paynamicsPayment) {
            abort(404);
        }

        if(Carbon::parse($paynamicsPayment->expiry_limit)->format('F d, Y h:i A') < Carbon::now()->format('F d, Y h:i A')) {
            abort(404);
        }

        if($paynamicsPayment->is_inclusive) {
            $amount = (double)$paynamicsPayment->amount;
        } else {
            $amount = (double)$paynamicsPayment->amount + (double)$paynamicsPayment->fee + (double)$paynamicsPayment->tnh_fee;
        }

        $data = [
            'business'       => $paynamicsPayment->paymentable,
            'paymentMethod'  => $paynamicsPayment->paymentMethod,
            'amount'         => $amount,
            'paynamicsPayment'      => $paynamicsPayment
        ];

        $paynamicsPayment = json_decode($paynamicsPayment);

        /** 
         * PAYMENT INSTRUCTION // Check If Direct OTC Info is Array
         */
        if(isset($paynamicsPayment->direct_otc_info)) {
            if(is_array(json_decode($paynamicsPayment->direct_otc_info))) {
                $payment_instructions = json_decode($paynamicsPayment->direct_otc_info)[0];

                $data['payment_instructions'] = $payment_instructions;
                return view('v2.onlinePayments.payment_instructions')->with($data);
            }
        }

        if(isset($paynamicsPayment->payment_action_info)) {
            if(is_array(json_decode($paynamicsPayment->payment_action_info))) {
                $payment_instructions = json_decode($paynamicsPayment->payment_action_info)[0];

                $data['payment_instructions'] = $payment_instructions;
                return view('v2.onlinePayments.payment_instructions')->with($data);
            }
        }

        \Alert::success('Payment has been processed')->flash();
        return redirect()->to($paynamicsPayment->direct_otc_info ?? $paynamicsPayment->payment_action_info);
    }
}
