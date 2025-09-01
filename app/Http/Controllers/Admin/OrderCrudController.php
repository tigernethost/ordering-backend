<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\ShippingAddress;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Class OrderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OrderCrudController extends CrudController
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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/order');
        CRUD::setEntityNameStrings('order', 'orders');



        $user = backpack_user();
        $branchIds = $user->branches->pluck('id');
        //dd($branchIds, $user->branches);
        
        if (!$user->hasRole('Administrator')) {
            CRUD::addClause('whereHas', 'branch', function ($query) use ($branchIds) {
                $query->whereIn('branches.id', $branchIds);
            });

            CRUD::denyAccess(['update', 'create']);
        }



        CRUD::addFilter([
            'name' => 'for_reservation',
            'type' => 'dropdown',
            'label' => 'Reservation',
        ], [
            1 => 'Yes',
            0 => 'No',
        ], function ($value) {
            // Apply a query based on the presence of the related `reservation`
            if ($value == 1) {
                CRUD::addClause('has', 'reservation');
            } else {
                CRUD::addClause('doesntHave', 'reservation');
            }
        });

        CRUD::addFilter([
            'name' => 'order_type',
            'type' => 'dropdown',
            'label' => 'Order Type',
        ], [
            'pickup' => 'Pickup',
            'delivery' => 'Delivery',
        ], function ($value) {
            CRUD::addClause('where', 'order_type', $value);
        });

        CRUD::addFilter([
            'name' => 'order_status',
            'type' => 'dropdown',
            'label' => 'Order Status',
        ], [
            'pending' => 'Pending',
            'processing' => 'Order Is Being Processed',
            'for_pickup' => 'Ready For Pickup',
            'for_delivery' => 'For Delivery',
            'complete' => 'Order Complete',
        ], function ($value) {
            CRUD::addClause('where', 'status', $value); // Adjusted to match the correct column name
        });



        CRUD::addFilter([
            'type' => 'date',
            'name' => 'created_at',
            'label' => 'Created At',
        ], false, function ($value) {
            CRUD::addClause('whereDate', 'created_at', $value);
        });

        CRUD::denyAccess(['create']);
        $this->crud->addButtonFromView('line', 'update_order_status', 'update_order_status', 'beginning');


    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::enableDetailsRow();
        CRUD::allowAccess('details_row');



        // Row buttons for trashed entries
        $this->crud->addButtonFromView('line', 'restore_entry', 'restore_entry', 'beginning');
        $this->crud->addButtonFromView('line', 'force_delete_entry', 'force_delete_entry', 'beginning');

        CRUD::column([
            'name' => 'order_id',
            'label' => 'Order ID',
        ]);

        CRUD::column([
            'name' => 'branch_id',
            'type' => 'select',
            'label' => 'Branch',
            'entity' => 'branch',
            'attribute' => 'name', // This uses the accessor in the model
            'model' => '\App\Models\Branch'
        ]);


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



        // CRUD::column([
        //     'name' => 'payment_id',
        //     'type' => 'select',
        //     'label' => 'Payment Method',
        //     'entity' => 'multisysPayment',
        //     'attribute' => 'payment_channel', // Use the accessor for display
        //     'model' => '\App\Models\MultisysPayment',
        // ]);

        CRUD::column([
            'name' => 'order_payment_status',
            'label' => 'Order Payment Status',
            'type' => 'text'
        ]);

        // CRUD::column([
        //     'name' => 'total_amount',
        //     'label' => 'Order Total Amount',
        // ]);

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
            'name' => 'for_reservation',
            'label' => 'For Reservation',
            'type' => 'boolean'
        ]);

        CRUD::column([
            'name' => 'created_at',
            'label' => 'Created At',
            'type' => 'datetime',
            'format' => 'MMMM DD YYYY hh:mm A', // Format for "December 10 20389 12:00 AM"
        ]);

        // Filter: Only Trashed
        $this->crud->addFilter([
            'type'  => 'simple',
            'name'  => 'trashed',
            'label' => 'Only Trashed',
        ], false, function () {
            $this->crud->addClause('onlyTrashed');
        });

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
        CRUD::setValidation(OrderRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    protected function setupShowOperation()
    {
        CRUD::column([
            'name' => 'order_id',
            'label' => 'Order ID',
        ]);


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
            'name' => 'payment_id',
            'type' => 'select',
            'label' => 'Payment Method',
            'entity' => 'multisysPayment',
            'attribute' => 'payment_channel', // Use the accessor for display
            'model' => '\App\Models\MultisysPayment',
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
            'name' => 'for_reservation',
            'label' => 'For Reservation',
            'type' => 'boolean'
        ]);

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
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function showDetailsRow($id)
    {
        $order = $this->crud->getEntry($id);
        $orderItems = $order->orderItems;
        $transaction = $order->multisysPayment;
        $customer = $order->customer;
        $shippingAddress = $order->shippingAddress;

        $data = [
            'order' => $order,
            'orderItems' => $orderItems,
            'transaction' => $transaction,
            'customer' => $customer,
            'shippingAddress' => $shippingAddress
        ];



        return view('crud::orders.details_row', $data);
    }
    public function changeOrderStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
            'status' => 'required',
        ]);

        $order = Order::findOrFail($request->id);
        $order->status = $request->status;
        //dd($order);
        $order->save();

        \Alert::success('Order status changed successfully.')->flash();
        return redirect()->back();
    }

    public function printOrder($id)
    {
        $order = Order::with(['customer', 'orderItems.product', 'multisysPayment'])->findOrFail($id);
    
        $pdf = Pdf::loadView('orders.print', compact('order'))
            ->setPaper([0, 0, 162, 1200], 'portrait'); // 2.25in (162pt) width
    
        return $pdf->stream('order-' . $order->order_id . '.pdf');
    }
    
    // Soft Delete: Restore a trashed order
    public function restore($id)
    {
        $entry = Order::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Order restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed order
    public function forceDelete($id)
    {
        $entry = Order::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Order permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Order is not trashed.');
    }
}
