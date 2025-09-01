<div class="card">
    <div class="card-body">
        <h2 class="card-title">Today's Orders</h2>

        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        @if ($branches->isEmpty())
            <div class="alert alert-info" role="alert">
                You are not assigned to any branches.
            </div>
        @else
            @if ($lalamoveOrders->count() > 0)
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Order Type</th>
                                <th>Payment Status</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lalamoveOrders as $order)
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $order->customer->full_name ?? 'N/A' }}</td>
                                    <td>{{ $order->total_amount }}</td>
                                    <td>{{ ucfirst($order->order_type) }}</td>
                                    <td>{{ ucfirst($order->multisysPayment?->status) }}</td>
                                    <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $order->status)) }}</td>
                                    
                                    {{-- @if ($order->multisysPayment?->status === 'Success/Paid' || $order->status === 'complete') --}}
                                        <td>
                                            @php
                                                $items = $order->orderItems->map(function ($item) {
                                                    return [
                                                        'name' => $item->product?->name,
                                                        'image' => $item->product?->image_thumbnail,
                                                        'quantity' => $item->quantity,
                                                        'price' => $item->price,
                                                    ];
                                                });
                                            @endphp
                                            
                                            <a href="javascript:void(0)" class="btn btn-sm btn-primary viewOrder"
                                                data-id="{{ $order->id }}"
                                                data-order-id="{{ $order->order_id }}"
                                                data-customer="{{ $order->customer->full_name ?? 'N/A' }}"
                                                data-note="{{ $order->order_note }}"
                                                data-complete-address="{{ $order->customer?->full_address }}"
                                                data-contact="{{ $order->customer?->phone }}"
                                                data-order-type="{{ ucfirst($order->order_type) }}"
                                                data-order-status="{{ ucwords(str_replace('_', ' ', $order->status)) }}"
                                                data-amount="{{ number_format($order->total_amount, 2) }}"
                                                data-payment-status="{{ ucfirst($order->multisysPayment?->status) }}"
                                                data-created-at="{{ $order->created_at->format('M d, Y h:i A') }}"
                                                data-reserved-at="{{ $order->reservation?->reservation_date->format('M d, Y h:i A') }}"
                                                data-pickup-time="{{ $order->lalamovePlacedOrder ? \Carbon\Carbon::parse($order->lalamovePlacedOrder->created_at)->addMinutes(20)->format('h:i A') : 'NA' }}"
                                                data-driver='@json(json_decode($order->lalamovePlacedOrder?->driver_details))'
                                                data-share-link="{{ $order->lalamovePlacedOrder ? $order->lalamovePlacedOrder->share_link : 'NA' }}"
                                                data-items='@json($items)'
                                                data-is-complete="{{ $order->status === 'complete' ? 'true' : 'false' }}"
                                                data-is-payment-complete="{{ optional($order->multisysPayment)->status === 'Success/Paid' ? 'true' : 'false' }}"
                                                data-has-lalamove-placed-order="{{ $order->lalamovePlacedOrder ? 'true' : 'false' }}"
                                                >
                                                Order Details
                                            </a>

                                            {{-- @if ($order->multisysPayment?->status == 'Success/Paid' && $order->order_type === 'delivery' && is_null($order->lalamovePlacedOrder))
                                                <a href="javascript:void(0)" class="btn btn-sm btn-info bookLalamoveOrder"
                                                    data-id="{{ $order->id }}"
                                                    data-order-id="{{ $order->order_id }}"
                                                    data-customer="{{ $order->customer->full_name ?? 'N/A' }}"
                                                    data-note="{{ $order->order_note }}"
                                                    data-complete-address="{{ $order->customer?->full_address }}"
                                                    data-contact="{{ $order->customer?->phone }}"
                                                    data-order-type="{{ ucfirst($order->order_type) }}"
                                                    data-order-status="{{ ucwords(str_replace('_', ' ', $order->status)) }}"
                                                    data-amount="{{ number_format($order->total_amount, 2) }}"
                                                    data-payment-status="{{ ucfirst($order->multisysPayment?->status) }}"
                                                    data-created-at="{{ $order->created_at->format('M d, Y h:i A') }}"
                                                    data-reserved-at="{{ $order->reservation?->reservation_date->format('M d, Y h:i A') }}"
                                                    data-pickup-time="{{ $order->lalamovePlacedOrder ? \Carbon\Carbon::parse($order->lalamovePlacedOrder->created_at)->addMinutes(20)->format('h:i A') : 'NA' }}"
                                                    data-driver='@json(json_decode($order->lalamovePlacedOrder?->driver_details))'
                                                    data-items='@json($items)'
                                                    data-is-complete="{{ $order->status === 'complete' ? 'true' : 'false' }}"
                                                    data-is-payment-complete="{{ optional($order->multisysPayment)->status === 'Success/Paid' ? 'true' : 'false' }}">
                                                    Book Lalamove
                                                </a>
                                            @endif --}}

                               
                                        </td>

                                    {{-- @else
                                        <td>
                                            <a href="javascript:void(0)" class="btn btn-sm btn-primary disabled" style="pointer-events: none; opacity: 0.6; cursor: not-allowed;">
                                                Order Details
                                            </a>
                                        </td>                                    
                                    @endif --}}
                                                                                                                                    
                                </tr>
                            @endforeach
                        </tbody>
                        
                    </table>
                </div>
            @else
                <div class="alert alert-warning" role="alert">
                    No orders available for the selected branch.
                </div>
            @endif
        @endif
    </div>
</div>

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.updateOrder');
        if (!button) return;

        let id = button.dataset.id;
        let customer = button.dataset.customer;
        let orderId = button.dataset.orderId;
        let orderQuotationId = button.dataset.orderQoutationId;
        let orderType = button.dataset.orderType;
        let orderStatus = button.dataset.orderStatus;

        const statusOptions = orderType === 'delivery'
        ? {
            processing: 'Order is being processed',
            for_delivery: 'For Delivery',
            complete: 'Order Complete'
        }
        : {
            processing: 'Order is being processed',
            for_pickup: 'Ready For Pickup',
            complete: 'Order Complete'
        };


        const currentStatus = orderStatus; 

        const statusOrder = Object.keys(statusOptions);
        const currentIndex = statusOrder.indexOf(currentStatus);

        const filteredEntries = Object.entries(statusOptions).filter(
            ([key], index) => index > currentIndex
        );


        // Convert to option HTML
        const optionsHtml = filteredEntries
            .map(([value, label]) => `<option value="${value}">${label}</option>`)
            .join('');

        Swal.fire({
            title: `Process Order # ${orderId} by ${customer}`,
            html: `
                <div style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
                    <label for="processOrder">Status:</label>
                    <select id="processOrder" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        ${optionsHtml}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Submit Request',
            preConfirm: () => {
                return new Promise((resolve) => {
                    let processOrderStatus = document.getElementById('processOrder').value;

                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while the order is being updated.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    Livewire.on('orderProcessed', () => {
                        Swal.close();
                        resolve(true);
                    });

                    Livewire.on('orderFailed', (errorMessage) => {
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error'
                        });
                    });

                    Livewire.dispatch('processOrder', {
                        detail: {
                            id: id,
                            orderId: orderId,
                            orderQuotationId: orderQuotationId,
                            processOrderStatus: processOrderStatus
                        }
                    });

                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success!', 'Order Updated Successfully', 'success');
            }
        });
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.viewOrder');
        if (!button) return;

        const orderId = button.dataset.orderId;
        const orderNote = button.dataset.note;
        const customer = button.dataset.customer;
        const address = button.dataset.completeAddress
        const phone = button.dataset.contact
        const orderType = button.dataset.orderType.toLowerCase();
        const orderStatus = button.dataset.orderStatus;
        const amount = button.dataset.amount;
        const paymentStatus = button.dataset.paymentStatus;
        const createdAt = button.dataset.createdAt;
        const reservedAt = button.dataset.reservedAt;
        const id = button.dataset.id;
        const orderQuotationId = button.dataset.orderQuotationId;
        const isComplete = button.dataset.isComplete === 'true';
        const isPaymentComplete = button.dataset.isPaymentComplete === 'true';
        const pickupTime = button.dataset.pickupTime;

        //
        const hasLalamovePlacedOrder = button.dataset.hasLalamovePlacedOrder === 'true';

        const shouldShowButton = !isComplete && isPaymentComplete;

        const shareLink = button.dataset.shareLink;
        const driver = JSON.parse(button.dataset.driver || 'null');
        const driverDetailsHtml = driver && orderType === 'delivery' ? `
            <hr>
            <div style="margin-bottom: 20px;">
                <h5 style="margin-top: 10px; text-align: left;">Driver Details</h5>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                    <tbody>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Expected Rider Pickup</td><td style="padding: 8px; border: 1px solid #ccc;">${pickupTime}</td></tr>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Driver ID</td><td style="padding: 8px; border: 1px solid #ccc;">${driver.driverId ?? 'N/A'}</td></tr>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Name</td><td style="padding: 8px; border: 1px solid #ccc;">${driver.name ?? 'N/A'}</td></tr>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Contact No.</td><td style="padding: 8px; border: 1px solid #ccc;">${driver.phone ?? 'N/A'}</td></tr>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Plate #</td><td style="padding: 8px; border: 1px solid #ccc;">${driver.plateNumber ?? 'N/A'}</td></tr>
                        <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Photo</td><td style="padding: 8px; border: 1px solid #ccc;">
                            ${driver.photo ? `<img src="${driver.photo}" width="50" height="50">` : 'N/A'}
                        </td></tr>
                    </tbody>
                </table>
                ${shareLink ? `
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="${shareLink}" target="_blank" style="
                            display: inline-block;
                            padding: 10px 15px;
                            font-size: 14px;
                            color: #fff;
                            background-color: #007bff;
                            border-radius: 5px;
                            text-decoration: none;">
                            Track Order
                        </a>
                    </div>
                ` : ''}
            </div>
        ` : '';


        // const printButtonHtml = `<div style="
        //     padding-top: 20px;
        //     display: flex;
        //     justify-content: flex-end;
        // "><button type="button" class="btn btn-secondary" onclick="window.print()"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
        //     <path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/>
        //     <path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/>
        //     <path d="M7 13a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2z"/>
        //     </svg>
        // Print Order</button></div>`;
        const printButtonHtml = `
        <div style="padding-top: 20px; display: flex; justify-content: flex-end;">
            <a href="/admin/order/${orderId}/print" target="_blank" class="btn btn-secondary">
                Print Order
            </a>
        </div>`;

                        
        const items = JSON.parse(button.dataset.items || '[]');

        const itemRows = items.map(item => `
            <tr>
                <td style="border: 1px solid #ccc; padding: 8px;"><img src="${item.image}" width="50" height="50"></td>
                <td style="border: 1px solid #ccc; padding: 8px;">${item.name}</td>
                <td style="border: 1px solid #ccc; padding: 8px;">${item.quantity}</td>
                <td style="border: 1px solid #ccc; padding: 8px;">${item.price}</td>
            </tr>
        `).join('');

        Swal.fire({
            title: `<strong>Order #${orderId}</strong>`,
            html: `
                <div style="margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                        <tbody>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Customer</td><td style="padding: 8px; border: 1px solid #ccc;">${customer}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Address</td><td style="padding: 8px; border: 1px solid #ccc;">${address}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Contact No.</td><td style="padding: 8px; border: 1px solid #ccc;">${phone}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Amount</td><td style="padding: 8px; border: 1px solid #ccc;">${amount}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Order Type</td><td style="padding: 8px; border: 1px solid #ccc;">${orderType.charAt(0).toUpperCase() + orderType.slice(1)}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Payment Status</td><td style="padding: 8px; border: 1px solid #ccc;">${paymentStatus}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Created At</td><td style="padding: 8px; border: 1px solid #ccc;">${createdAt}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Status</td><td style="padding: 8px; border: 1px solid #ccc;">${orderStatus}</td></tr>
                            <tr><td style="font-weight: bold; padding: 8px; border: 1px solid #ccc;">Order Note</td><td style="padding: 8px; border: 1px solid #ccc;">${orderNote || 'No note'}</td></tr>
                        </tbody>
                    </table>
                </div>
                ${driverDetailsHtml}
                <hr>
                <div>
                    <h5 style="margin-top: 10px; text-align: left;">Items</h5>
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
                        <thead>
                            <tr>
                                <th style="padding: 8px; border: 1px solid #ccc;">Image</th>
                                <th style="padding: 8px; border: 1px solid #ccc;">Name</th>
                                <th style="padding: 8px; border: 1px solid #ccc;">Qty</th>
                                <th style="padding: 8px; border: 1px solid #ccc;">Price</th>
                            </tr>
                        </thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
                ${printButtonHtml}
            `,
            width: 800,
            showCancelButton: !isComplete && isPaymentComplete,
            showConfirmButton: true,
            confirmButtonText: 'Close',
            cancelButtonText: 'Update Order Status',
            reverseButtons: true
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                // Trigger update status modal manually here
                const statusOptions = orderType === 'pickup'
                ? {
                    processing: 'Order is being processed',
                    for_pickup: 'Ready For Pickup',
                    complete: 'Order Complete'
                }
                : {
                    processing: 'Order is being processed',
                    for_delivery: 'For Delivery',
                    complete: 'Order Complete'
                };
                    // Remove 'processing' if Lalamove order already placed
                    if (hasLalamovePlacedOrder) {
                        delete statusOptions.processing;
                    }

                const currentStatus = orderStatus.toLowerCase(); 

                const statusOrder = Object.keys(statusOptions);
                const currentIndex = statusOrder.indexOf(currentStatus);

                const filteredEntries = Object.entries(statusOptions).filter(
                    ([key], index) => index > currentIndex
                );


                // Convert to option HTML
                const optionsHtml = filteredEntries
                    .map(([value, label]) => `<option value="${value}">${label}</option>`)
                    .join('');

                Swal.fire({
                    title: `Process Order # ${orderId} by ${customer}`,
                    html: `
                        <div style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
                            <label for="processOrder">Status:</label>
                            <select id="processOrder" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                ${optionsHtml}
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Submit Request',
                    preConfirm: () => {
                        return new Promise((resolve) => {
                            let processOrderStatus = document.getElementById('processOrder').value;

                            Swal.fire({
                                title: 'Processing...',
                                text: 'Please wait while the order is being updated.',
                                icon: 'info',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            Livewire.on('orderProcessed', () => {
                                Swal.close();
                                resolve(true);
                            });

                            Livewire.on('orderFailed', (errorMessage) => {
                                Swal.fire({
                                    title: 'Error!',
                                    text: errorMessage,
                                    icon: 'error'
                                });
                            });

                            Livewire.dispatch('processOrder', {
                                detail: {
                                    id: id,
                                    orderId: orderId,
                                    orderQuotationId: orderQuotationId,
                                    processOrderStatus: processOrderStatus
                                }
                            });

                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Success!', 'Order Updated Successfully', 'success');
                    }
                });
            }
        });
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.bookLalamoveOrder');
        if (!button) return;

        const orderId = button.dataset.orderId;
        const customer = button.dataset.customer;
        const address = button.dataset.completeAddress;
        const phone = button.dataset.contact;
        const amount = button.dataset.amount;
        const orderType = button.dataset.orderType;
        const paymentStatus = button.dataset.paymentStatus;
        const pickupTime = button.dataset.pickupTime;

        Swal.fire({
            title: `<strong>Process Order #${orderId}</strong>`,
            html: `
                <div style="text-align: left;">
                    <p><strong>Customer:</strong> ${customer}</p>
                    <p><strong>Contact No.:</strong> ${phone}</p>
                    <p><strong>Address:</strong> ${address}</p>
                    <p><strong>Amount:</strong> ₱${amount}</p>
                    <p><strong>Order Type:</strong> ${orderType}</p>
                    <p><strong>Payment Status:</strong> ${paymentStatus}</p>
                    <p><strong>Expected Pickup:</strong> ${pickupTime}</p>
                </div>
                <hr>
                <p style="margin-top: 10px;">Are you sure you want to process this order now in Lalamove?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, process it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while the order is being processed in Lalamove.',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                Livewire.on('orderBooked', () => {
                    Swal.close();
                    Swal.fire('Success!', 'Order processed successfully in Lalamove.', 'success');
                });

                Livewire.on('bookLalamoveFailed', (detail) => {
                    Swal.close();

                    let msg;
                    if (typeof detail === 'string') {
                        msg = detail;
                    } else if (Array.isArray(detail) && detail.length > 0) {
                        msg = detail[0];
                    } else if (detail?.errorMessage) {
                        msg = detail.errorMessage;
                    } else {
                        msg = 'Unknown error occurred';
                    }

                    Swal.fire('Error!', msg, 'error');
                });



                Livewire.dispatch('bookLalamove', {
                    detail: {
                        orderId: orderId
                    }
                });
            }
        });
    });



</script>
@endpush