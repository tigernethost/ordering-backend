<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesReportExport;
use App\Http\Requests\SalesReportRequest;
use App\Models\Order;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Class SalesReportCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SalesReportCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Order::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sales-report');
        CRUD::setEntityNameStrings('sales report', 'sales reports');


        CRUD::addFilter([
            'type'  => 'date_range',
            'name'  => 'created_at',
            'label' => 'Order Time Range'
        ], false, function ($value) {
            $dates = json_decode($value);
            if ($dates->from && $dates->to) {
                $this->crud->addClause('where', 'created_at', '>=', $dates->from);
                $this->crud->addClause('where', 'created_at', '<=', $dates->to);
            }
        });

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

        CRUD::removeButtons(['show', 'create', 'delete', 'update']);

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
        CRUD::setValidation(SalesReportRequest::class);
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


    public function index()
    {
        return view('reports.sales');
    }

    public function getSalesReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Invalid date range'], 400);
        }

        $salesData = Order::selectRaw('branch_id, SUM(total_amount) as total_sales, COUNT(*) as total_orders')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('branch_id')
            ->with('branch:id,name') // Eager load branch details
            ->get()
            ->map(function ($item) {
                return [
                    'branch_name' => $item->branch->name ?? 'Unknown Branch',
                    'total_sales' => $item->total_sales,
                    'total_orders' => $item->total_orders,
                ];
            });


        return response()->json($salesData);
    }

    public function printSalesReport($startDate, $endDate)
    {
        $salesData = Order::selectRaw('branch_id, SUM(total_amount) as total_sales, COUNT(*) as total_orders')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('branch_id')
            ->with('branch:id,name') // Eager load branch details
            ->get();
    
        $makimura = public_path('images/makimura.jpg');
        $makimuraLogo = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($makimura));
    
        // Pass data to the Blade view
        $pdf = Pdf::loadView('reports.sales_log_pdf', compact('salesData', 'startDate', 'endDate', 'makimuraLogo'));
        return $pdf->stream('sales_report.pdf');
    }
    

    public function exportSalesReportToExcel($startDate, $endDate)
    {
        $salesData = Order::selectRaw('branch_id, SUM(total_amount) as total_sales, COUNT(*) as total_orders')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('branch_id')
            ->with('branch:id,name') // Eager load branch details
            ->get()
            ->map(function ($item) {
                return [
                    'branch_name' => $item->branch->name ?? 'Unknown Branch',
                    'total_sales' => $item->total_sales,
                    'total_orders' => $item->total_orders,
                ];
            });
    
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesReportExport($salesData->toArray(), $startDate, $endDate),
            'sales_report.xlsx'
        );
    }
    


}
