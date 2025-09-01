<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>

    @php
        $currentBranch = null;
    @endphp

    @foreach ($attendanceLogs as $groupKey => $logs)
        @php
        
            $firstLog = $logs->first();
            $employee = $firstLog->employee;
            $branch = $firstLog->branch;
            $date = $firstLog->time_in;
           
            // Display branch header only if the branch exists and it's a new branch
            if ($branch && $currentBranch !== $branch->id) {
                $currentBranch = $branch->id;
                echo "<h2>Branch: {$branch->name}</h2>";
            }

            // Initialize variables for each attendance type
            $timeIn1 = $timeOut1 = $timeIn2 = $timeOut2 = $timeIn3 = $timeOut3 = $overtimeIn = $overtimeOut = 'N/A';

            // Assign each log entry to its respective variable
            foreach ($logs as $log) {
                switch ($log->type) {
                    case 0:
                        if ($timeIn1 === 'N/A') {
                            $timeIn1 = $log->time_in->format('H:i:s');
                        } elseif ($timeIn2 === 'N/A') {
                            $timeIn2 = $log->time_in->format('H:i:s');
                        } else {
                            $timeIn3 = $log->time_in->format('H:i:s');
                        }
                        break;
                    case 1:
                        if ($timeOut1 === 'N/A') {
                            $timeOut1 = $log->time_in->format('H:i:s');
                        } elseif ($timeOut2 === 'N/A') {
                            $timeOut2 = $log->time_in->format('H:i:s');
                        } else {
                            $timeOut3 = $log->time_in->format('H:i:s');
                        }
                        break;
                    case 4:
                        $overtimeIn = $log->time_in->format('H:i:s');
                        break;
                    case 5:
                        $overtimeOut = $log->time_in->format('H:i:s');
                        break;
                }
            }
        @endphp

        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
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
                <tr>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $date }}</td>
                    <td>{{ $timeIn1 }}</td>
                    <td>{{ $timeOut1 }}</td>
                    <td>{{ $timeIn2 }}</td>
                    <td>{{ $timeOut2 }}</td>
                    <td>{{ $timeIn3 }}</td>
                    <td>{{ $timeOut3 }}</td>
                    <td>{{ $overtimeIn }}</td>
                    <td>{{ $overtimeOut }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>



@push('scripts')
<script>
    function downloadPdfReport() {
        // Get the current URL and extract the filter parameters
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);

        // Construct the PDF download URL with the current filter parameters
        let downloadUrl = "{{ url('admin/attendance-log/download-pdf') }}?" + params.toString();

        // Redirect to the download URL
        window.open(downloadUrl, '_blank');
    }
</script>
@endpush