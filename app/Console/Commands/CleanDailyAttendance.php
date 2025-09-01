<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\CleanAttendanceLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CleanDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-attendance:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean raw attendance logs and save to clean_attendance_logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateYesterday = Carbon::yesterday();
        $date = $dateYesterday->toDateString();

        // $date = Carbon::parse('2025-05-13')->toDateString(); // for debugging

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
            \Log::info("Employee: ", [
                'employee' => $employee,
                'emp_id' => $employee?->employee_id
            ]);

            $cleaned[] = [
                'employee_id' => $employeeId,
                'emp_id' => $employee?->employee_id,
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

        $this->info("Cleaned " . count($cleaned) . " attendance records for {$date}");

        return Command::SUCCESS;
    }


    protected function isNightShift(array $logs)
    {
        if (empty($logs)) {
            return false;
        }

        $firstLogType = $logs[0]['type'];

        return $firstLogType === 1; // First log is OUT = night shift
    }
}
