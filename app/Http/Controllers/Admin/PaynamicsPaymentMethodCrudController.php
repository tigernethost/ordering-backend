<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PaynamicsPaymentMethodRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

/**
 * Class PaynamicsPaymentMethodCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PaynamicsPaymentMethodCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
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
        CRUD::setModel(\App\Models\PaynamicsPaymentMethod::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/paynamics-payment-method');
        CRUD::setEntityNameStrings('paynamics payment method', 'paynamics payment methods');
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
        //CRUD::setValidation(PaynamicsPaymentMethodRequest::class);
        // CRUD::setFromDb(); // set fields from db columns.

        CRUD::addField([
            'name'  => 'payment_category_id',
            'label' => 'Payment Category',
            'type'  => 'select2',
            'attribute' => 'name',
            'entity' => 'paynamicsPaymentCategory',
            'model' => '\App\Models\PaynamicsPaymentCategory',
        ]);

        CRUD::addField([
            'name'  => 'name',
            'label' => 'Name',
            'type'  => 'text', 
        ]);

        CRUD::addField([
            'name'  => 'code',
            'label' => 'Code',
            'type'  => 'text', 
        ]);

        CRUD::addField([
            'name'  => 'fee',
            'label' => 'Fee (%)',
            'type'  => 'text', 

        ]);

        CRUD::addField([
            'name'  => 'minimum_fee',
            'label' => 'Minimum Fee - Fixed Amount (PHP)',
            'type'  => 'text', 

        ]);

        CRUD::addField([
            'name'  => 'description',
            'label' => 'Description',
            'type'  => 'textarea', 

        ]);


        CRUD::addField([
            'name'  => 'logo',
            'label' => 'Logo',
            'type'  => 'image',
            'crop' => true,  

        ]);

        CRUD::addField([
            'label' => "Icon",
            'name' => 'icon',
            'type' => 'icon_picker',
            'iconset' => 'fontawesome',
            'wrapperAttributes' => [ 'class' => 'form-group col-md-6 col-xs-12' ]
        ]);

        CRUD::addField([
            'name'  => 'active',
            'label' => 'Active',
            'type'  => 'boolean', 
        ]);

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

    // protected function store(Request $request)
    // {
    //     dd($request->all());
    // }
}
