<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderReportRequest;
use App\Models\Order;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Class OrderReportCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderReportCrudController extends CrudController
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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/order-report');
        CRUD::setEntityNameStrings('order report', 'order reports');

        CRUD::addFilter([
            'type'  => 'select2',
            'name'  => 'branch_id',
            'label' => 'Branch'
        ], function () {
            return \App\Models\Branch::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->addClause('where', 'branch_id', $value);
        });


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

        CRUD::addButtonFromView('top',  'download_pdf', 'reports.order_pdf', 'beginning');
        CRUD::addButtonFromView('top', 'download_xls', 'reports.order_xls', 'beginning');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::removeButtons(['show', 'create', 'delete', 'update']);

        CRUD::column([
            'name' => 'customer_id',
            'type' => 'select',
            'label' => 'Customer',
            'entity' => 'customer',
            'attribute' => 'full_name', // This uses the accessor in the model
            'model' => '\App\Models\Customer',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('customer', function ($q) use ($searchTerm) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
                });
            },
        ]);

        CRUD::column([
            'name' => 'branch_id',
            'type' => 'select',
            'label' => 'Branch',
            'entity' => 'branch',
            'attribute' => 'name',
            'model' => '\App\Models\Branch',
        ]);

        CRUD::column([
            'name' => 'order_payment_status',
            'label' => 'Order Payment Status',
            'type' => 'text'
        ]);

        CRUD::column([
            'name' => 'total_amount',
            'label' => 'Order Total Amount',
        ]);

        CRUD::column('status')
            ->label('Order Status')
            ->value(function ($entry) {
                // Map database values to user-friendly labels
                $statusLabels = [
                    'processing' => 'Pending',
                    'for_pickup' => 'For Pickup',
                    'for_delivery' => 'For Delivery',
                    'complete' => 'Complete',
                ];

                return $statusLabels[$entry->status] ?? ucfirst($entry->status); // Default to capitalized status if not mapped
            });


        CRUD::column('order_type')
            ->label('Order Type')
            ->value(function ($entry) {
                return ucfirst($entry->order_type); // Capitalizes the first letter
        });


        CRUD::column('status')
            ->label('Order Status')
            ->value(function ($entry) {
                // Map database values to user-friendly labels
                $statusLabels = [
                    'processing' => 'Order Is Being Processed',
                    'for_pickup' => 'Ready For Pickup',
                    'for_delivery' => 'For Delivery',
                    'complete' => 'Order Complete',
                ];

                return $statusLabels[$entry->status] ?? ucfirst($entry->status); // Default to capitalized status if not mapped
            });


        CRUD::column('order_type')
            ->label('Order Type')
            ->value(function ($entry) {
                return ucfirst($entry->order_type); // Capitalizes the first letter
         });



        CRUD::column([
            'name' => 'created_at',
            'label' => 'Created At',
            'type' => 'datetime',
            'format' => 'MMMM DD YYYY hh:mm A', // Format for "December 10 20389 12:00 AM"
        ]);


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

    public function downloadPdf(Request $request)
    {
        // Build query with filters
        $query = Order::query();

        // Apply date range filter
        if ($request->has('created_at')) {
            $dates = json_decode($request->input('created_at'));
            if ($dates && isset($dates->from) && isset($dates->to)) {
                $query->whereBetween('created_at', [$dates->from, $dates->to]);
            }
        }

        // Apply branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Fetch the filtered data
        $orders = $query->with(['customer', 'branch'])->get();

        $makimura = public_path('images/makimura.jpg');
        $makimuraLogo = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($makimura));

        // Generate PDF
        $pdf = Pdf::loadView('reports.order_log_pdf', compact('orders', 'makimuraLogo'))
            ->setPaper('A4', 'landscape');

        return $pdf->stream('reports.order_report.pdf');
    }

    public function exportXls(Request $request)
    {
        // Build query with filters
        $query = Order::query();

        // Apply date range filter
        if ($request->has('created_at')) {
            $dates = json_decode($request->input('created_at'));
            if ($dates && isset($dates->from) && isset($dates->to)) {
                $query->whereBetween('created_at', [$dates->from, $dates->to]);
            }
        }

        // Apply branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Fetch data for the report
        $orders = $query->with(['customer', 'branch'])->get();

        // Export to Excel
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OrderReportExport($orders),
            'order_report.xlsx'
        );
    }

}
