<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Livewire\Component;

class EmployeeSearch extends Component
{
    public $query = ''; // Search input
    public $employees = []; // Results
    public $selectedEmployee = null;
    public $isAdmin = null;
    public $startDate;
    public $endDate;
    public $attendanceLogs = [];
    public $selectedLogId;
    public $logId, $logType;

    protected $listeners = [
        'updateLogType' => 'updateLogType',
    ];
    
    public function mount()
    {
        $this->isAdmin = backpack_auth()->user()->hasRole('admin') ?? false;

        // Automatically set the selected employee if the user is an employee
        if (backpack_auth()->user()->hasRole('employee')) {
            $this->selectedEmployee = backpack_auth()->user()->employee;
    
            if ($this->selectedEmployee) {
                $this->dispatch('employeeSelected', ['employeeId' => $this->selectedEmployee->id]);
                $this->loadAttendanceLogs(); // Load attendance logs on mount
            }
        }
    }
    

    public function openEditModal($logId)
    {
        $this->logId = $logId;
        $log = AttendanceLog::find($logId);
    
        if ($log) {
            $this->logType = $log->type;
            $this->dispatch('openEditAttendanceModal');
        }
    }
    
    public function updateLogType($type)
    {
        $this->validate([
            'logType' => 'required|integer',
        ]);
    
        $log = AttendanceLog::find($this->logId);
    
        if ($log) {
            $log->type = $type;
            $log->save();

            $this->loadAttendanceLogs();

            $this->dispatch('logTypeUpdated');  // Notify frontend of success
            $this->dispatch('closeEditAttendanceModal'); // Close the modal
            session()->flash('success', 'Attendance type updated successfully.');
        }
    }
    

    public function updatedQuery()
    {
        $this->employees = Employee::where('name', 'like', '%' . $this->query . '%')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedEmployee = Employee::findOrFail($employeeId);
        $this->query = ''; // Clear the search input
        $this->employees = []; // Clear the results

        // Notify the other component about the selected employee
        $this->dispatch('employeeSelected', ['employeeId' => $employeeId]);

        // Load attendance logs for the selected employee
        $this->loadAttendanceLogs();
    }

    public function updatedStartDate()
    {
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->toDateString();
        }
        $this->loadAttendanceLogs();
    }
    
    public function updatedEndDate()
    {
        $this->loadAttendanceLogs();
    }
    

    public function loadAttendanceLogs()
    {
        // Clear the attendance logs if either date is missing
        if (!$this->startDate || !$this->endDate) {
            $this->attendanceLogs = [];
            return;
        }
    
        // Proceed to load logs if both dates are present
        if ($this->selectedEmployee) {
            $query = AttendanceLog::where('employee_id', $this->selectedEmployee->id);
    
            // Filter by date range if both dates are selected
            $query->whereBetween('time_in', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
    
            $this->attendanceLogs = $query->orderBy('time_in', 'asc')->get();
        }
    }
       
    

    public function render()
    {
        return view('livewire.employee-search');
    }
}
