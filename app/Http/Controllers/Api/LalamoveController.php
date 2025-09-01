<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LalamoveMetaData;
use App\Models\LalamovePlaceOrder;
use App\Models\Order;
use App\Models\OrderQoutation;
use App\Traits\LalamoveRateLimiter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LalamoveController extends Controller
{
    use LalamoveRateLimiter;

    private $apiSecret;
    private $apiKey;
    private $country;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.lalamove.key');
        $this->apiSecret = config('services.lalamove.secret');
        $this->country = config('services.lalamove.country');
        $this->baseUrl = config('services.lalamove.base_url');
    }

    private function formatPhilippinePhoneNumber($number) {
        $number = preg_replace('/\D+/', '', $number);
        
        if (strpos($number, '+63') === 0) {
            $formattedNumber = $number;
        } else {
            $formattedNumber = '+63' . substr($number, 1);
        }
        
        return $formattedNumber;
    }

    public function generateSignature(string $method, string $path, ?array $body = null, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? now()->getTimestampMs();
    
        // Convert body to JSON or use empty string for GET
        $jsonBody = $body ? json_encode($body, JSON_UNESCAPED_SLASHES) : '';

    
        // Create raw signature string
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$jsonBody}";
    
        // Compute HMAC SHA256
        $signature = hash_hmac('sha256', $rawSignature, $this->apiSecret);

        //dd($rawSignature, $signature, $jsonBody, $timestamp);
    
        return [
            'signature' => $signature,
            'timestamp' => $timestamp
        ];
    }

    public function regenerateQuotationId($quotationId)
    {
        try {
            $this->checkLalamoveRateLimit(); 

            $path = "/v3/quotations";
            $method = 'POST';


            $signatureData = $this->generateSignature($method, $path);
            try {
                $quotation = OrderQoutation::findOrFail($quotationId);
            } catch (\Throwable $e) {
                return response()->json([
                    'error' => 'Order quotation not found.',
                    'details' => $e->getMessage()
                ], 404);
            }

            $rawBody = json_decode($quotation->raw_body);

            // $body = [
            //     'data' => [
            //         //'scheduleAt' => $quotation->schedule_at, 
            //         'serviceType' => $rawBody->data->serviceType,
            //         'language' => 'en_PH',
            //         'item' => $rawBody->data->item,
            //         'stops' => $rawBody->data->stops,
            //         'isRouteOptimized' => true,
            //         'specialRequests' => ['THERMAL_BAG_1']
            //     ]
            // ];

            $dataArray = json_decode(json_encode($rawBody->data), true); // Convert to clean array

            $body = [
                'data' => array_merge($dataArray, [
                    'isRouteOptimized' => true
                ])
            ];
            
            if (filter_var(env('INCLUDE_THERMAL_BAG'), FILTER_VALIDATE_BOOLEAN)) {
                $body['data']['specialRequests'] = ['THERMAL_BAG_1'];
            }
            

            // Manually encode and reuse the same JSON string
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

            $signatureData = $this->generateSignature($method, $path, $body);

            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->withBody($jsonBody, 'application/json')
            ->post("{$this->baseUrl}{$path}");
        
            
            if ($response->successful()) {
        
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to regenerate quotation id',
                    'details' => $response->json(),
                    'raw_body' => $jsonBody, // for debug
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }
        

    }

    public function getQuotationDetails(string $quotationId)
    {
        try {
            $this->checkLalamoveRateLimit(); 
            
            $path = "/v3/quotations/{$quotationId}";
            $method = 'GET';

            $signatureData = $this->generateSignature($method, $path);

            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
            ])->get("{$this->baseUrl}{$path}");

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to get qoutation details',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }    
    }

    public function placeOrder(Request $request)
    {
        try {
            $this->checkLalamoveRateLimit(); 
        
            $isPODEnabled = true;
            $partner = "Lalamove Partner 1";
            $path = "/v3/orders";
            $method = 'POST';
            
            $orderQuotation = OrderQoutation::where('quotation', $request->quotationId)->first();
            
            if (!$orderQuotation || !$orderQuotation->order) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Order quotation not found or order is missing.'
                ], 404);
            }
            
            $order = $orderQuotation->order;
            
            if (!$order->branch || !$order->customer) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Branch or customer information is missing from the order.'
                ], 404);
            }
            
            $branch = $order->branch;
            $customer = $order->customer;
            
        
            $body = [
                'data' => [
                    'quotationId' => $orderQuotation->quotation,
                    'sender' => [
                        'stopId' => $orderQuotation->sender_stop_id,
                        'name' => $branch->name,
                        'phone' => $branch->phone ? $branch->phone : '+639123456789',
                    ],
                    'recipients' => [[
                        'stopId' => $orderQuotation->recipient_stop_id,
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        'phone' => $customer->phone ? $customer->phone : '+639123456789',
                        // 'remarks' => 'Deliver with care'
                    ]],
                    'isPODEnabled' => $isPODEnabled,
                    'partner' => $partner,
                    // 'metadata' => [
                    //     'restaurantOrderId' => $order->id,
                    //     'restaurantName' => $branch->name
                    // ]
                ]
            ];

            \Log::info("data", [
                'body' => $body,
                'order' => $order,
                'customer' => $customer
            ]);
        
            // Manually encode and reuse the same JSON string
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $signatureData = $this->generateSignature($method, $path, $body);
        
            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
                'Content-Type' => 'application/json',
            ])
            ->withBody($jsonBody, 'application/json')
            ->post("{$this->baseUrl}{$path}");
        
            
            if ($response->successful()) {
                $orderQuotation->update([
                    'lalamove_order_ref' => $response->json()['orderRef'] ?? null,
                    'status' => 'placed',
                ]);
        
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to place order',
                    'details' => $response->json(),
                    'raw_body' => $jsonBody, // for debug
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }    
    }

    public function getOrder(string $orderId)
    {
        try {
            $this->checkLalamoveRateLimit(); 
        
            $path = "/v3/orders/{$orderId}";
            $method = 'GET';

            $signatureData = $this->generateSignature($method, $path);

            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
            ])->get("{$this->baseUrl}{$path}");

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to track order',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }    
    }

    public function getCities() 
    {
        try {
            $this->checkLalamoveRateLimit(); // Enforce QPM
    
            $path = "/v3/cities";
            $method = 'GET';

            $signatureData = $this->generateSignature($method, $path);

            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
            ])->get("{$this->baseUrl}{$path}");

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to fetch cities',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }
    }

    public function getDriverDetails(string $orderId, string $driverId)
    {
        try {
            $this->checkLalamoveRateLimit(); // Enforce QPM
    
            $path = "/v3/orders/{$orderId}/drivers/{$driverId}";
            $method = 'GET';

            $signatureData = $this->generateSignature($method, $path);

            $response = Http::withHeaders([
                'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
                'Market' => $this->country,
                'Request-ID' => (string) Str::uuid(),
            ])->get("{$this->baseUrl}{$path}");

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to fetch driver details',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("🚫 Rate limit blocked placeOrder", ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 429);
        }    
    }

    public function trackFullOrder(string $orderId)
    {
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'error' => 'Order not found.',
            ], 404);
        }
    
        $placeOrder = $order->lalamovePlacedOrder;
    
        if (!$placeOrder || !$placeOrder->lalamove_order_id) {
            return response()->json([
                'status' => 'error',
                'error' => 'No Lalamove order found for this order.',
            ], 404);
        }

        $orderDetails = $this->getOrder($placeOrder->lalamove_order_id);
        $orderData = $orderDetails->getData(true)['data'] ?? null;
    
        if (!$orderData) {
            return response()->json([
                'error' => 'Order data not found.'
            ], 404);
        }
    
        $driverId = $orderData['driverId'] ?? null;
        $lalamoveOrderId = $orderData['orderId'] ?? null;

        if (!$lalamoveOrderId) {
            // driverId field not found, meaning no assigned driver yet
            return response()->json([
                'message' => 'Invalid lalamove order data. No order id found',
                'order' => $orderData
            ], 200);
        }
    
        if (!$driverId) {
            // driverId field not found, meaning no assigned driver yet
            return response()->json([
                'message' => 'Order fetched but no driver assigned yet.',
                'order' => $orderData
            ], 200);
        }
    
        // 🔥 If driverId exists, continue fetching driver
    
        $driverDetails = $this->getDriverDetails($lalamoveOrderId, $driverId); 
        $driverData = $driverDetails->getData(true)['data'] ?? null;
    
        return response()->json([
            'message' => 'Order and Driver details fetched successfully',
            'order' => $orderData,
            'driver' => $driverData
        ], 200);
    }
    
    public function lalamoveWebhook()
    {
        $path = "/v3/webhook";
        $method = 'PATCH';

        $body = [
            'data' => [ 
                'url' => env('LALAMOVE_WEBHOOK_URL')
            ]
        ];

        // Manually encode and reuse the same JSON string
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        // return $jsonBody;
        $signatureData = $this->generateSignature($method, $path, $body);

        $response = Http::withHeaders([
            'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
            'Market' => $this->country,
            'Request-ID' => (string) Str::uuid(),
            'Content-Type' => 'application/json',
        ])
        ->withBody($jsonBody, 'application/json')
        ->patch("{$this->baseUrl}{$path}");
    
        
        if ($response->successful()) {
    
            return response()->json($response->json(), 200);
        } else {
            return response()->json([
                'error' => 'Failed to submit webhook',
                'details' => $response->json(),
                'raw_body' => $jsonBody, // for debug
            ], $response->status());
        }
    }

    public function webhookResponse(Request $request)
    {
        \Log::info('📥 Lalamove Webhook Received', $request->all());
    
        // 1. Immediately respond 200 OK
        response()->json(['message' => 'Received'], 200)->send();
        flush();
    
        // 2. Read essentials
        $timestamp = $request->input('timestamp');
        $apiKey = $request->input('apiKey');
        $bodySignature = $request->input('signature');
        $eventType = $request->input('eventType');
    
        $rawBody = $request->getContent();
        $method = 'POST';
        $path = '/lalamove/response'; // Adjust if your webhook path is different
    
        // 3. API Key validation
        if ($apiKey !== $this->apiKey) {
            \Log::warning(" Invalid API key: {$apiKey}");
            return;
        }
    
        // 4. Extract "data" part only (RAW) without re-encoding
        $parsedBody = json_decode($rawBody, true);
        if (!isset($parsedBody['data'])) {
            \Log::warning(' "data" field missing in webhook');
            return;
        }
    
        $dataOnly = $parsedBody['data'];
    
        // Important: Build JSON exactly like JavaScript's JSON.stringify
        $dataOnlyJson = json_encode($dataOnly, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
        \Log::info(' Data Only JSON', ['json' => $dataOnlyJson]);
    
        // 5. Build signature
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$dataOnlyJson}";
        $expectedSignature = hash_hmac('sha256', $rawSignature, $this->apiSecret);
    
        if (!hash_equals($expectedSignature, $bodySignature)) {
            \Log::warning(' Invalid signature', [
                'expected' => $expectedSignature,
                'received' => $bodySignature,
                'raw' => $rawSignature,
            ]);
            return;
        }
    
        \Log::info(' Signature validated successfully');
    
        // 6. Process Event
        $orderResponse = $dataOnly['order'] ?? null;
    
        if (!$eventType) {
            \Log::error('⚠️ Missing eventType');
            return;
        }
    
        if (in_array($eventType, [
            'ORDER_STATUS_CHANGED',
            'DRIVER_ASSIGNED',
            'ORDER_AMOUNT_CHANGED',
            'ORDER_REPLACED',
            'ORDER_EDITED'
        ]) && (!$orderResponse || !isset($orderResponse['orderId']))) {
            \Log::error('⚠️ Missing orderId in payload');
            return;
        }
    
        if ($orderResponse) {
            $orderRef = $orderResponse['orderId'];
            $lalamoveOrder = LalamovePlaceOrder::where('lalamove_order_id', $orderRef)->first();
            $order = $lalamoveOrder->order;
    
            if (!$lalamoveOrder) {
                \Log::warning("⚠️ Webhook received for unknown orderRef: {$orderRef}");
                return;
            }

            if (!$order) {
                \Log::warning("Order not found.");
                return;
            }
    
            if (isset($orderResponse['updatedAt'])) {
                $incomingTime = Carbon::parse($orderResponse['updatedAt']);
                if ($lalamoveOrder->updated_at && $incomingTime->lessThan($lalamoveOrder->updated_at)) {
                    \Log::info("⏳ Ignored outdated webhook for order {$orderRef}");
                    return;
                }
            }
    
            switch ($eventType) {
                case 'ORDER_STATUS_CHANGED':
                    \Log::info("Order Status Changed", compact('lalamoveOrder', 'order'));
                    $lalamoveOrder->update([
                        'status' => $orderResponse['status'] ?? $lalamoveOrder->status,
                        'lalamove_driver_id' => $orderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id,
                        'share_link' => $orderResponse['shareLink'] ?? $lalamoveOrder->share_link,
                    ]);
                    if ($orderResponse['status'] === 'PICKED_UP') {
                        $lalamoveOrder->order->update(['status' => 'order_picked_up']);

                        if (!empty($orderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id)) {
                            $driverId = $orderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id;
                            try {
                                $response = $this->getDriverDetails($lalamoveOrder->lalamove_order_id, $driverId);
                            
                                // If it's a JsonResponse or Response, decode it
                                if ($response instanceof \Illuminate\Http\JsonResponse || $response instanceof \Illuminate\Http\Response) {
                                    $getDriverDetails = $response->getData(true); // true = return as array
                                } else {
                                    $getDriverDetails = is_array($response) ? $response : json_decode($response, true);
                                }
                            
                                if (is_array($getDriverDetails) && isset($getDriverDetails['data'])) {
                                    $lalamoveOrder->update([
                                        'driver_details' => json_encode($getDriverDetails['data']),
                                    ]);
                                    \Log::info("Driver details saved at PICKED_UP", [
                                        'lalamove_order_id' => $lalamoveOrder->id,
                                        'driver_details' => $getDriverDetails['data'],
                                    ]);
                                } else {
                                    \Log::warning("Driver details API returned no usable data at PICKED_UP", [
                                        'lalamove_order_id' => $lalamoveOrder->id,
                                        'response' => $getDriverDetails,
                                    ]);
                                }
                            } catch (\Exception $e) {
                                \Log::error("Error fetching driver details at PICKED_UP", [
                                    'lalamove_order_id' => $lalamoveOrder->id,
                                    'driverId' => $driverId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                            
                        } else {
                            \Log::warning("PICKED_UP status but missing driverId", [
                                'lalamove_order_id' => $lalamoveOrder->id,
                                'orderResponse' => $orderResponse,
                            ]);
                        }
                    } elseif ($orderResponse['status'] === 'COMPLETED') {
                        $lalamoveOrder->order->update(['status' => 'complete']);
                    } elseif ($orderResponse['status'] === 'CANCELED') {
                        $lalamoveOrder->order->update(['status' => 'cancelled']);
                    }
                    break;
    
                case 'DRIVER_ASSIGNED':
                    \Log::info("Driver Assigned", [
                        'lalamoveOrder' => $lalamoveOrder,
                        'order' => $order,
                        'orderResponse' => $orderResponse,
                        'driver' => $dataOnly['driver'],
                    ]);
                    
                    $driverOrderResponse = $dataOnly['driver'] ?? null;
                    
                    $lalamoveOrder->update([
                        'lalamove_driver_id' => $driverOrderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id,
                        'share_link' => $driverOrderResponse['shareLink'] ?? $lalamoveOrder->share_link,
                    ]);

                    if (!empty($driverOrderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id)) {
                        $driverId = $driverOrderResponse['driverId'] ?? $lalamoveOrder->lalamove_driver_id;
                        try {
                            $response = $this->getDriverDetails($lalamoveOrder->lalamove_order_id, $driverId);
                        
                            // If it's a JsonResponse or Response, decode it
                            if ($response instanceof \Illuminate\Http\JsonResponse || $response instanceof \Illuminate\Http\Response) {
                                $getDriverDetails = $response->getData(true); // true = return as array
                            } else {
                                $getDriverDetails = is_array($response) ? $response : json_decode($response, true);
                            }
                        
                            if (is_array($getDriverDetails) && isset($getDriverDetails['data'])) {
                                $lalamoveOrder->update([
                                    'driver_details' => json_encode($getDriverDetails['data']),
                                ]);
                                \Log::info("Driver details saved at PICKED_UP", [
                                    'lalamove_order_id' => $lalamoveOrder->id,
                                    'driver_details' => $getDriverDetails['data'],
                                ]);
                            } else {
                                \Log::warning("Driver details API returned no usable data at PICKED_UP", [
                                    'lalamove_order_id' => $lalamoveOrder->id,
                                    'response' => $getDriverDetails,
                                ]);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error fetching driver details at PICKED_UP", [
                                'lalamove_order_id' => $lalamoveOrder->id,
                                'driverId' => $driverId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        
                    } else {
                        \Log::warning("PICKED_UP status but missing driverId", [
                            'lalamove_order_id' => $lalamoveOrder->id,
                            'orderResponse' => $orderResponse,
                        ]);
                    }
                    
                    break;
                    
                case 'ORDER_AMOUNT_CHANGED':
                    \Log::info("Order Amount Changed", compact('lalamoveOrder', 'order'));
                    $lalamoveOrder->update([
                        'price_total' => $orderResponse['priceTotal'] ?? null,
                        'price_base' => $orderResponse['priceBreakdown']['base'] ?? null,
                        'price_total_exclude_priority_fee' => $orderResponse['priceBreakdown']['totalExcludePriorityFee'] ?? null,
                        'currency' => $orderResponse['currency'] ?? null,
                    ]);
                    break;
    
                case 'ORDER_REPLACED':
                    \Log::info("Order Replaced");
                    $lalamoveOrder->update([
                        'lalamove_order_id' => $orderResponse['orderId'],
                        'status' => $orderResponse['status'] ?? 'REPLACED',
                    ]);
                    break;
    
                case 'ORDER_EDITED':
                    \Log::info("Order Edited");
                    $lalamoveOrder->update([
                        'stops' => json_encode($orderResponse['stops'] ?? []),
                        'status' => $orderResponse['status'] ?? $lalamoveOrder->status,
                    ]);
                    break;
            }
        }
    
        if ($eventType === 'WALLET_BALANCE_CHANGED') {
            \Log::info("💰 Wallet balance changed for apiKey: {$apiKey}");
        }
    
        \Log::info("✅ Webhook processed successfully");
    }


    // Lalamove Metadata


    public function getVehiclesByBranch(Request $request)
    {
        $branchId = $request->query('branch_id');

        if (!$branchId) {
            return response()->json(['error' => 'Missing ?branch_id parameter'], 400);
        }

        $branch = Branch::findOrFail($branchId);

        if (!$branch || !$branch->lalamove_loc_code) {
            return response()->json(['error' => 'Invalid branch or Lalamove location code not set.'], 404);
        }

        $locode = $branch->lalamove_loc_code;

        $vehicles = LalamoveMetaData::where('locode', $locode)->get();

        if ($vehicles->isEmpty()) {
            return response()->json(['error' => "No vehicles found for locode [$locode]"], 404);
        }

        $formatted = $vehicles->map(function ($v) {
            return [
                'key' => $v->vehicle_key,
                'load' => (int) ($v->load['value']),
                'unit' => $v->load['unit'],
                'description' => $v->description,
                'icon' => $v->icon,
            ];
        });        
    
        return response()->json($formatted->values());
    }



   
}