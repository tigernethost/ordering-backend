<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Log Report</title>
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

        .makimura-logo {
            width: 100%;
            margin-bottom: 5px;
        }

        .makimura-logo img {
            display: block;
            margin: 0 auto;
            width: 90px;
        }

        .address small {
            font-size: 10px;
        }

        table {
            width: 100%; 
            border-collapse: collapse; 
            font-size: 8px;
        }

        table, th, td { 
            border: 1px solid black; 
            padding: 8px; 
            font-size: 14px;
            text-align: center; 
        }

        th { 
            background-color: #f2f2f2; 
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="makimura-logo">
            <img src="{{ $makimuraLogo }}" alt="Makimura Logo">
            <div class="address">
                <small>
                    Angeles City, Philippines <br>
                    makimura.ramen@gmail.com
                </small>
                <h3>Employee Report</h3>
            </div>
        </div>
    </div>

    @forelse ($attendanceLogs as $branchDateKey => $logsByEmployee)
        @php
            [$branchName, $date] = explode('|', $branchDateKey);
        @endphp
        <h2>Branch: {{ $branchName ?? 'N/A' }}</h2>
        <small>Date: {{ $date }}</small>

        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Time-In 1</th>
                    <th>Time-Out 1</th>
                    <th>Time-In 2</th>
                    <th>Time-Out 2</th>
                    <th>Time-In 3</th>
                    <th>Time-Out 3</th>
                    <th>Overtime In</th>
                    <th>Overtime Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logsByEmployee as $employeeName => $logs)
                    @php
                        // Initialize variables for aggregated time slots
                        $timeIn1 = $timeOut1 = $timeIn2 = $timeOut2 = $timeIn3 = $timeOut3 = $overtimeIn = $overtimeOut = 'N/A';

                        // Aggregate times for each log entry
                        foreach ($logs as $log) {
                            switch ($log->type) {
                                case 0: // Time-In
                                    if ($timeIn1 === 'N/A') {
                                        $timeIn1 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    } elseif ($timeIn2 === 'N/A') {
                                        $timeIn2 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    } else {
                                        $timeIn3 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    }
                                    break;
                                case 1: // Time-Out
                                    if ($timeOut1 === 'N/A') {
                                        $timeOut1 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    } elseif ($timeOut2 === 'N/A') {
                                        $timeOut2 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    } else {
                                        $timeOut3 = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    }
                                    break;
                                case 4: // Overtime-In
                                    $overtimeIn = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    break;
                                case 5: // Overtime-Out
                                    $overtimeOut = \Carbon\Carbon::parse($log->time_in)->format('h:i:s A');
                                    break;
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $employeeName }}</td>
                        <td>{{ $timeIn1 }}</td>
                        <td>{{ $timeOut1 }}</td>
                        <td>{{ $timeIn2 }}</td>
                        <td>{{ $timeOut2 }}</td>
                        <td>{{ $timeIn3 }}</td>
                        <td>{{ $timeOut3 }}</td>
                        <td>{{ $overtimeIn }}</td>
                        <td>{{ $overtimeOut }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
    @empty
        <p>No records found.</p>
    @endforelse
</body>
</html>
