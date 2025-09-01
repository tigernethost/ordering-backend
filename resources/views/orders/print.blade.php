<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->order_id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 5px;
            padding-bottom: 20mm; /* Added bottom padding */
            width: 55mm;
        }
        

        @page {
            margin: 0 0 20mm 0; /* Added bottom margin for print */
        }

        .business-header {
            text-align: center;
            margin-bottom: 5px;
        }

        .business-header img {
            max-width: 50px;
            height: auto;
            margin-bottom: 5px;
        }

        h2, h3, h4 {
            margin: 0;
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info p {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th, td {
            text-align: left;
            padding: 3px 0;
            font-size: 11px;
        }

        th {
            border-bottom: 1px dashed #000;
        }

        tfoot td {
            border-top: 1px dashed #000;
            font-weight: bold;
        }

        .total {
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="business-header">
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ public_path('images/logo.png') }}" alt="Logo">
        @endif
        <h2>Makimura Ramen</h2>
        <p>{{ $order->branch->name }}</p>
    </div>
    
    <div class="divider"></div>
    <h3 style="text-align:center;">Order Receipt</h3>
    <h4 style="text-align:center;">#{{ $order->order_id }}</h4>
    <div class="divider"></div>

    <div class="info">
        <p><strong>Customer:</strong> {{ $order->customer->full_name }}</p>
        <p><strong>Address:</strong> {{ $order->customer->full_address }}</p>
        <p><strong>Contact:</strong> {{ $order->customer->phone }}</p>
        <p><strong>Order Type:</strong> {{ ucfirst($order->order_type) }}</p>
        <p><strong>Payment Status:</strong> {{ ucfirst($order->multisysPayment?->status) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        <p><strong>Created At:</strong> {{ $order->created_at->format('M d, Y h:i A') }}</p>
        <p><strong>Order Note:</strong> {{ $order->order_note ?? 'No note' }}</p>
    </div>

    <div class="divider"></div>
    <h4>Items</h4>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="total">Total</td>
                <td class="total">₱{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
