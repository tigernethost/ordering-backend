<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;


/**
 * Class DepartmentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DepartmentCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings('department', 'departments');
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
        // CRUD::column('name')->label('Employee Name');
        // CRUD::column('employee_id')->label('Employee ID');
        // CRUD::column('position')->label('Position');
        // Display Branches as a comma-separated list
        // CRUD::addColumn([
        //     'name' => 'branches', // relationship name
        //     'label' => 'Branches',
        //     'type' => 'select_multiple',
        //     'entity' => 'branches', // the relationship method in the Employee model
        //     'attribute' => 'name', // attribute to show in the list
        //     'model' => 'App\Models\Branch', // related model
        // ]);

        // Display Departments as a comma-separated list
        // CRUD::addColumn([
        //     'name' => 'departments', // relationship name
        //     'label' => 'Departments',
        //     'type' => 'select_multiple',
        //     'entity' => 'departments', // the relationship method in the Employee model
        //     'attribute' => 'name', // attribute to show in the list
        //     'model' => 'App\Models\Department', // related model
        // ]);
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
        CRUD::setValidation(DepartmentRequest::class);
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

    // Soft Delete: Restore a trashed department
    public function restore($id)
    {
        $entry = Department::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Department restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed department
    public function forceDelete($id)
    {
        $entry = Department::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Department permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Department is not trashed.');
    }
}
