<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PaymentCategoryRequest;
use App\Models\PaymentCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PaymentCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PaymentCategoryCrudController extends CrudController
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
        CRUD::setModel(\App\Models\PaymentCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/payment-category');
        CRUD::setEntityNameStrings('payment category', 'payment categories');

        $user = backpack_user();
        
        if (!$user->hasRole('Administrator')) {

            CRUD::denyAccess(['update']);
        }
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
        CRUD::setValidation(PaymentCategoryRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

        CRUD::field('name');
        CRUD::field('description')->type('textarea');
        CRUD::field('active')->type('checkbox');

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

    // Soft Delete: Restore a trashed payment category
    public function restore($id)
    {
        $entry = PaymentCategory::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Payment category restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed payment category
    public function forceDelete($id)
    {
        $entry = PaymentCategory::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Payment category permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Payment category is not trashed.');
    }
}
