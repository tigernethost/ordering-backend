<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPlaced;
use App\Http\Controllers\Api\LalamoveController;
use App\Mail\PaymentSuccessMail;
use App\Models\LalamovePlaceOrder;
use App\Models\MultisysPayment;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class MultisysPaymentController extends Controller
{
    public function createPayment($data, $txnid, $url)
    {
        $response = [
            'status'  => null,
            'status_code' => null,
            'message' => null,
            'data'    => null,
        ];


        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']); // only if payment is direct and not going to multisys user interface
        $rawData = null;
        $amount = (int) $data['amount'];
        $token = env('MULTISYS_TOKEN');
        $code = env('MULTISYS_CODE');
        $digest = sha1($amount . $txnid . $token); // sha1($amount$txnid$token)
        //$callbackUrl = 'biller-domain.dev';
        $callbackUrl = env('APP_URL') . '/online_payment/multisys/response';
        $redirectUrl = $data['redirect_url'];

        

        if($paymentMethod->url === 'generate_paymaya') {
            $rawData = json_encode([
                'amount' => $amount,
                'txnid' => $txnid,
                'callback_url' => $callbackUrl,
                'redirect_url' => $redirectUrl,
                'digest' => $digest,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'contact_phone' => $data['mobile'],
                'contact_email' => $data['email'],
                'billing_address_line1' => $data['billing_address_line1'],
                'billing_address_city' => $data['billing_address_city'],
                'billing_address_state' => $data['billing_address_state'],
                'billing_address_zip_code' => $data['billing_address_zip_code'],
                'billing_address_country_code' => $data['billing_address_country_code']
            ]);
        } else if ($paymentMethod->url === 'generate_dragonpay') {
            $rawData = json_encode([
                'amount' => $amount,
                'txnid' => $txnid,
                'callback_url' => $callbackUrl,
                'redirect_url' => $redirectUrl,
                'digest' => $digest,
                'channel' => 'DRAGONPAY',
            ]);
        } else if ($paymentMethod->url === 'generate_landbankpay') {
            $rawData = json_encode([
                'amount' => $amount,
                'txnid' => $txnid,
                'callback_url' => $callbackUrl,
                'redirect_url' => $redirectUrl,
                'digest' => $digest,
                'mobile' => $data['formatted_mobile'],
            ]);
        } else {
            $rawData = json_encode([
                'amount' => $amount,
                'txnid' => $txnid,
                'callback_url' => $callbackUrl,
                'redirect_url' => $redirectUrl,
                'digest' => $digest
            ]);
        }

        // $rawData = json_encode([ // for initialize
        //     'amount' => $amount,
        //     'txnid' => $txnid,
        //     'callback_url' => $callbackUrl,
        //     'redirect_url' => $redirectUrl,
        //     'digest' => $digest
        // ]);

        /* Send Request */

        try {
            $apiCall = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-MultiPay-Token' => $token,
                'X-MultiPay-Code' => $code
            ])->withBody(
                $rawData, 'application/json'
            )->post($url);

        } catch (\Throwable $th) {
            $response = [
                'status'  => 'error',
                'status_code' => 500,
                'message' => 'Creating Payment Error',
                'data'    => $th->getMessage(),
            ];
            return $response;  
        }


        $initialResponse = $apiCall->body();

        $responseBody = $apiCall->json();
        $responseMessage = $apiCall->reason();
        
        //dd($responseBody, $responseMessage);
        
        if (!$apiCall || $apiCall->status() !== 200) {
            $response = [
                'status'  => 'error',
                'status_code' => $apiCall->status(),
                'message' => $responseMessage,
                'data'    => $responseBody,
            ];
        } else  {
            $multisysPayment = MultisysPayment::create([
                'payment_method_id' => $data['payment_method_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'description' => $data['description'],
                'billing_address_line1' => $data['billing_address_line1'],
                'billing_address_city' => $data['billing_address_city'],
                'billing_address_state' => $data['billing_address_state'],
                'billing_address_zip_code' => $data['billing_address_zip_code'],
                'billing_address_country_code' => $data['billing_address_country_code'],
                'txnid' => $txnid,
                'initial_amount' => $amount,
                'digest' => $digest,
                'raw_data' => $rawData,
                'callback_url' => $callbackUrl,
                'refno' => $responseBody['data']['refno'] ?? null,
                'url' => $url
            ]);

            $response = [
                'status'  => 'success',
                'status_code' => $apiCall->status(),
                'message' => $responseMessage,
                'data'    => $responseBody['data'],
                'payment_id' => $multisysPayment->id,
            ];
        }
        //dd($response);

        \Log::info("Response Multisys", $response);

        return $response;    
    }

    public function webhookResponse(Request $request)
    {
        \Log::info("Webhook response received", $request->all());
    
        try {
            // Retrieve transaction based on txnid
            $multisysPayment = MultisysPayment::where('txnid', $request->txnid)->first();
            $order = $multisysPayment->order;
    
            if (!$multisysPayment) {
                \Log::warning("Multisys Payment not found for txnid: " . $request->txnid);
                return response()->json(['error' => 'Payment record not found'], 404);
            }
    
            if (!$order) {
                \Log::warning("Order not found in this transaction: " . $request->txnid);
                return response()->json(['error' => 'Order record not found'], 404);
            }

            // Check if this transaction has already been processed successfully
            if ($multisysPayment->status === 'Success/Paid') {
                \Log::info("Transaction already processed for txnid: " . $request->txnid);
                return response()->json(['message' => 'Transaction already processed'], 200);
            }

            $orderReservation = $order->reservation;
    
            // Handle different statuses
            switch ($request->status) {
                case 'S': // Success
                    $status = $this->mapStatus($request->status);
                    $multisysPayment->update([
                        'response' => $request->all(),
                        'response_code' => $request->status,
                        'response_message' => $request->message,
                        'amount' => $request->amount,
                        'mpay_refno' => $request->refno,
                        'refno' => $request->refno,
                        'transaction_date' => $request->transaction_date,
                        'payment_channel' => $request->payment_channel,
                        'payment_channel_branch' => $request->payment_channel_branch,
                        'status' => $status,
                    ]);


                    // Place Lalamove Order
                    // if ($order->order_type === 'delivery' && !LalamovePlaceOrder::where('order_id', $order->id)->exists()) {
                    //     $this->lalamovePlaceOrder($order->id);
                    // }                    

                    // Trigger Alert Notification
                    event(new NewOrderPlaced($order)); 

                    // Decrement product slots in the branch for each ordered item

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

                    // Send email notification about the order and transaction
                    if (!$multisysPayment->mail_sent) {
                        //Mail::to($multisysPayment->email)->send(new PaymentSuccessMail($multisysPayment));
                        $multisysPayment->update(['mail_sent' => 1]);

                    }

                    \Log::info("Payment success email sent to payer.");
                    break;
    
                case 'P': // Pending
                    $status = $this->mapStatus($request->status);
                    $multisysPayment->update([
                        'status' => $status,
                    ]);
                    \Log::info("Payment is pending for txnid: " . $request->txnid);
                    break;
    
                case 'F': // Failed
                    $status = $this->mapStatus($request->status);
                    $multisysPayment->update([
                        'status' => $status,
                    ]);
                    \Log::error("Payment failed for txnid: " . $request->txnid);
                    break;
    
                case 'Void': // Voided
                    $status = $this->mapStatus($request->status);
                    $multisysPayment->update([
                        'status' => $status,
                    ]);
                    \Log::warning("Payment voided for txnid: " . $request->txnid);
                    break;
    
                default:
                    \Log::warning("Unknown status received for txnid: " . $request->txnid);
                    break;
            }
    
            \Log::info("Webhook processed successfully for txnid: " . $request->txnid);
            return response()->json(['message' => 'Webhook received and processed'], 200);
        } catch (\Throwable $th) {
            \Log::error("Error processing webhook response: " . $th->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    private function mapStatus($status)
    {
        $statusMapping = [
            'S' => 'Success/Paid',
            'P' => 'Pending',
            'F' => 'Failed',
            'Void' => 'Void',
        ];

        return $statusMapping[$status] ?? 'Unknown';
    }

    public function confirmation() // redirect_url
    {
        return view('checkout.confirmation');
    }

    // public function lalamovePlaceOrder($orderId)
    // {
    //     \Log::info("Inside the place order in webhooks", ['order_id' => $orderId]);
    //     $order = Order::findOrFail($orderId);
    //     $orderQuotation = $order->orderQuotation;
    //     $lalamoveController = new LalamoveController();

    //     try {
    //         // Check if quotation is expired
    //         if (now()->greaterThan(Carbon::parse($orderQuotation->expires_at))) {
    //             $regenerateQuotation = $lalamoveController->regenerateQuotationId($orderQuotation->id);

    //             if ($regenerateQuotation->getStatusCode() !== 200) {
    //                 $error = $regenerateQuotation->getData(true);
    //                 $message = $error['details']['errors'][0]['message']
    //                     ?? $error['error']
    //                     ?? 'Unknown error occurred.';
                    
    //                 \Log::error('Error in Lalamove', ['response' => $error]);                    
    //                 return;
    //             }

    //             $newQuotationData = $regenerateQuotation->getData(true);

    //             // Validate data key exists
    //             if (!isset($newQuotationData['data']['quotationId'])) {
    //                 $this->dispatch('orderFailed', ['errorMessage' => 'Missing quotationId from regenerate response.']);
    //                 return;
    //             }

    //             $orderQuotation->update([
    //                 'quotation' => $newQuotationData['data']['quotationId'],
    //                 'sender_stop_id' => $newQuotationData['data']['stops'][0]['stopId'] ?? null,
    //                 'recipient_stop_id' => $newQuotationData['data']['stops'][1]['stopId'] ?? null,
    //             ]);
    //         }

    //         $fakeRequest = new Request([
    //             'quotationId' => $orderQuotation->quotation
    //         ]);

    //         $placeOrder = $lalamoveController->placeOrder($fakeRequest);

    //         if ($placeOrder->getStatusCode() !== 200) {
    //             $error = $placeOrder->getData(true);
    //             $message = $error['details']['errors'][0]['message']
    //                 ?? $error['error']
    //                 ?? 'Unknown error occurred.';
                
    //             \Log::error('Error in Lalamove', ['response' => $error]);                    
    //             return;
    //         }

    //         $data = $placeOrder->getData(true)['data'] ?? null;

    //         if (!$data || !isset($data['orderId'])) {
    //             return;
    //         }

    //         LalamovePlaceOrder::create([
    //             'order_id' => $order->id,
    //             'lalamove_order_id' => $data['orderId'],
    //             'lalamove_quotation_id' => $data['quotationId'],
    //             'lalamove_driver_id' => $data['driverId'] ?? null,
    //             'share_link' => $data['shareLink'] ?? null,
    //             'status' => $data['status'],
    //             'distance_value' => isset($data['distance']['value']) ? floatval($data['distance']['value']) : null,
    //             'distance_unit' => $data['distance']['unit'] ?? null,
    //             'price_base' => $data['priceBreakdown']['base'] ?? null,
    //             'price_total_exclude_priority_fee' => $data['priceBreakdown']['totalExcludePriorityFee'] ?? null,
    //             'price_total' => $data['priceBreakdown']['total'] ?? null,
    //             'currency' => $data['priceBreakdown']['currency'] ?? null,
    //             'partner' => $data['partner'] ?? null,
    //             'stops' => json_encode($data['stops'] ?? []),
    //             'response_payload' => json_encode($data),
    //         ]);

    //         $order->update([
    //             'status' => 'for_delivery'
    //         ]);

    //         $order->reservation?->update([
    //             'status' => 'for_delivery'
    //         ]);


    //     } catch (\Throwable $e) {
    //         \Log::error("Placing Lalamove Order Failed!", [
    //             'error' => $e->getMessage()
    //         ]);
    //     }

    // }

    public function lalamovePlaceOrder($orderId)
    {
        \Log::info("Inside the place order in webhooks", ['order_id' => $orderId]);

        $order = Order::findOrFail($orderId);
        $orderQuotation = $order->orderQuotation;

        // Safety: prevent duplicate Lalamove orders
        if (LalamovePlaceOrder::where('order_id', $order->id)->exists()) {
            \Log::info("Lalamove order already exists for order_id: $orderId");
            return;
        }

        $lalamoveController = new LalamoveController();

        try {
            // Create a fake request to reuse existing placeOrder method
            $fakeRequest = new Request([
                'quotationId' => $orderQuotation->quotation
            ]);

            $placeOrder = $lalamoveController->placeOrder($fakeRequest);

            if ($placeOrder->getStatusCode() !== 200) {
                $error = $placeOrder->getData(true);
                $message = $error['details']['errors'][0]['message']
                    ?? $error['error']
                    ?? 'Unknown error occurred.';

                \Log::error('Error in Lalamove', ['response' => $error]);
                return;
            }

            $data = $placeOrder->getData(true)['data'] ?? null;

            if (!$data || !isset($data['orderId'])) {
                \Log::warning('Invalid response from Lalamove placeOrder.');
                return;
            }

            LalamovePlaceOrder::create([
                'order_id' => $order->id,
                'lalamove_order_id' => $data['orderId'],
                'lalamove_quotation_id' => $data['quotationId'],
                'lalamove_driver_id' => $data['driverId'] ?? null,
                'share_link' => $data['shareLink'] ?? null,
                'status' => $data['status'],
                'distance_value' => isset($data['distance']['value']) ? floatval($data['distance']['value']) : null,
                'distance_unit' => $data['distance']['unit'] ?? null,
                'price_base' => $data['priceBreakdown']['base'] ?? null,
                'price_total_exclude_priority_fee' => $data['priceBreakdown']['totalExcludePriorityFee'] ?? null,
                'price_total' => $data['priceBreakdown']['total'] ?? null,
                'currency' => $data['priceBreakdown']['currency'] ?? null,
                'partner' => $data['partner'] ?? null,
                'stops' => json_encode($data['stops'] ?? []),
                'response_payload' => json_encode($data),
            ]);

            $order->update(['status' => 'preparing']);
            $order->reservation?->update(['status' => 'preparing']);

            \Log::info("Lalamove order placed successfully for order_id: $orderId");

        } catch (\Throwable $e) {
            \Log::error("Placing Lalamove Order Failed!", [
                'error' => $e->getMessage()
            ]);
        }
    }


}