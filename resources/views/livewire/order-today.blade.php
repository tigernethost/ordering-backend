<div class="card">
    <div class="card-body">
        <h2 class="card-title">Today's Orders</h2>

        @if($branches->isEmpty())
            <div class="alert alert-info" role="alert">
                You are not assigned to any branches.
            </div>
        @else


            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $order->customer_name ?? 'N/A' }}</td>
                                    <td>{{ number_format($order->total_amount, 2) }}</td>
                                    <td>{{ $order->created_at->format('H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning" role="alert">
                    No orders have been placed today for this branch.
                </div>
            @endif
        @endif
    </div>
</div>