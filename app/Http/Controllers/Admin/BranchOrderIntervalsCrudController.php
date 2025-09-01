<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BranchOrderIntervalsRequest;
use App\Models\BranchOrderIntervals;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class BranchOrderIntervalsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BranchOrderIntervalsCrudController extends CrudController
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
        CRUD::setModel(\App\Models\BranchOrderIntervals::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/branch-order-intervals');
        CRUD::setEntityNameStrings('branch order intervals', 'branch order intervals');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        //CRUD::setFromDb(); // set columns from db columns.
        CRUD::column('operating_days')->label('Operating Days');

        // CRUD::column([
        //     'name' => 'products',
        //     'type' => 'select2_multiple',
        //     'label' => 'Products with limited order',
        //     'attribute' => 'name',
        // ]);

        CRUD::column([
            'name'  => 'start', 
            'label' => 'Start Time',
            'type'  => 'text',
        ]);

        CRUD::column([
            'name'  => 'end',
            'label' => 'End Time',
            'type'  => 'text',
        ]);
        
        // Row buttons for trashed entries
        $this->crud->addButtonFromView('line', 'restore_entry', 'restore_entry', 'end');
        $this->crud->addButtonFromView('line', 'force_delete_entry', 'force_delete_entry', 'end');

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
        CRUD::setValidation(BranchOrderIntervalsRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

        CRUD::addField([
            'name'    => 'operating_days',  // Name of the field in your database
            'label'   => 'Operating Days',  // Label to display
            'type'    => 'select_from_array',  // Choose from a predefined set of options
            'options' => [
                'Weekdays' => 'Weekdays',  // Monday to Friday
                'Weekend'  => 'Weekend',   // Saturday and Sunday
                'Everyday' => 'Everyday'   // All days of the week
            ],
            'default' => 'Everyday',  // Set default option if needed
        ]);


        // CRUD::addField([
        //     'name' => 'products',
        //     'type' => 'select2_multiple',
        //     'label' => 'Select a product with limited order with this time',
        //     'attribute' => 'name',
        //     'pivot' => true,
        // ]);

        CRUD::addField([
            'name'  => 'start_time',  
            'label' => 'Start Time', 
            'type'  => 'time',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);
        
        CRUD::addField([
            'name'  => 'end_time', 
            'label' => 'End Time',
            'type'  => 'time', 
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        
        
        
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

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    // Soft Delete: Restore a trashed branch order interval
    public function restore($id)
    {
        $entry = BranchOrderIntervals::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Order interval restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed branch order interval
    public function forceDelete($id)
    {
        $entry = BranchOrderIntervals::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Order interval permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Order interval is not trashed.');
    }
}
