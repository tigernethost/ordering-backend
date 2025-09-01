<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AttendanceLogRequest;
use App\Models\AttendanceLog;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Class AttendanceLogCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AttendanceLogCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\AttendanceLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/attendance-log');
        CRUD::setEntityNameStrings('attendance log', 'attendance logs');

        // Add Filters
        $this->addFilters();

        // Add a button to download the PDF
        $this->crud->addButtonFromView('top', 'download_pdf', 'download_pdf', 'beginning');
        $this->crud->addButtonFromView('top', 'download_xls', 'download_xls', 'beginning');

        
        CRUD::column([
            // 1-n relationship
            'label'     => 'Employee', // Table column heading
            'type'      => 'select2',
            'name'      => 'employee_id', // the column that contains the ID of that connected entity;
            'entity'    => 'employee', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Employee", // foreign key model
         ],);

         CRUD::column([
            // 1-n relationship
            'label'     => 'Branch', // Table column heading
            'type'      => 'select2',
            'name'      => 'branch_id', // the column that contains the ID of that connected entity;
            'entity'    => 'branch', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Branch", // foreign key model
         ],);


         CRUD::column([
            // 1-n relationship
            'label'     => 'Device', // Table column heading
            'type'      => 'select2',
            'name'      => 'device_id', // the column that contains the ID of that connected entity;
            'entity'    => 'device', // the method that defines the relationship in your Model
            'attribute' => 'device_name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Device", // foreign key model
         ],);

         CRUD::column([
            // 1-n relationship
            'name'    => 'type',
            'label'   => 'Type',
            'type'    => 'select2_from_array',
            'options' => ['0' => 'Check In', '1' => 'Check Out', '4' => 'OT In', '5' => 'OT Out']
         ]);


    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */

        
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AttendanceLogRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    private function addFilters()
    {
        // Date Range Filter
        $this->crud->addFilter([
            'type'  => 'date_range',
            'name'  => 'time_in',
            'label' => 'Time In Date Range'
        ], false, function ($value) {
            $dates = json_decode($value);
            if ($dates->from && $dates->to) {
                $this->crud->addClause('where', 'time_in', '>=', $dates->from);
                $this->crud->addClause('where', 'time_in', '<=', $dates->to);
            }
        });

        // Branch Filter
        $this->crud->addFilter([
            'type'  => 'select2',
            'name'  => 'branch_id',
            'label' => 'Branch'
        ], function () {
            return \App\Models\Branch::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->addClause('where', 'branch_id', $value);
        });
    }

    public function downloadPdfReport(Request $request)
    {
        // Build the query with filters
        $query = AttendanceLog::query();
    
        // Apply date range filter
        if ($request->has('time_in')) {
            $dates = json_decode($request->input('time_in'));
            if ($dates && isset($dates->from) && isset($dates->to)) {
                $query->where('time_in', '>=', $dates->from)
                      ->where('time_in', '<=', $dates->to);
            }
        }
    
        // Apply branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
    
        // Fetch the filtered records and group them by branch, date, and employee
        $attendanceLogs = $query->with(['employee', 'branch', 'device'])
            ->get()
            ->groupBy(function ($log) {
                return $log->branch->name . '|' . \Carbon\Carbon::parse($log->time_in)->format('Y-m-d');
            })
            ->map(function ($logsByBranchAndDate) {
                return $logsByBranchAndDate->groupBy('employee.name');
            });
    
        $makimura = public_path('images/makimura.jpg');
        $makimuraLogo = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($makimura));
    
        // Pass grouped logs to the PDF view
        $pdf = Pdf::loadView('reports.attendance_log_pdf', compact('attendanceLogs', 'makimuraLogo'))
            ->setPaper("A4", "landscape");
    
        // Download the PDF
        return $pdf->stream('attendance_logs_report.pdf');
    }
    

    public function downloadXlsReport(Request $request)
    {
        // Build the query with filters
        $query = AttendanceLog::query();
    
        // Apply date range filter
        if ($request->has('time_in')) {
            $dates = json_decode($request->input('time_in'));
            if ($dates && isset($dates->from) && isset($dates->to)) {
                $query->where('time_in', '>=', $dates->from)
                      ->where('time_in', '<=', $dates->to);
            }
        }
    
        // Apply branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
    
        // Fetch filtered records and group them
        $attendanceLogs = $query->with(['employee', 'branch', 'device'])
            ->get()
            ->groupBy(function ($log) {
                $time = \Carbon\Carbon::parse($log->time_in);
            
                // Apply cutoff logic: time-outs (type 1 or 5) before 4 AM belong to the previous day
                if (
                    in_array($log->type, [1, 5]) &&
                    $time->hour < 4
                ) {
                    $time->subDay(); // Move to previous day
                }
            
                return $log->branch->name . '|' . $time->format('Y-m-d');
            })            
            ->map(function ($logsByBranchAndDate) {
                return $logsByBranchAndDate->groupBy('employee.name');
            });
    
        // Pass the filtered data to the export class
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceLogExport($attendanceLogs),
            'attendance_logs_report.xlsx'
        );
    }
    
    
    
    
    

    
}
