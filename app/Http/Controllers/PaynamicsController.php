<?php

namespace App\Http\Controllers;

use App\Models\PaynamicsPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Helpers
use Illuminate\Support\Str;

class PaynamicsController extends Controller
{
    private $code;
    private $business;
    private $paymentMethod;
    private $paybizWallet;

    private $tnh_fixed_markup;
    private $tnh_percent_markup;
    private $tnh_tax_and_fee;

    private $data;
    private $request;

    private $merchantId;
    private $merchantKey;

    private $fullname;
    private $firstname;
    private $lastname;
    private $address;
    private $email;
    private $mobile;
    private $description;
    private $amount;
    private $total_fee;
    private $total_amount;

    private $tnh_fee;
    private $paynamics_fee;
    private $paynamics_percent;
    private $paynamics_minimum;

    private $inclusive;

    private $response_codes = [];

    /*
    |--------------------------------------------------------------------------
    | PANAMICS PAYMENT DATA INITIALIZATION
    |--------------------------------------------------------------------------
    */
    public function initialize( $paymentMethod, $request)
    {
        $response = [
            'status'  => null,
            'message' => null,
            'data'    => null
        ];
       
        $this->code          = Str::random(6);
        // $this->business      = $business;
        // $this->paybizWallet  = $business->paybizWallet;
        $this->paymentMethod = $paymentMethod;
        $this->request       = $request;

        $this->inclusive     = env('PAYNAMIC_INCLUSIVE');

        //dd($this->inclusive);
        /** 
         * GET PAYER INFORMATION
         */
        $this->fullname      = $request['first_name'] . ' ' . $request['last_name'];

        /** 
         * PAYNAMICS REQUIRED DATA
         */
        $this->order       = $request['order'];

        $this->request_id    = $this->generateRequestId();
        $this->merchant_id   = env('PAYNAMIC_MERCHANT_ID');
        $this->merchant_key  = env('PAYNAMIC_MERCHANT_KEY');
        
        $this->biz_wallet_id     = env('PAYNAMIC_BIZ_WALLET_ID');
        $this->tnh_biz_wallet_id = env('PAYNAMIC_TNH_BIZ_WALLET_ID');

        $this->tnh_fixed_markup     = env('TNH_MARKUP_FIXED');
        $this->tnh_percent_markup   = env('TNH_MARKUP_PERCENT');
        
        $this->settlement_id    = $this->generateSettlementId();
        $this->settlement_id_2  = $this->generateSettlementId2();

       
        /** 
         * TOTAL AMOUNT COMPUTATION AND FEES ( EXCLUSIVE )
         */
        if(! $this->inclusive ) {
            $this->amount           = $this->request['amount'];
            $this->tnh_fee_v2       = $this->request['amount'] * (env('TNH_MARKUP_PERCENT')/100);
                                
            $full_settlement        = $this->amount + $this->tnh_fee_v2;

            
            $this->total_fee        = number_format($this->getTotalTaxAndFee($full_settlement), 2, '.', '');
            

            if ($this->total_fee <= $this->paymentMethod->minimum_fee) {
                $final_fee              =  22.40;
                $amount_final_for_fee   =  5;

            }
            else {
                $final_fee              = $this->total_fee;
                $amount_final_for_fee   =  $this->request['amount'] * (env('TNH_MARKUP_PERCENT')/100);
            }

            $final_amount           = $this->amount + $amount_final_for_fee;

            
            $this->paynamics_fee    = $final_fee;

            $this->tnh_fee          = $amount_final_for_fee;

            $this->settlement_amount = (double)$request['amount'];

            $this->total_amount = $final_amount + $final_fee;

            $this->total_fee = $this->paynamics_fee;
        }

        

        /** 
         * TOTAL AMOUNT COMPUTATION AND FEES ( INCLUSIVE )
         */
        if( $this->inclusive ) {
            $this->amount           = $this->request['amount'];
            $this->tnh_fee_v2       = $this->request['amount'] * (env('TNH_MARKUP_PERCENT')/100);
                                 
            $full_settlement        = $this->amount + $this->tnh_fee_v2;

            
            $this->total_fee        = number_format($this->getTotalTaxAndFee($full_settlement), 2, '.', '');

            
            if ($this->total_fee <= 20) {
                $final_fee          =   22.40;
                $amount_final_for_fee   =  5;

            }
            else {
                $final_fee          = $this->total_fee;
                $amount_final_for_fee   =  $this->request['amount'] * (env('TNH_MARKUP_PERCENT')/100);
            }

            // dd($final_fee);
            $final_amount           = $this->amount;

            
            $this->paynamics_fee    = $final_fee;

            $this->tnh_fee          = $amount_final_for_fee;

            $this->settlement_amount = (double)$request['amount'] - (double)$this->paynamics_fee - (double)$this->tnh_fee;
        

            $this->total_amount = $final_amount;

            $this->total_fee = $this->paynamics_fee;
        }

        /** 
         * TESTING COMPUTATION
         */
        $computations = [
            'amount'        => $this->amount ,
            'total_fee'     => $this->paynamics_fee + $this->tnh_fee,
            'total_amount'  => $this->total_amount,
            'paynamics_fee' => $this->paynamics_fee,
            'tnh_fee'       => $this->tnh_fee,
            'is_inclusive'     => $this->inclusive ? 'Inclusive' : 'Exclusive',
        ];
        // dd($computations);
        // dd($computations);

        /** 
         * TRANSACTION INFORMATION
         */
        $_mid       = $this->merchant_id; //<-- your merchant id
        $_requestid = $this->request_id;
        $_pchannel  = $this->getPaymentChannel();
        $_ipaddress = env("SERVER_IP");
        // $_descnote  = $this->paybizWallet->descriptor_note;
        $_descnote  = 'MAKIMURA';
        // $_descnote  = $this->business->name;

        // $_noturl    = route('online_payment.webhook_notification'); //url of paynamics webhook notification
        // $_resurl    = route('online_payment.webhook_response'); //url of paynamics webhook response
        // $_cancelurl = route('online_payment.webhook_cancel'); //url of paynamics webhook cancel

        $_noturl    = env("APP_URL") . "/online-payment/paynamics/notification"; //url of paynamics webhook notification
        $_resurl    = env('ORDER_CONFIRMATION_URL') . '/order-confirmation/' . $this->request_id; //url of paynamics
        $_cancelurl = env("APP_URL") . "/online-payment/paynamics/cancel"; //url of paynamics webhook cancel

        $_fee       = $this->total_fee; // Total Fee
        $_amount    = ! $this->inclusive ? $this->total_amount : (double)number_format($this->amount, 2, '.', ''); // kindly set this to the total amount of the transaction. Set the amount to 2 decimal point before generating signature.
        
        // SPLIT FEE
        $paynamics_fee  = $this->paynamics_fee; // Paynamics Fee
        $tnh_fee        = $this->tnh_fee; // TNH Fee / Markup

        // dd($_amount, $_fee, $paynamics_fee, $tnh_fee);

        $_currency  = "PHP"; //PHP or USD

        // $_mlogo_url = config('settings.logo');
        // $_mtac_url  = route('privacy');

        $_mlogo_url = 'test';
        $_mtac_url  = 'TEST';

        $_pmethod           = $this->paymentMethod->paynamicsPaymentCategory->method;
        $_trx_type          = 'sale';
        $_payment_action    = $this->paymentMethod->paynamicsPaymentCategory->action; // PLACE IN DATABASE COLUMN PAYMENT ACTION
        $_collection_method = 'single_pay';
        $_payment_notification_status  = '1';
        $_payment_notification_channel = '0';

        /** 
         * PAYER INFORMATION
         */
        $_fname     = $request['first_name']; // kindly set this to first name of the customer
        $_mname     = ""; // kindly set this to middle name of the cutomer
        $_lname     = $request['last_name']; // kindly set this to last name of the cutomer

        /** 
         * BILLING/SHIPPING INFORMATION
         */
        $_addr1     = $request['address'];// kindly set this to address2 of the cutomer
        //dd($request);
        $_addr2     = $request['email']; // kindly set this to address1 of the cutomer
        $_city      = $request['billing_address_city']; // kindly set this to city of the cutomer
        $_state     = $request['billing_address_state']; // kindly set this to state of the cutomer
        $_country   = 'PH'; // kindly set this to country of the cutomer
        $_zip       = $request['billing_address_zip_code']; // kindly set this to zip/postal of the cutomer
        $_sec3d     = "try3d"; // 
        $_email     = $request['email']; // kindly set this to email of the cutomer
        $_phone     = $request['mobile']; // EXAMPLE ONLY kindly set this to phone number of the cutomer
        $_mobile    = $request['mobile']; // kindly set this to mobile number of the cutomer
        $_clientip  = $this->getClientIp();
        $_dob       = "12-01-1986";

        /**
         * SETTLEMENT INFORMATION (Settlement To Business)
         */
        $_biz_wallet_id       = $this->biz_wallet_id;
        $_settlement_id       = $this->settlement_id;
        $_settlement_amount   = $this->settlement_amount; // THE GROSS AMOUNT THAT THE PARENT OR STUDENT ENTERED
        $_settlement_currency = 'PHP';
        $_reason              =  'Makimura settlement';

        /**
         * SETTLEMENT INFORMATION 2 (Settlement To TNH)
         */
        $_biz_wallet_id_2       = $this->tnh_biz_wallet_id;
        $_settlement_id_2       = $this->settlement_id_2;
        $_settlement_amount_2   = (double)$tnh_fee; // TNH FEE / MARKUP
        $_settlement_currency_2 = 'PHP';
        $_reason_2              = 'Makimura - TNH Transaction Fee';

        /**
         * PAYER SIGNATURE
         */
        $forSign    = $_fname . $_lname . $_mname . $_email . $_phone . $_mobile . $_dob . $this->merchant_key;
        $_signature      = hash("sha512", $forSign);

        /**
         * TRANSACTION SIGNATURE
         */
        $_rawTrx = $this->merchant_id . $this->request_id . $_noturl . $_resurl . $_cancelurl . $_pmethod . $_payment_action . $_collection_method . $_amount . $_currency . $_descnote . $_payment_notification_status . $_payment_notification_channel . $this->merchant_key;
        $_signatureTrx = hash("sha512", $_rawTrx);

        /**
         * SETTLEMENT SIGNATURE
         */
        $_rawSttlmnt         = $_biz_wallet_id . $_settlement_amount . $_settlement_currency . $_reason . $_settlement_id . $this->merchant_key;
        $_signatureStlmnt    = hash("sha512", $_rawSttlmnt);

        /**
         * SECOND SETTLEMENT (2) SIGNATURE
         */
        $_rawSttlmnt_2         = $_biz_wallet_id_2 . $_settlement_amount_2 . $_settlement_currency_2 . $_reason_2 . $_settlement_id_2 . $this->merchant_key;
        $_signatureStlmnt_2    = hash("sha512", $_rawSttlmnt_2);

        // \Log::info([
        //     'raw_payer'     => $forSign,
        //     'payer_sig'     => $_signature,
        //     'raw_trx'       => $_rawTrx,
        //     'trx_sig'       => $_signatureTrx,
        //     'raw_sttlmnt'   => $_rawSttlmnt,
        //     'sttlmnt_sig'   => $_signatureStlmnt,
        //     'raw_sttlmnt2'  => $_rawSttlmnt_2,
        //     'sttlmnt2_sig'  => $_signatureStlmnt_2,
        // ]);


        /**
         * DATA TO SEND TO PAYNAMICS
         */
        $data = array(
            'transaction' => 
            array(
              'request_id'       => $_requestid,
              'notification_url' => $_noturl,
              'response_url'     => $_resurl,
              'cancel_url'       => $_cancelurl,
              'pmethod'          => $_pmethod,
              'pchannel'         => $_pchannel, //Check documentation page 156
              'payment_action'   => $_payment_action, //Check documentation page 156
              'collection_method' => $_collection_method,
              'payment_notification_status'  => $_payment_notification_status,
              'payment_notification_channel' => $_payment_notification_channel,
              'mlogo_url' => asset($_mlogo_url), // Logo
              'amount'    => strval($_amount), 
              'currency'  => $_currency,
              'descriptor_note'  => $_descnote,
              'trx_type'  => $_trx_type,
              'mtac_url'  => $_mtac_url, // Check tigernet website https://tigernethost.com/tnc.php
              'signature' => $_signatureTrx,
            ),
            'billing_info' => 
            array(
              'billing_address1' => $_addr1 ? $_addr1 : "",
              'billing_address2' => $_addr2 ? $_addr2 : "",
              'billing_city'     => $_city ? $_city : "",
              'billing_state'    => $_state? $_state : "",
              'billing_country'  => $_country ? $_country : "PH",
              'billing_zip'      => $_zip ? $_zip : "",
            ),
            'shipping_info' => 
            array(
              'fname'  => $_fname,
              'lname'  => $_lname,
              'mname'  => $_mname,
              'email'  => $_email,
              'phone'  => $_phone,
              'mobile' => $_mobile,
              'dob'    => '12-01-1986',
            ),
            'customer_info' => 
            array(
              'fname'  => $_fname,
              'lname'  => $_lname,
              'mname'  => $_mname,
              'email'  => $_email,
              'phone'  => $_phone,
              'mobile' => $_mobile,
              'dob'    => '12-01-1986',
              'signature' => $_signature,
            ),
            'settlement_information' => 
            array(
              array(
                'biz_wallet_id'       => $_biz_wallet_id,
                'settlement_amount'   => strval($_settlement_amount),
                'settlement_currency' => $_settlement_currency,
                'reason'              => $_reason,
                'settlement_id'       => $_settlement_id,
                'signature'           => $_signatureStlmnt,
              ),
              array(
                'biz_wallet_id'       => $_biz_wallet_id_2,
                'settlement_amount'   => strval($_settlement_amount_2),
                'settlement_currency' => $_settlement_currency_2,
                'reason'              => $_reason_2,
                'settlement_id'       => $_settlement_id_2,
                'signature'           => $_signatureStlmnt_2,
              )
            ),
            'order_details' => 
            array(
              'orders' => [
                [
                  'itemname'   => 'Payment for ' . 'Makimura', //CHANGE TPO "PAYMENT FOR SY 2019-2020 200001"
                  'quantity'   => 1,
                  'unitprice'  => strval($_settlement_amount), //TOTAL AMOUNT THE PAYER ENTERED
                  'totalprice' => strval($_settlement_amount), //TOTAL AMOUNT THE PAYER ENTERED
                ],
                [
                //   'itemname'   => $this->inclusive ? 'Less: TNH Fee'  : 'TNH Fee',
                  'itemname'   => 'Convenience Fee',
                  'quantity'   => 1,
                  'unitprice'  => strval($this->tnh_fee), //TOTAL AMOUNT THE PAYER ENTERED
                  'totalprice' => strval($this->tnh_fee), //TOTAL AMOUNT THE PAYER ENTERED
                ],
                [
                //   'itemname'   => $this->inclusive ? 'Less: PTI Fee'  : 'PTI Fee',
                  'itemname'   => 'PTI Fee',
                  'quantity'   => 1,
                  'unitprice'  => strval($_fee), // GET FEE BASED ON THE DOCUMENTATION
                  'totalprice' => strval($_fee),
                ],
              ],
                
             
              'subtotalprice'    => strval( !$this->inclusive ? $this->total_amount : number_format($this->amount, 2, '.', '')),
              'shippingprice'    => '0.00',
              'discountamount'   => '0.00',
              'totalorderamount' => strval( !$this->inclusive ? $this->total_amount : number_format($this->amount, 2, '.', '')),
            ),
        );
        
        // \Log::info([
        //     'title' => 'Payment Initialization',
        //     'data'  => $data]
        // );


       

        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        // dd($data, 'Done Initialization!');
        $response = [
            'status'  => 'success',
            'message' => 'Payment Data is Created',
            'data'    => $data,
            'amount'  => $_amount,
        ];

        return $response;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYMENT
    |--------------------------------------------------------------------------
    */
    public function createPayment($payment_data)
    {
        $response = [
            'status'  => null,
            'message' => null,
            'data'    => null,
        ];

        $decoded_payment_data = json_decode($payment_data);
        // dd($payment_data);
        // dd(env('PAYNAMIC_URL'), env('PAYNAMIC_USERNAME'), env('PAYNAMIC_PASSWORD'));
        /**
         * Send Data to Paynamics API
         */

        // dd($decoded_payment_data);
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, env('PAYNAMIC_URL'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            ));
            curl_setopt($ch, CURLOPT_USERPWD, env('PAYNAMIC_USERNAME') . ":" . env('PAYNAMIC_PASSWORD'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payment_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $server_output = curl_exec($ch);
            $http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close ($ch); 

            $response_data = json_decode($server_output);

            // dd($response_data);
            // \Log::info($server_output);

            
            // dd($response_data);
            if($http_code != 200) {
                $response = [
                    'status'  => 'error',
                    'message' => 'Response Code: ' . $http_code,
                    'data'    => null,
                    'http_code' => $http_code
                ];
                // \Log::error([
                //     'title' => 'Creating Payment Error',
                //     'response_code' => $http_code,
                //     'response_data' => $response_data
                // ]);
            }
            else {
                // \Log::info([
                //     'title' => 'Payment Created',
                //     'response_code' => $http_code,
                //     'response_data' => $response_data
                // ]);
            }
            
        }  catch (\Exception $e) {
            // abort(404, $ex);
            $response = [
                'status'  => 'Creating Payment Error',
                'message' => $ex,
                'data'    => null,
                'http_code' => $http_code
            ];
            return $response;
        }


        $timestamp               = Carbon::now()->format('Y-m-d H:i:s');
        $direct_otc_info         = "";
        $payment_action_info     = "";
        $settlement_info_details = "";

        $response['message'] = $response_data->response_code . ' - ' . $response_data->response_message;


        if( in_array($response_data->response_code, ['GR001', 'GR002', 'GR033']) ) 
        {
            /**
             * Validate Paynamics Respose
             */
            if(isset($response_data->direct_otc_info)) {
                $direct_otc_info        =   is_array($response_data->direct_otc_info) 
                                                ? json_encode($response_data->direct_otc_info) 
                                                : $response_data->direct_otc_info;
            }
        
            if(isset($response_data->payment_action_info)) {
                $payment_action_info    =   is_array($response_data->payment_action_info) 
                                                ? json_encode($response_data->payment_action_info) 
                                                : $response_data->payment_action_info;
            }
        
            if(isset($response_data->direct_otc_info)) {
                if($response_data->direct_otc_info != "") {
                    $response = [
                        'status'  => 'success',
                        'message' => 'Payment has been successfully made.',
                        'data'    => $server_output,
                        'http_code' => $http_code
                    ];
        
                } 
                else {
                    $response = [
                        'status'  => 'error',
                        'message' => 'No DIRECT OTC HAS BEEN MADE',
                        'data'    => null,
                        'http_code' => $http_code
                    ];
                }
            } 
            else if(isset($response_data->payment_action_info)) {
                if($response_data->payment_action_info != "") {
                    $response = [
                        'status'  => 'success',
                        'message' => 'Payment has been successfully made.',
                        'data'    => $server_output,
                        'http_code' => $http_code
                    ];
        
                } 
                else {
                    $response = [
                        'status'  => 'error',
                        'message' => 'No DIRECT OTC or PAYMENT ACTION HAS BEEN MADEE',
                        'data'    => null,
                        'http_code' => $http_code
                    ];
                }
        
            } 
            else {
                $response = [
                    'status'  => 'error',
                    'message' => 'No DIRECT OTC or PAYMENT ACTION HAS BEEN MADE',
                    'data'    => null,
                    'http_code' => $http_code
                ];
            }

            $settlement_info_details    = json_encode($response_data->settlement_info_details);

            if(isset($response_data->timestamp)) {
              $timestamp = Carbon::parse($response_data->timestamp)->format('Y-m-d H:i:s');
            }
            else {
              $timestamp = Carbon::now()->format('Y-m-d H:i:s');
            }
            
        }
        
        // dd($response_data);
        /**
         * Save Paynamics Payment ( Response )
         */
        $paynamicsPayment = PaynamicsPayment::create([
            'payment_method_id' => $this->paymentMethod->id,

            'firstname'     => $this->request['first_name'],
            'lastname'      => $this->request['last_name'],
            'email'         => $this->request['email'],
            'mobile'        => $this->request['mobile'],
            'address'       => $this->request['address'],
            'description'   => $this->request['description'],

            'amount' => $this->amount,
            'fee'    => $this->total_fee,
            'tnh_fee'       => $this->tnh_fee,
            'is_inclusive'  => $this->inclusive,

            'raw_data'          => $payment_data,
            'initial_response'  => $server_output,

            'request_id'    => $response_data->request_id,
            'response_id'   => $response_data->response_id,
            'merchant_id'   => isset($response_data->merchant_id) ? $response_data->merchant_id : '-',
            'expiry_limit'  => isset($response_data->expiry_limit) ? $response_data->expiry_limit : 'NULL',
            'direct_otc_info'       => $direct_otc_info,
            'payment_action_info'   => $payment_action_info,
            'response' => $server_output,

            'timestamp' => $timestamp,
            'signature' => isset($response_data->signature) ? $response_data->signature : '-',
            'response_code'     => $response_data->response_code,
            'response_message'  => $response_data->response_message,
            'response_advise'   => $response_data->response_advise,
            'settlement_info_details' => $settlement_info_details,

            'status' => $response_data->response_code === 'GR033' ? 'PENDING' : 'CREATED',
        ]);

        $response['payment_id'] = $paynamicsPayment->id;

        return $response;  
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Request ID
    | FORMAT => ONE - Business Formatted ID - Date - Code (ONE-00001-211104-DNWJAR)
    |--------------------------------------------------------------------------
    */
    private function generateRequestId()
    {
        $date       = Carbon::now()->format('ymd');
        $custom_id  = Str::padLeft($this->order->id, 5, '0');

        $request_id = 'ONE-' . $custom_id . '-' . $date . '-' . $this->code;
        return $request_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Settlement ID
    | FORMAT => ONESETTLEMENT - Business Formatted ID - Date - Code (ONESETTLEMENT-00001-211104-DNWJAR)
    |--------------------------------------------------------------------------
    */
    private function generateSettlementId()
    {
        $date       = Carbon::now()->format('ymd');
        $custom_id  = Str::padLeft($this->order->id, 5, '0');

        $settlement_id = 'ONESETTLEMENT-' . $custom_id . '-' . $date . '-' . $this->code;
        return $settlement_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Settlement ID 2
    | FORMAT => ONESETTLEMENT2 - Business Formatted ID - Date - Code (ONESETTLEMENT-00001-211104-DNWJAR)
    |--------------------------------------------------------------------------
    */
    private function generateSettlementId2()
    {
        $date       = Carbon::now()->format('ymd');
        $custom_id  = Str::padLeft($this->order->id, 5, '0');

        $settlement_id = 'ONESETTLEMENT2-' . $custom_id . '-' . $date . '-' . $this->code;
        return $settlement_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Compute Total Tax and Fee
    |--------------------------------------------------------------------------
    */
    private function getTotalTaxAndFee($full_settlement)
    {

        $fee_percent    = (double)$this->paymentMethod->fee * (12/100); //.27

        

        $total_tax      = (double)$this->paymentMethod->fee + $fee_percent; // 2.25 + .27 = 2.52
        // number_format((double)$this->amount + $this->total_fee, 2, '.', '')
        $total_with_tax = (double)($total_tax / 100);

       

        // $amount  =  ((double)$full_settlement / ($total_with_tax/100)) - (double)$full_settlement;
        $amount  =  (double)$full_settlement;
        
        $percent_total = (100 - $total_tax) / 100;
        
        $settlement_value = $amount / $percent_total;

        $pti_fee = $settlement_value - $amount;
    
        $min_fee = ((double)$this->paymentMethod->minimum_fee * (12/100)) + (double)$this->paymentMethod->minimum_fee;
        
        // return $pti_fee > (double)$this->paymentMethod->minimum_fee ? $pti_fee :  $min_fee ;
        return $pti_fee ;
    }

    /*
    |--------------------------------------------------------------------------
    | Compute Paynamics Tax and Fee
    | 0.0252 Paynamics Percent  
    | Paynamics Fee => Multiply the Total Amount to ( 2.52/100 )
    |--------------------------------------------------------------------------
    */
    private function getPaynamicsTaxAndFee($final_amount)
    {
        // Without Markup
        // nm = No Markup
        $paynamics_percent  = ((double)$this->paymentMethod->fee); // No Mark up
        $fee_percent_nm     = $paynamics_percent  * (12/100); //.27
        $total_tax_nm       = ((double)$paynamics_percent + $fee_percent_nm) / 100; // 2.25 + .27 = 2.52
        
        $test_tax = [
            'paynamics_percent'  => $paynamics_percent,
            'fee_percent_nm'     => $fee_percent_nm,
            'total_tax_nm'  => $total_tax_nm,
        ];

        $minimum_fee    = (double)$this->paymentMethod->minimum_fee * (double)1.12;
        $total_amount   = $this->total_amount;
        $total_fee      = $this->total_fee;

        $test_fee = [
            'minimum_fee'  => $minimum_fee,
            'total_amount'     => $total_amount,
            'total_fee'     => $total_fee,
        ];

        $paynamics_fee      = (double)$total_amount * (double)$total_tax_nm; // 0.0252 Paynamics Fee Percent
        $paynamics_min_fee  = (((double)$this->paymentMethod->minimum_fee - $this->tnh_fixed_markup) * 1.12);

        $test_paynamics_fee = [
            'paynamics_fee'  => $paynamics_fee,
            'paynamics_min_fee'     => $paynamics_min_fee,
        ];

        

        return (double)$total_fee > (double)$minimum_fee ? $paynamics_fee : $paynamics_min_fee;
    }

    private function getPaynamicsTaxAndFeeV2($final_amount)
    {
        // Without Markup
        // nm = No Markup
        $paynamics_percent  = ((double)$this->paymentMethod->fee); // No Mark up
        $fee_percent_nm     = $paynamics_percent  * (12/100); //.27
        $total_tax_nm       = ((double)$paynamics_percent + $fee_percent_nm) / 100; // 2.25 + .27 = 2.52
        
        $test_tax = [
            'paynamics_percent'  => $paynamics_percent,
            'fee_percent_nm'     => $fee_percent_nm,
            'total_tax_nm'  => $total_tax_nm,
        ];

        $minimum_fee    = (double)$this->paymentMethod->minimum_fee * (double)1.12;
        $total_amount   = $this->total_amount;
        $total_fee      = $this->total_fee;

        $test_fee = [
            'minimum_fee'  => $minimum_fee,
            'total_amount'     => $total_amount,
            'total_fee'     => $total_fee,
        ];

        $paynamics_fee      = (double)$total_amount * (double)$total_tax_nm; // 0.0252 Paynamics Fee Percent
        $paynamics_min_fee  = (((double)$this->paymentMethod->minimum_fee - $this->tnh_fixed_markup) * 1.12);

        $test_paynamics_fee = [
            'paynamics_fee'  => $paynamics_fee,
            'paynamics_min_fee'     => $paynamics_min_fee,
        ];

        

        return (double)$total_fee > (double)$minimum_fee ? $paynamics_fee : $paynamics_min_fee;
    }

    /*
    |--------------------------------------------------------------------------
    | Compute TNH Transaction Fee ( Markup )
    | 
    |--------------------------------------------------------------------------
    */
    private function getTnhTaxAndFee()
    {
        if((double)$this->total_fee > (double)$this->paymentMethod->minimum_fee) {
            return (double)$this->total_fee - (double)$this->paynamics_fee;
        }
        return env('TNH_MARKUP_FIXED');
    }

    private function getTnhTaxAndFeeV2()
    {
        dd($this->total_fee);
        $amount  = $this->request['amount'];

    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment Channel
    |--------------------------------------------------------------------------
    */
    private function getPaymentChannel()
    {
        return $this->paymentMethod->code;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Client IP
    |--------------------------------------------------------------------------
    */
    private function getClientIp ()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /*
    |--------------------------------------------------------------------------
    | PANAMICS WEBHOOK NOTIFICATION
    |--------------------------------------------------------------------------
    */
    public function webhookNotification(Request $request)
    {
        \Log::info('Webhook Notification Received.', ['payload' => $request->all()]);
    
        try {
            $alert_message = $request->response_message ?? 'Something went wrong.';
            $response = [
                'TITLE'         => 'Paynamics Payment Notification',
                'REQUEST ID'    => $request->request_id,
                'RESPONSE CODE' => $request->response_code,
                'MESSAGE'       => $request->response_message,
            ];
    
            // Log the incoming request details
            \Log::info('Webhook Data', ['data' => $response]);
    
            $paynamicsPayment = PaynamicsPayment::where('request_id', $request->request_id)->first();
    
            if (! $paynamicsPayment) {
                \Log::warning('Paynamics Payment NOT Found', [
                    'request_id' => $request->request_id,
                    'response_code' => $request->response_code,
                    'response_message' => $request->response_message
                ]);
                $response['MESSAGE'] = 'Paynamics Payment NOT Found. ' . $request->response_message;
                $alert_message = 'Paynamics Payment NOT Found';
            } else {
                \Log::info('Paynamics Payment Found.', ['request_id' => $request->request_id]);
    
                // Updating paynamics payment record
                \Log::info('Updating Paynamics Payment Record.');
                $paynamicsPayment->update([
                    'pay_reference' => $request->pay_reference ?? '',
                    'response_code' => $request->response_code,
                    'response_message' => $request->response_message,
                    'response_advise' => $request->response_advise,
                    'settlement_info_details' => json_encode($request->settlement_info_details),
                    'timestamp' => Carbon::parse($request->timestamp)->format('Y-m-d H:i:s'),
                    'mail_sent' => 0
                ]);
    
                if ($request->response_code != 'GR033') {
                    \Log::info('Marking Payment as Approved.', ['response_code' => $request->response_code]);
                    $paynamicsPayment->update(['status' => 'APPROVED']);
                }
    
                // Log stock decrement or reservation status
                $order = $paynamicsPayment->order;
                $orderReservation = $order->reservation;
    
                if (!$orderReservation) {
                    foreach ($order->orderItems as $orderItem) {
                        $product = $orderItem->product;
                        $category = $product->category;
                        $branchCategory = $order->branch->categories->find($category->id);
    
                        if ($branchCategory && $branchCategory->pivot->slots >= $orderItem->quantity) {
                            $branchCategory->pivot->decrement('slots', $orderItem->quantity);
                            \Log::info("Decremented slots for category.", [
                                'category' => $category->name,
                                'branch' => $order->branch->name,
                                'quantity' => $orderItem->quantity
                            ]);
                        } else {
                            \Log::warning("Insufficient slots for category.", [
                                'category' => $category->name,
                                'branch' => $order->branch->name,
                                'quantity_requested' => $orderItem->quantity
                            ]);
                        }
    
                        if ($product->stock >= $orderItem->quantity) {
                            $product->decrement('stock', $orderItem->quantity);
                            \Log::info("Decremented product stock.", [
                                'product' => $product->name,
                                'quantity' => $orderItem->quantity
                            ]);
                        } else {
                            \Log::warning("Insufficient stock for product.", [
                                'product' => $product->name,
                                'stock' => $product->stock,
                                'quantity_requested' => $orderItem->quantity
                            ]);
                        }
                    }
                } else {
                    $orderReservation->update(['status' => 'paid']);
                    \Log::info('Order reservation marked as paid.', ['order_id' => $order->id]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Webhook Notification Error', [
                'error_message' => $e->getMessage(),
                'request' => $request->all()
            ]);
            abort(400, $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANAMICS WEBHOOK RESPONSE
    |--------------------------------------------------------------------------
    */
    public function webhookResponse(Request $request)
    {
        \Log::info('Webhook Response Received.', ['payload' => $request->all()]);
    
        try {
            $response = [
                'TITLE'         => 'Paynamics Payment Response',
                'REQUEST ID'    => $request->request_id,
                'RESPONSE CODE' => $request->response_code,
                'MESSAGE'       => $request->response_message,
            ];
    
            \Log::info('Processing Webhook Response.', ['data' => $response]);
    
            $paynamicsPayment = PaynamicsPayment::where('request_id', $request->request_id)->first();
    
            if (! $paynamicsPayment) {
                \Log::warning('Paynamics Payment NOT Found.', [
                    'request_id' => $request->request_id
                ]);
            } else {
                /**
                 * UPDATE PAYNAMICS PAYMENT
                 */
                $paynamicsPayment->update([
                    'pay_reference' => $request->pay_reference ?? '',
                    'response_code' => $request->response_code,
                    'response_message' => $request->response_message,
                    'response_advise' => $request->response_advise,
                    'settlement_info_details' => json_encode($request->settlement_info_details),
                    'timestamp' => Carbon::parse($request->timestamp)->format('Y-m-d H:i:s'),
                    'mail_sent' => 0
                ]);
    
                \Log::info('Updated Paynamics Payment Record.', ['request_id' => $request->request_id]);
    
                /**
                 * MAIL THE PAMENT NOTIFICATION TO PAYER
                 */
                //Mail::to($paynamicsPayment->email)->send(new PaymentNotificationMail($paynamicsPayment));
                
                /**
                 * PAYNAMICS SUCCESS CODE IS SUCCESS ||  SUCCESS with 3DS || PENDING
                 */
                if($request->response_code == 'GR001' || $request->response_code == 'GR002' || $request->response_code != 'GR033') {
                    // UPDATE PAYNAMICS PAYMENT MAIL
                    $paynamicsPayment->update([
                        'mail_sent'         => 1
                    ]);
     
                    if($request->response_code != 'GR033') {
                        $paynamicsPayment->update([
                            'status' => 'APPROVED'
                        ]);
                    }
                } 
                // else {
                //     $response['TITLE'] = 'Paynamics Payment Deleted';
                //     $paynamicsPayment->delete();
                //     \Log::info($response);
                // }


                $order = $paynamicsPayment->order;

                $orderReservation = $order->reservation;

                if (!$orderReservation) {
                    // For same-day orders
                    foreach ($order->orderItems as $orderItem) {
                        $product = $orderItem->product;
                        $category = $product->category; // Assuming you have a relationship for categories
                        $branchCategory = $order->branch->categories->find($category->id);

                        if ($branchCategory && $branchCategory->pivot->slots >= $orderItem->quantity) {
                            $branchCategory->pivot->decrement('slots', $orderItem->quantity);
                        } else {
                            \Log::warning("Insufficient slots for category '{$category->name}' in branch '{$order->branch->name}'.");
                        }
                    }
                } else {
                    $orderReservation->update([
                        'status' => 'paid'
                    ]);
                }

        
                // Decrement product stock
                if ($product->stock >= $orderItem->quantity) {
                    $product->decrement('stock', $orderItem->quantity);
                } else {
                    \Log::warning("Insufficient stock for product '{$product->name}'.");
                }
            }

        } catch (\Exception $e) {
            // \Log::info([
            //     'TITLE'         => 'Paynamics Payment Response Error', 
            //     'REQUEST ID '   => isset($request->request_id) ? $request->request_id : $request,
            //     'RESPONSE CODE' => isset($request->response_code) ? $request->response_code : $request,
            //     'ERROR: '       => $e
            // ]);
            $alert_message = $e;
            abort(400, $e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANAMICS WEBHOOK NOTIFICATION
    |--------------------------------------------------------------------------
    */
    public function webhookCancel(Request $request)
    {
        \Log::info('webhook cancel');
        \Log::info($request);

        try {
            $alert_message  = isset($request->response_message) ? $request->response_message : 'Something went wrong.';
            $response       = [
                'TITLE'         => 'Paynamics Payment Cancel',
                'REQUEST ID'    => $request->request_id,
                'RESPONSE CODE' => $request->response_code,
                'MESSAGE'       => $request->response_message
            ];

            $paynamicsPayment = PaynamicsPayment::where('request_id', $request->request_id)->first();

            if(! $paynamicsPayment) {
                $response['MESSAGE'] = 'Paynamics Payment NOT Found. ' . $request->response_message;
                $alert_message       = 'Paynamics Payment NOT Found';
                \Log::info($response);
            } 
            else {

                
                /**
                 * UPDATE PAYNAMICS PAYMENT
                 */
                $paynamicsPayment->update([
                    // 'response'          => json_encode($request),
                    'pay_reference'     => isset($request->pay_reference) ? $request->pay_reference : '',
                    'response_code'     => $request->response_code,
                    'response_message'  => $request->response_message,
                    'response_advise'   => $request->response_advise,
                    'settlement_info_details' => json_encode($request->settlement_info_details),
                    'timestamp'         => Carbon::parse($request->timestamp)->format('Y-m-d H:i:s'),
                    'mail_sent'         => 0
                ]);
    
                \Log::info($response);
     
                /**
                 * MAIL THE PAMENT NOTIFICATION TO PAYER
                 */
               // Mail::to($paynamicsPayment->email)->send(new PaymentNotificationMail($paynamicsPayment));
     
                /**
                 * PAYNAMICS SUCCESS CODE IS SUCCESS ||  SUCCESS with 3DS || PENDING
                 */
                if($request->response_code == 'GR001' || $request->response_code == 'GR002' || $request->response_code != 'GR033') {
                    // UPDATE PAYNAMICS PAYMENT MAIL
                    $paynamicsPayment->update([
                        'mail_sent'         => 1
                    ]);
     
                    if($request->response_code != 'GR033') {
                        $paynamicsPayment->update([
                            'status' => 'APPROVED'
                        ]);
                    }
                } 
                // else {
                //     $response['TITLE'] = 'Paynamics Payment Deleted';
                //     $paynamicsPayment->delete();
                //     \Log::info($response);
                // }
            }

        } catch (\Exception $e) {
            // \Log::info([
            //     'TITLE'         => 'Paynamics Payment Cancel Error', 
            //     'REQUEST ID '   => isset($request->request_id) ? $request->request_id : $request,
            //     'RESPONSE CODE' => isset($request->response_code) ? $request->response_code : $request,
            //     'ERROR: '       => $e
            // ]);
            $alert_message = $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANAMICS RESPONSE URL
    |--------------------------------------------------------------------------
    */
    // public function responseURL($request_id)
    // {
    //     $paynamicsPayment = PaynamicsPayment::where('request_id', $request_id)->first();
    //     abort_if(! $paynamicsPayment, 404);

    //     $business = $paynamicsPayment->paymentable;
    //     abort_if(! $business, 404);
    //     abort_if(! $business->paybizWallet, 404);
    //     abort_if(! $business->paybizWallet->active, 404);
        
    //     // Add Minutes To Last Updated
    //     $updated_at = Carbon::parse($paynamicsPayment->updated_at)->addMinutes(15);

    //     if( $updated_at < Carbon::now() ) {
    //         return Redirect::route('online_payment.index', ['business_slug' => $business->slug]);
    //     }

    //     if($paynamicsPayment->is_inclusive) {
    //         $amount = (double)$paynamicsPayment->amount;
    //     } else {
    //         $amount = (double)$paynamicsPayment->amount + (double)$paynamicsPayment->fee + (double)$paynamicsPayment->tnh_fee;
    //     }

    //     $data = [
    //         'business'       => $business,
    //         'paymentMethod'  => $paynamicsPayment->paymentMethod,
    //         'amount'         => $amount,
    //         'paynamicsPayment' => $paynamicsPayment
    //     ];

    //     return view('v2.onlinePayments.payment_response')->with($data);
    // }

    /*
    |--------------------------------------------------------------------------
    | PANAMICS CANCEL URL
    |--------------------------------------------------------------------------
    */
    public function cancelURL(Request $request)
    {
        // \Log::info('webhook cancelURL');
        // \Log::info($request);
        dd($request);
    }
}