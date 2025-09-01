<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PaymentMethodCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PaymentMethodCrudController extends CrudController
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
        CRUD::setModel(\App\Models\PaymentMethod::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/payment-method');
        CRUD::setEntityNameStrings('payment method', 'payment methods');

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
       // CRUD::setFromDb(); // set columns from db columns.

        CRUD::column('name')->type('text');

        CRUD::addColumn([
            'name'  => 'payment_category_id',
            'label' => 'Payment Category',
            'type'  => 'select',
            'attribute' => 'name',
            'entity' => 'paymentCategory',
            'model' => '\App\Models\PaymentCategory',
        ]);

        CRUD::column('url')->type('text');

        CRUD::addColumn([
            'name'  => 'logo',
            'label' => 'Logo',
            'type'  => 'image',
            'height' => '30px',
            'width'  => '30px',
        ]);

        CRUD::addColumn([
            'name' => 'active', 
            'label' => 'Active', 
            'type' => 'boolean'
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
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PaymentMethodRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        CRUD::field('name')->type('text');

        CRUD::addField([
            'name'  => 'payment_category_id',
            'label' => 'Payment Category',
            'type'  => 'select2',
            'attribute' => 'name',
            'entity' => 'paymentCategory',
            'model' => '\App\Models\PaymentCategory',
        ]);

        CRUD::field('url')->type('text');

        CRUD::addField([
            'name'  => 'logo',
            'label' => 'Logo',
            'type'  => 'image',
            'crop' => true, // set to true to allow cropping, false to disable

        ]);

        CRUD::addField([
            'name' => 'active', 
            'label' => 'Active', 
            'type' => 'boolean'
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

    // Soft Delete: Restore a trashed payment method
    public function restore($id)
    {
        $entry = PaymentMethod::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Payment method restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed payment method
    public function forceDelete($id)
    {
        $entry = PaymentMethod::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Payment method permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Payment method is not trashed.');
    }
}
