<div class="card">
    <div class="card-body">
        <h2 class="card-title">Incoming Orders</h2>

        @if($branches->isEmpty())
            <div class="alert alert-info" role="alert">
                You are not assigned to any branches.
            </div>
        @else
            <div class="mb-3">
                <label class="form-label">Select Branch</label>
                <select wire:model="selectedBranch" class="form-select">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($incomingOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Reservation Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomingOrders as $order)
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $order->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $order->reservation_date->format('M d, Y H:i') }}</td>
                                    <td>{{ ucfirst($order->status) }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-success">Mark as Completed</button>
                                        <button class="btn btn-sm btn-danger">Cancel</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
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