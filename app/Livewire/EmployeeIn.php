<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AttendanceLog;
use Carbon\Carbon;

class EmployeeIn extends Component
{
    public $checkedInCount = 0;
    public $employeesCheckedIn = [];

    public function mount()
    {
        $this->fetchCheckedInData();
    }

    public function fetchCheckedInData()
    {
        $today = Carbon::today();

        // Get the count of distinct employees
        $this->checkedInCount = AttendanceLog::whereDate('time_in', $today)
            ->distinct('employee_id')
            ->count('employee_id');

        // Get the names of employees
        $this->employeesCheckedIn = AttendanceLog::with('employee')
            ->whereDate('time_in', $today)
            ->get()
            ->pluck('employee.name')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.employee-in');
    }
}
