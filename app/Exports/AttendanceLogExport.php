<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceLogExport implements FromArray, WithHeadings
{
    protected $attendanceLogs;

    public function __construct($attendanceLogs)
    {
        $this->attendanceLogs = $attendanceLogs;
    }

    /**
     * Return the filtered data as an array.
     */
    public function array(): array
    {
        $data = [];

        foreach ($this->attendanceLogs as $branchDateKey => $logsByEmployee) {
            // Parse branch name and date
            [$branchName, $date] = explode('|', $branchDateKey);

            // Add branch and date header
            $data[] = [$branchName, 'Date: ' . $date, '', '', '', '', '', '', '', '', ''];

            foreach ($logsByEmployee as $employeeName => $logs) {
                // Initialize variables for time slots
                $timeIn1 = $timeOut1 = $timeIn2 = $timeOut2 = $timeIn3 = $timeOut3 = $overtimeIn = $overtimeOut = 'N/A';

                // Aggregate times for each log entry
                foreach ($logs as $log) {
                    switch ($log->type) {
                        case 0: // Time-In
                            if ($timeIn1 === 'N/A') {
                                $timeIn1 = $log->time_in;
                            } elseif ($timeIn2 === 'N/A') {
                                $timeIn2 = $log->time_in;
                            } else {
                                $timeIn3 = $log->time_in;
                            }
                            break;
                        case 1: // Time-Out
                            if ($timeOut1 === 'N/A') {
                                $timeOut1 = $log->time_in;
                            } elseif ($timeOut2 === 'N/A') {
                                $timeOut2 = $log->time_in;
                            } else {
                                $timeOut3 = $log->time_in;
                            }
                            break;
                        case 4: // Overtime-In
                            $overtimeIn = $log->time_in;
                            break;
                        case 5: // Overtime-Out
                            $overtimeOut = $log->time_in;
                            break;
                    }
                }

                // Add employee data for the date
                $data[] = [
                    '',
                    '',
                    $employeeName,
                    $timeIn1,
                    $timeOut1,
                    $timeIn2,
                    $timeOut2,
                    $timeIn3,
                    $timeOut3,
                    $overtimeIn,
                    $overtimeOut,
                ];
            }
        }

        return $data;
    }

    /**
     * Define the headings for the Excel file.
     */
    public function headings(): array
    {
        return [
            'Branch',
            'Date',
            'Employee',
            'Time-In 1',
            'Time-Out 1',
            'Time-In 2',
            'Time-Out 2',
            'Time-In 3',
            'Time-Out 3',
            'Overtime In',
            'Overtime Out',
        ];
    }
}
