<?php

namespace App\Http\Controllers\Api;

use App\Events\NewOrderPlaced;
use App\Events\OrderCreated;
use App\Http\Controllers\PaymentControllerV2;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Branch;
use App\Models\BranchOrderIntervals;
use App\Models\Customer;
use App\Models\MultisysPayment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderQoutation;
use App\Models\OrderReservation;
use App\Models\PaymentMethod;
use App\Models\PaynamicsPayment;
use App\Models\PaynamicsPaymentMethod;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Livewire;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $rules = [
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array',
            'order_type' => 'required|in:delivery,pickup',
            'reservation_date' => 'required',
            'branch_interval_id' => 'required',
            //'quotation_id' => 'required'
            'order_note' => ['nullable', 'max:1500', 'regex:/^[A-Za-z0-9\s.,:\-()+]*$/']
        ];



        if($request->order_type === 'delivery') {
            $rules['lalamove'] = ['required'];
            $rules['quotation_id'] = ['required'];
        }

        // Add conditional validation for 'payment_method_id'
        if (env('PAYMENT_GATEWAY') === 'PAYNAMICS') {
            $rules['payment_method_id'] = [
                'required',
                'exists:paynamics_payment_methods,id',
                Rule::exists('paynamics_payment_methods', 'id')->where('active', 1),
            ];
        } else {
            $rules['payment_method_id'] = [
                'required',
                'exists:payment_methods,id',
                Rule::exists('payment_methods', 'id')->where('active', 1),
            ];
        }

        // Validate the request
        $request->validate($rules);

        // dd($request->payment_method_id);
        $branch = Branch::findOrFail($request->branch_id);
        $branchProducts = $branch->products;

        $timeInterval = BranchOrderIntervals::findOrFail($request->branch_interval_id);

        // Combine reservation_date and end_time into a full datetime
        $reservationDate = $request->reservation_date; // '2025-06-19'
        $endTime = $timeInterval->end_time; // '11:30'
        
        // Append ':00' if seconds are missing
        if (preg_match('/^\d{2}:\d{2}$/', $endTime)) {
            $endTime .= ':00';
        }
        
        // Combine
        $reservationEndDateTimeStr = $reservationDate . ' ' . $endTime;
        
        // Now parse
        $reservationEndDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $reservationEndDateTimeStr);
        
        // Compare
        if (!$timeInterval || $reservationEndDateTime->lte(now()->addMinutes(30))) {
            return response()->json(['message' => 'Selected interval has expired. Please choose another.'], 422);
        }

        //$scheduleAtLocal = Carbon::parse("{$request->reservation_date} {$timeInterval->start_time}");

        //dd($scheduleAtLocal);
        //dd(Carbon::parse($request->reservation_date . ' ' . $timeInterval->start_time));

        // Checking if products are not out of stock and available in the branch

        foreach ($request->items as $item) {
            // Check if the product exists in the branch's products
            $branchProduct = $branchProducts->firstWhere('id', $item['id']);
            if (!$branchProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Product with ID {$item['id']} is not available in the selected branch."
                ], 400);
            }

            // Check if the stock is sufficient
            if ($branchProduct->stock < $item['quantity']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Not enough stock for product '{$branchProduct->name}'. ",
                    'requested' => $item['quantity'],
                    'available' => $branchProduct->stock
                ], 400);
            }
        }

        // End

        $reservationDate = Carbon::parse($request->reservation_date . ' ' . $timeInterval->start_time);

        // Determine if it's a reservation based on the reservation date
        $isReservation = true;

        // Set the comparison date (reservation date or current time)
        $comparisonDate = $isReservation ? $reservationDate : Carbon::now();

        // Retrieve branch order intervals
        $branchOrderIntervals = $branch->branchIntervals;
        $orderTime = null;

        // Find the order interval matching the comparison date
        foreach ($branchOrderIntervals as $orderInterval) {
            $startTime = $comparisonDate->copy()->setTimeFromTimeString($orderInterval->start_time);
            $endTime = $comparisonDate->copy()->setTimeFromTimeString($orderInterval->end_time);

            // Handle overnight intervals
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }

            // Check if the comparison date falls within the interval
            if ($comparisonDate->between($startTime, $endTime)) {
                $orderTime = $orderInterval;
                break;
            }
        }

        // If no valid interval is found, return an error
        if (is_null($orderTime)) {
            $message = $isReservation
                ? 'No orders available for the selected reservation date and time.'
                : 'No orders available at this time.';
            return response()->json(['message' => $message], 400);
        }



        $intervalCategories = $branch->categories; // Access branch's category pivot relationship

        foreach ($request->items as $item) {
            $product = $branchProducts->firstWhere('id', $item['id']);
            $category = $product->category; // Get the category of the product

            if ($category) {
                $categorySlot = $intervalCategories->firstWhere('id', $category->id);
                if ($categorySlot && $categorySlot->pivot->slots !== null) { // Category has slot restrictions
                    if ($categorySlot->pivot->default_slots === 0) {
                        // Skip slot restriction check for unlimited categories
                        continue;
                    }
                    if ($isReservation) {
                        // Calculate reserved slots for the category
                        $reservedSlots = OrderReservation::where('branch_id', $branch->id)
                            ->where('order_interval_id', $orderTime->id)
                            //->where('status', 'paid')
                            ->with('order.orderItems')
                            ->get()
                            ->filter(fn($reservation) => $reservation->order) // Ensure reservations have orders
                            ->flatMap(fn($reservation) => $reservation->order->orderItems)
                            ->whereIn('product_id', $category->products->pluck('id'))
                            ->sum('quantity');

                        //dd($reservedSlots);
                        $remainingSlots = $categorySlot->pivot->default_slots - $reservedSlots;

                        if ($remainingSlots < $item['quantity']) {
                            return response()->json([
                                'status' => 'error',
                                'message' => "Category '{$category->name}' has insufficient slots for the selected reservation interval.",
                                'requested' => $item['quantity'],
                                'available' => $remainingSlots,
                            ], 400);
                        }
                    } else {
                        // For same-day orders
                        $availableSlots = $categorySlot->pivot->slots;
                        if ($availableSlots < $item['quantity']) {
                            return response()->json([
                                'status' => 'error',
                                'message' => "Category '{$category->name}' has insufficient slots for the selected time interval.",
                                'requested' => $item['quantity'],
                                'available' => $availableSlots,
                            ], 400);
                        }
                    }
                }
            }
        }

        // Create an order reservation for reservations
        if ($isReservation) {
            $orderReservation = OrderReservation::create([
                'branch_id' => $branch->id,
                'order_interval_id' => $timeInterval->id,
                'reservation_date' => $reservationDate,
                'status' => 'pending',
            ]);
        }

        // Proceed to create the order (common for both reservation and same-day orders)


        // Billing address (customer info)
        $user = auth('sanctum')->user(); // Check if the user is logged. Returns null for guest users

        if ($user) {
            $customer = Customer::where('user_id', $user->id)->first();

            if (!$customer) {
                return response()->json([
                    'message' => 'No saved customer information found.',
                ], 404);
            }

        } else { // guest customer
            // Validate incoming customer data
            $validated = $request->validate([
                'customer.first_name' => 'required|string|max:255',
                'customer.last_name' => 'required|string|max:255',
                'customer.email' => 'required|email|max:255',
                'customer.phone' => 'required|string|max:15',
                'customer.address' => 'required|string|max:255',
                'customer.city' => 'required|string|max:255',
                'customer.region' => 'nullable|string|max:255',
                'customer.zip_code' => 'required|string|max:10',
                'customer.province' => 'required|string|max:255',
                'customer.marketing' => 'boolean',
                'customer.latitude' => 'required|string|max:255',
                'customer.longitude' => 'required|string|max:255',
            ]);


            if ($request->password !== null) { // if password is provided, create an account
                $user = User::create([
                    'name' => $validated['customer']['first_name'] . ' ' . $validated['customer']['last_name'],
                    'email' => strtolower($validated['customer']['email']),
                    'password' => Hash::make($request->password),
                ]);

                $user->assignRole('customer');
                $validated['user_id'] = $user->id;

            }

            // $formattedNumber =  $this->formatPhilippinePhoneNumber($validated['customer']['phone']);
            $roundedLat = number_format((float) $validated['customer']['latitude'], 8, '.', '');
            $roundedLng = number_format((float) $validated['customer']['longitude'], 8, '.', '');            
            
            // $validated['customer']['phone'] = $formattedNumber;
            $validated['customer']['latitude'] = $roundedLat;
            $validated['customer']['longitude'] = $roundedLng;

            $customer = Customer::updateOrCreate(
                ['email' => $validated['customer']['email']],
                $validated['customer'] + ['user_id' => $validated['user_id'] ?? null]
            );
            

            // $customer = Customer::create($validated['customer']);
        }

        //dd($request->input('shipping_address'));

        // Shipping info
        if (!$request->same_as_billing) { // user doesn't want to use same billing address
            $request->validate([
                'shipping_address.first_name' => 'required|string|max:255',
                'shipping_address.last_name' => 'required|string|max:255',
                'shipping_address.email' => 'required|email|max:255',
                'shipping_address.phone' => 'required|string|max:15',
                'shipping_address.address' => 'required|string|max:255',
                'shipping_address.city' => 'required|string|max:255',
                'shipping_address.region' => 'nullable|string|max:255',
                'shipping_address.zip_code' => 'required|string|max:10',
                'shipping_address.province' => 'required|string|max:255',
            ]);

            $shippingAddressData = $request->input('shipping_address');
            // $formattedNumber =  $this->formatPhilippinePhoneNumber($shippingAddressData['phone']);
            // $shippingAddressData['phone'] = $formattedNumber;
            //dd($shippingAddressData);
            $shippingAddress = ShippingAddress::create($shippingAddressData);
            //dd($shippingAddress);
        } else {
            $shippingAddress = ShippingAddress::create([
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'province' => $customer->province,
                'region' => $customer->region,
                'zip_code' => $customer->zip_code,
            ]);
        }


        // Calculate total amount and prepare order items
        $totalAmount = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $product = Product::find($item['id']);
            $quantity = $item['quantity'];
            $price = $product->price;
            $totalAmount += $price * $quantity;

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        // Create the order
        $order = Order::create([
            'customer_id' => $customer->id,
            'branch_id' => $request->branch_id,
            'branch_order_interval_id' => $timeInterval->id,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'order_type' => $request->order_type,
            'order_note' => $request->order_note
        ]);

        // Save Qoutation ID
        $deliveryFee = 0;

        if ($request->order_type === 'delivery') {

            $lalamoveController = new LalamoveController();
            $quotationResponse = $lalamoveController->getQuotationDetails($request->quotation_id);
    
            // Check if response is successful (status 200)
            if ($quotationResponse->getStatusCode() !== 200) {
                return response()->json([
                    'error' => 'Failed to retrieve quotation details from Lalamove.',
                    'details' => $quotationResponse->getData(true)
                ], $quotationResponse->getStatusCode());
            }
    
            $quotationDetails = $quotationResponse->getData(true)['data'];

            $rawBody = $request->lalamove; // get from the request
    
            //dd($rawBody);

            foreach ($rawBody['data']['stops'] as &$stop) {
                $stop['coordinates']['lat'] = number_format((float) $stop['coordinates']['lat'], 8, '.', '');
                $stop['coordinates']['lng'] = number_format((float) $stop['coordinates']['lng'], 8, '.', '');
            }

            OrderQoutation::create([
                'order_id' => $order->id,
                'quotation' => $quotationDetails['quotationId'],
                'sender_stop_id' => $quotationDetails['stops'][0]['stopId'],
                'recipient_stop_id' => $quotationDetails['stops'][1]['stopId'],
                'service_type' => $quotationDetails['serviceType'],
                'distance_value' => $quotationDetails['distance']['value'],
                'distance_unit' => $quotationDetails['distance']['unit'],
                'price_total' => $quotationDetails['priceBreakdown']['total'],
                'currency' => $quotationDetails['priceBreakdown']['currency'],
                'expires_at' => Carbon::parse($quotationDetails['expiresAt'])->timezone('Asia/Manila'),
                'schedule_at' => Carbon::parse($quotationDetails['scheduleAt'])->timezone('Asia/Manila'),                  
                'response_payload' => json_encode($quotationDetails), 
                'raw_body' => json_encode($rawBody),
            ]);

            $deliveryFee = $quotationDetails['priceBreakdown']['total'];
    
            $order->update([
                'delivery_fee' => $quotationDetails['priceBreakdown']['total']
            ]); // add delivery_fee to order
        }

        // // Trigger Alert Notification
        // event(new NewOrderPlaced($order)); 

        // add order_id to order reservation
        if ($isReservation) {
            $orderReservation->update([
                'order_id' => $order->id
            ]);
        }

        // Update shipping address information
        $shippingAddress->update([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
        ]);


        // Add items to the order
        foreach ($orderItems as $orderItem) {
            $orderItem['order_id'] = $order->id;
            OrderItem::create($orderItem);
        }

        $amountToPay = 0;
        if ($request->order_type === 'delivery') {
            $amountToPay = $order->total_amount + $quotationDetails['priceBreakdown']['total'];
        } else {
            $amountToPay = $order->total_amount;
        }
        

        //dd($order->total_amount);
        // Prepare payment data for Multisys
        $paymentData = [
            'payment_method_id' => $request->payment_method_id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'mobile' => $customer->phone,
            'description' => 'Order #' . $order->id,
            'amount' => $amountToPay,
            'delivery_fee' => $deliveryFee,
            'billing_address_line1' => $customer->address,
            'billing_address_city' => $customer->city,
            'billing_address_state' => $customer->province,
            'billing_address_zip_code' => $customer->zip_code,
            'billing_address_country_code' => 'PH',
            'redirect_url' => env('ORDER_CONFIRMATION_URL') . '/order-confirmation/' . $order->order_id
        ];

        //dd($paymentData);

        // Call the PaymentController to handle payment

        if (env('PAYMENT_GATEWAY') === 'PAYNAMICS') {
            $paymentMethod = PaynamicsPaymentMethod::findOrFail($request->payment_method_id);

            $paymentData = [
                'payment_method_id' => $paymentMethod->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'mobile' => $customer->phone,
                'description' => 'Makimura Order #' . $order->id,
                'address' => $customer->address,
                'billing_address_city' => $customer->city,
                'billing_address_state' => $customer->province,
                'billing_address_zip_code' => $customer->zip_code,
                'description' => 'Order #' . $order->id . ' - ' . 'MAKIMURA',
                'amount' => $order->total_amount,
                'fee' => $request->fee,
                'total_amount' => $request->total_amount,
                'order' => $order,
            ];
            $paymentController = new PaymentControllerV2();

            $payment = $paymentController->submitPayment($paymentData);


            if ($payment['status'] !== 'success') {
                return response()->json([
                    'message' => 'Payment failed',
                    'details' => $payment['message'] ?? 'Unknown error',
                ], $payment['http_code']);
            }

            // Link the payment to the order
            $order->update(['paynamics_payment_id' => $payment['payment_id']]);

            // Redirect the user to the payment URL or return success response
            $response = [
                'message' => 'Order placed successfully',
                'order' => $order,
                'payment_url' => $payment['web_url'] ?? null,
                'request_id' => $payment['request_id'] ?? null,
                'amount' => $payment['amount'] ?? 0,
            ];

            if (isset($payment['instruction'])) {
                $response['instruction'] = $payment['instruction'];
            }

            return response()->json($response, $payment['http_code'] ?? 200);



        } else {
            $paymentController = new PaymentController();
            $payment = $paymentController->submit(new Request($paymentData));


            // Handle payment response
            if ($payment['status'] !== 'success') {
                return response()->json([
                    'message' => 'Payment failed',
                    'details' => $payment['message'] ?? 'Unknown error',
                    'errors' => $payment['data']['errors'] ?? [],
                ], $payment['status_code']);
            }

            // Link the payment to the order
            $order->update(['payment_id' => $payment['payment_id']]);
            
            //dd($order);
            // Redirect the user to the payment URL or return success response
            return response()->json([
                'message' => 'Order placed successfully',
                //'order' => $order,
                'payment_url' => !empty($payment['data']['url']) ? $payment['data']['url'] : $payment['data']['qr_url'],
            ], 201);

        }
    }

    public function myOrders(Request $request)
    {
        $validated = $request->validate([
            'uid' => 'required|string|max:255',
        ]);
    
        try {
            $customer = Customer::where('firebase_uid', $validated['uid'])->first(['id']);
    
            if (!$customer) {
                return response()->json(['message' => 'Customer not found'], 404);
            }
    
            $orders = Order::select('id', 'order_id', 'payment_id', 'customer_id', 'total_amount', 'delivery_fee', 'status', 'order_type')->
            with([
                    'orderItems',
                    'multisysPayment:id,txnid,payment_channel,status',
                    'customer:id,first_name,last_name,latitude,longitude'
                ])
                ->where('customer_id', $customer->id)
                ->latest()
                ->paginate(10);
    
            return response()->json([
                'message' => 'Orders retrieved successfully',
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                ],
            ]);
    
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
    
    function normalizeLalamoveCoordinates(array &$stops, $precision = 8)
    {
        foreach ($stops as &$stop) {
            $stop['coordinates']['lat'] = round((float) $stop['coordinates']['lat'], $precision);
            $stop['coordinates']['lng'] = round((float) $stop['coordinates']['lng'], $precision);
        }
    }

}