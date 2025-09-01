<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    public function index()
    {
        $attendanceLogs = AttendanceLog::with(['employee', 'branch'])
        ->orderBy('branch_id')
        ->orderBy('employee_id')
        ->orderBy('time_in')
        ->get()
        ->groupBy(function($log) {

            $time_in = Carbon::parse($log->time_in);
            return $log->branch_id . '-' . $log->employee_id . '-' . $time_in->toDateString();
        });

        return view('attendance_report.index', compact('attendanceLogs'));
    }

    public function exportPdf()
    {
        $attendanceLogs = Attendance::with('user')->orderBy('clock_in', 'asc')->get();

        $pdf = \PDF::loadView('attendance_report.index', compact('attendanceLogs'));
        return $pdf->download('attendance_report.pdf');
    }

}
