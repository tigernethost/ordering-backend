<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EmployeeRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EmployeeCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EmployeeCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Employee::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee');
        CRUD::setEntityNameStrings('employee', 'employees');

        if (!backpack_auth()->user()->can('create employee')) {
            $this->crud->denyAccess('create');
        }
        if (!backpack_auth()->user()->can('update employee')) {
            $this->crud->denyAccess('update');
        }
        if (!backpack_auth()->user()->can('delete employee')) {
            $this->crud->denyAccess('delete');
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

        CRUD::column('name')->label('Employee Name');
        CRUD::column('employee_id')->label('Employee ID');
        CRUD::column('position')->label('Position');

        CRUD::addColumn([
            'name' => 'branches', // relationship name
            'label' => 'Branches',
            'type' => 'select_multiple',
            'entity' => 'branches', // the relationship method in the Employee model
            'attribute' => 'name', // attribute to show in the list
            'model' => 'App\Models\Branch', // related model
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
        CRUD::setValidation(EmployeeRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        CRUD::field('name')->label('Employee Name');
        CRUD::field('employee_id')->label('Employee ID');
        CRUD::field('position')->label('Position');

        $this->crud->addField([
            'label' => 'System User',
            'type' => 'select2',
            'name' => 'user_id', // the db column
            'entity' => 'user', // the Eloquent relationship method
            'model' => \App\Models\User::class,
            'attribute' => 'name', // or 'email' or whatever identifier you prefer
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'branches', // relationship name in the Employee model
            'label' => 'Branches',
            'type' => 'select2_multiple',
            'entity' => 'branches', // the method in the Employee model defining the relationship
            'model' => 'App\Models\Branch', // related model
            'attribute' => 'name', // attribute to show in the select
            'pivot' => true, // indicate it's a many-to-many relationship
        ]);

        CRUD::addField([
            'name' => 'departments', // relationship name in the Employee model
            'label' => 'Departments',
            'type' => 'select2_multiple',
            'entity' => 'departments', // method in the Employee model defining the relationship
            'model' => 'App\Models\Department', // related model
            'attribute' => 'name', // attribute to show in the select
            'pivot' => true, // indicates a many-to-many relationship
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
