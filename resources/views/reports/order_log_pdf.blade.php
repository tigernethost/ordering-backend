<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }

        .logo {
            width: 100%;
            margin-bottom: 10px;
        }

        .logo img {
            display: block;
            margin: 0 auto;
            width: 100px;
        }

        .address {
            text-align: center;
            margin-top: 5px;
            font-size: 10px;
        }

        table {
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            font-size: 12px;
        }

        table, th, td { 
            border: 1px solid black; 
            padding: 8px; 
            text-align: center; 
        }

        th { 
            background-color: #f2f2f2; 
        }

        h3 {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="logo">
            <img src="{{ $makimuraLogo }}" alt="Makimura Logo">
        </div>
        <div class="address">
            <h3>Order Report</h3>
            <small>
                Angeles City, Philippines <br>
                makimura.ramen@gmail.com
            </small>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Branch</th>
                    <th>Order Status</th>
                    <th>Order Type</th>
                    <th>Total Amount</th>
                    <th>Order Date</th>
                    <th>Reservation Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $index => $order)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $order->customer->full_name ?? 'N/A' }}</td>
                    <td>{{ $order->branch->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($order->status) ?? 'N/A' }}</td>
                    <td>{{ ucfirst($order->order_type) ?? 'N/A' }}</td>
                    <td>{{ number_format($order->total_amount, 2) }}</td>
                    <td>{{ $order->created_at->format('F j, Y, g:i A') }}</td>
                    <td>{{ $order->reservation ? $order->reservation->reservation_date->format('F j, Y, g:i A') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>