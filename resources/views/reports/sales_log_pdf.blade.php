<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
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
            margin-bottom: 20px;
        }

        .logo {
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

        .date-range {
            margin-top: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="logo">
            <img src="{{ $makimuraLogo }}" alt="Makimura Logo">
        </div>
        <div class="address">
            <h3>Sales Report</h3>
            <small>
                Angeles City, Philippines <br>
                makimura.ramen@gmail.com
            </small>
            <div class="date-range">
                <p><strong>Date Range:</strong> {{ date('F j, Y, g:i A', strtotime($startDate)) }} - {{ date('F j, Y, g:i A', strtotime($endDate)) }}</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch</th>
                    <th>Total Sales</th>
                    <th>Total Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesData as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data->branch->name ?? 'Unknown Branch' }}</td>
                    <td>{{ number_format($data->total_sales, 2) }}</td>
                    <td>{{ $data->total_orders }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
