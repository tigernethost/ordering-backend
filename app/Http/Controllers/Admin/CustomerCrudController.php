<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CustomerCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CustomerCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Customer::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/customer');
        CRUD::setEntityNameStrings('customer', 'customers');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
       // CRUD::setFromDb(); // set columns from db columns.
        CRUD::column('first_name');
        CRUD::column('last_name');

        CRUD::column([
            'name' => 'has_account',
            'label' => 'Has Account',
            'type' => 'boolean',
        ]);

        CRUD::column('email');
        CRUD::column('phone');
        CRUD::column('address')->label('Address Line 1');
        CRUD::column('address2')->label('Address Line 2');
        CRUD::column('city');
        CRUD::column('province');
        CRUD::column('region');
        CRUD::column('zip_code');

        //dd(Customer::findOrFail(1)->has_account);

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
        CRUD::setValidation(CustomerRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

        CRUD::field('first_name');
        CRUD::field('last_name');
        CRUD::field('email');
        CRUD::field('phone');
        CRUD::field('address');
        CRUD::field('address2');
        CRUD::field('city');
        CRUD::field('province');
        CRUD::field('region');
        CRUD::field('zip_code');
        CRUD::field('marketing');

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

    // Soft Delete: Restore a trashed customer
    public function restore($id)
    {
        $entry = Customer::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Customer restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed customer
    public function forceDelete($id)
    {
        $entry = Customer::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Customer permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Customer is not trashed.');
    }
}
