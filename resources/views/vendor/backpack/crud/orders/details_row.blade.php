<div class="details-row">
    {{-- Order Summary --}}
    <div class="mb-4">
        <h4 class="text-primary border-bottom pb-2">Order Summary</h4>
        <p><strong>Order Date:</strong> {{ $order->created_at?->format('F j, Y, g:i a') ?? 'N/A' }}</p>
        @if ($order->reservation)
            <p><strong>Reservation Date:</strong> {{ $order->reservation->reservation_date?->format('F j, Y, g:i a') ?? 'N/A' }}</p>
        @endif
    </div>
    
    {{-- Order Reservation Details --}}
    @if ($order->reservation)
        <div class="mb-4">
            <h4 class="text-primary border-bottom pb-2">Order Reservation</h4>
            <p><strong>Order Status:</strong> {{ ucfirst($order->status ?? 'N/A') }}</p>
            <p><strong>Cancelled At:</strong> {{ $order->cancelled_at?->format('F j, Y, g:i a') ?? 'N/A' }}</p>
        </div>
    @endif

    {{-- Billing Address --}}
    <div class="mb-4">
        <h4 class="text-primary border-bottom pb-2">Billing Information</h4>
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th scope="row">Name</th>
                    <td>{{ $transaction?->first_name ?? 'N/A' }} {{ $transaction?->last_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th scope="row">Phone</th>
                    <td>{{ $transaction?->mobile ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th scope="row">Address</th>
                    <td>{{ $transaction?->billing_address_line1 ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th scope="row">Province</th>
                    <td>{{ $transaction?->billing_address_state ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th scope="row">City/Municipality</th>
                    <td>{{ $transaction?->billing_address_city ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th scope="row">Zip Code</th>
                    <td>{{ $transaction?->billing_address_zip_code ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Delivery Address --}}
    @if ($order->order_type === 'delivery')
        <div class="mb-4">
            <h4 class="text-primary border-bottom pb-2">Delivery Information</h4>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th scope="row">Name</th>
                        <td>{{ $shippingAddress?->first_name ?? 'N/A' }} {{ $shippingAddress?->last_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Phone</th>
                        <td>{{ $shippingAddress?->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Address</th>
                        <td>{{ $shippingAddress?->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Province</th>
                        <td>{{ $shippingAddress?->province ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">City/Municipality</th>
                        <td>{{ $shippingAddress?->city ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Zip Code</th>
                        <td>{{ $shippingAddress?->zip_code ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
    
    {{-- Order Items --}}
    <div class="mb-4">
        <h4 class="text-primary border-bottom pb-2">Order Items</h4>
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orderItems ?? [] as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity ?? 'N/A' }}</td>
                        <td>{{ number_format($item->price ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Transaction Details --}}
    <div class="mb-4">
        <h4 class="text-primary border-bottom pb-2">Transaction Details</h4>
        <table class="table table-bordered">
            <tbody>
                @if ($transaction)
                    <tr>
                        <th scope="row">Payment Method</th>
                        <td>{{ $transaction->payment_channel ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Transaction ID</th>
                        <td>{{ $transaction->txnid ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Amount</th>
                        <td>{{ number_format($transaction->initial_amount ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <span class="badge 
                                {{ $transaction->status === 'Completed' ? 'bg-success' : 'bg-warning' }}">
                                {{ $transaction->status ?? 'Pending' }}
                            </span>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" class="text-center text-muted">No Transaction Found</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
