<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\CleanAttendanceLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function cleanAttendanceLogs(Request $request)
    {
        $date = Carbon::parse($request->input('date'))->toDateString();
        // $dateNow = Carbon::now();
        // $date = $dateNow->toDateString();


        $start = Carbon::parse($date)->startOfDay(); 
        $end = Carbon::parse($date)->addDay()->endOfDay(); // today + tomorrow

        $attendanceLogs = AttendanceLog::whereBetween('time_in', [$start, $end])
            ->orderBy('employee_id')
            //->where('employee_id', 68)
            ->orderBy('time_in')
            ->get()
            ->groupBy('employee_id');

        $cleaned = [];

        foreach ($attendanceLogs as $employeeId => $logs) {
            $logs = $logs->sortBy('time_in')->values();

            // Build lightweight array for checking night shift
            $timeInsPh = $logs->map(function ($log) {
                return [
                    'time_in' => Carbon::parse($log->time_in)->timezone('Asia/Manila')->toDateTimeString(),
                    'type' => (int) $log->type,
                ];
            })->toArray();

            $isNightShift = $this->isNightShift($timeInsPh);
            

            $workingDate = $date;

            // If not night shift, only keep today's logs
            if (!$isNightShift) {
                $logs = $logs->filter(function ($log) use ($date) {
                    return Carbon::parse($log->time_in)->timezone('Asia/Manila')->toDateString() === $date;
                })->values();
            }

            // Parse attendance logs
            $parsed = [
                'check_in' => null,
                'break_out' => null,
                'break_in' => null,
                'check_out' => null,
            ];
            $missing = [];

            foreach ($logs as $log) {
                $status = (int) $log->status;
                $time = Carbon::parse($log->time_in)->timezone('Asia/Manila')->toDateTimeString();

                if (!$parsed['check_in'] && $status === 0) {
                    $parsed['check_in'] = $time;
                } elseif (!$parsed['break_out'] && $status === 1 && $parsed['check_in']) {
                    $parsed['break_out'] = $time;
                } elseif (!$parsed['break_in'] && $status === 0 && $parsed['break_out']) {
                    $parsed['break_in'] = $time;
                } elseif (!$parsed['check_out'] && $status === 1 && $parsed['break_in']) {
                    $parsed['check_out'] = $time;
                }
            }

            foreach ($parsed as $key => $value) {
                if (is_null($value)) {
                    $missing[] = $key;
                }
            }

            $employee = $logs->first()?->employee;
            $branch = $logs->first()?->branch;

            $cleaned[] = [
                'employee_id' => $employeeId,
                'emp_id' => $employee?->employee_id,
                'employee' => $employee?->name,
                'branch' => $branch?->name,
                'date' => $workingDate,
                'check_in' => $parsed['check_in'],
                'break_out' => $parsed['break_out'],
                'break_in' => $parsed['break_in'],
                'check_out' => $parsed['check_out'],
                'is_night_shift' => $isNightShift,
                'status' => empty($missing) ? 'complete' : 'incomplete',
                'missing_parts' => empty($missing) ? null : $missing,
            ];
        }

        foreach ($cleaned as $entry) {
            CleanAttendanceLog::updateOrCreate(
                [
                    'employee_id' => $entry['employee_id'],
                    'date' => $entry['date']
                ],
                $entry
            );
        }

        return response()->json([
            'date_processed' => $date,
            'records_cleaned' => count($cleaned),
            'data' => $cleaned
        ]);
    }

    protected function isNightShift(array $logs)
    {
        if (empty($logs)) {
            return false;
        }

        $firstLogType = $logs[0]['type'];

        return $firstLogType === 1; // First log is OUT = night shift
    }

	public function getCleanAttendance(Request $request)
	{
		$date = Carbon::parse($request->input('date'))->toDateString();

		$cleanedLogs = CleanAttendanceLog::whereDate('date', $date)->get();

        \Log::info("Cleaned Attendance Logs", [
            'date' => $request->input('date'),
            'logsCount' => $cleanedLogs->count(),
            'cleanedLogs' => $cleanedLogs
        ]);
		return response()->json([
            'message' => 'Cleaned Attendance Logs Fetched!',
			'date' => $date,
			'attendance' => $cleanedLogs
        ]);

	}
}
