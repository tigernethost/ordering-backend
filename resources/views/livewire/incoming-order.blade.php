<div class="card">
    <div class="card-body">
        <h2 class="card-title">Scheduled Orders</h2>

        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        @if($branches->isEmpty())
            <div class="alert alert-info" role="alert">
                You are not assigned to any branches.
            </div>
        @else

            @if($incomingOrders && $incomingOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Delivery Type</th>
                                <th>Reservation Date</th>
                                <th>Order Created Date</th>
                                {{-- <th>Status</th>
                                <th>Actions</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomingOrders as $order)
                                <tr>
                                    <td>{{ $order->order?->order_id }}</td>
                                    <td>{{ $order->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $order->order?->total_amount }}</td>
                                    <td>{{ ucfirst($order->order?->order_type) }}</td>
                                    <td>{{ $order->reservation_date->format('M d, Y') }}</td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    {{-- <td>
                                        <select wire:change="updateOrderStatus({{ $order->id }}, $event.target.value)"
                                                class="form-select">
                                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Preparing Order</option>
                                            <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Ready for Pickup</option>
                                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Order Completed</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button wire:click="cancelOrder({{ $order->id }})" class="btn btn-sm btn-danger">Cancel</button>
                                    </td> --}}
                                </tr>
                            @endforeach
                        </tbody>

                        @if ($incomingOrders->count() > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        {{ $incomingOrders->links() }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @else
                <div class="alert alert-warning" role="alert">
                    No incoming orders for this branch.
                </div>
            @endif
        @endif
    </div>
</div>
