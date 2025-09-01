<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DeviceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Branch;
/**
 * Class DeviceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DeviceCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Device::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/device');
        CRUD::setEntityNameStrings('device', 'devices');
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
        CRUD::setValidation(DeviceRequest::class);
        CRUD::setFromDb(); // set fields from db columns.
        
        $this->crud->addField([
            'label' => 'Branch',
            'type' => 'select2',
            'name' => 'branch_id', // the db column for the foreign key
            'entity' => 'branch',  // the method on the Device model
            'model' => Branch::class, // the related model
            'attribute' => 'name', // the column to show in the select
        ]);

        $this->crud->addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select_from_array',
            'options' => ['online' => 'Online', 'offline' => 'Offline', 'maintenance' => 'Maintenance'],
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
}
