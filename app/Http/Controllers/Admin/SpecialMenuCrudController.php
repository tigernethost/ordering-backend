<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SpecialMenuRequest;
use App\Models\SpecialMenu;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SpecialMenuCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SpecialMenuCrudController extends CrudController
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
        CRUD::setModel(\App\Models\SpecialMenu::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/special-menu');
        CRUD::setEntityNameStrings('special menu', 'special menus');
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
        CRUD::column([
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
        ]);
    
        CRUD::column([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    

        CRUD::column([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    
        CRUD::column([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'check',
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
        CRUD::setValidation(SpecialMenuRequest::class);
    
        CRUD::addField([
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
        ]);
    
        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    
        CRUD::addField([
            'name' => 'products',
            'label' => 'Select Products',
            'type' => 'select2_multiple',
            'entity' => 'products',
            'model' => 'App\Models\Product',
            'attribute' => 'name',
            'pivot' => true,
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    
        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'boolean',
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
        CRUD::column([
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
        ]);
    
        CRUD::column([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    

        CRUD::column([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
    
        CRUD::column([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'check',
        ]);   


        CRUD::column([
            'name' => 'products',
            'label' => 'Select Products',
            'type' => 'select2_multiple',
            'entity' => 'products',
            'model' => 'App\Models\Product',
            'attribute' => 'name',
            'pivot' => true,
        ]);   
    }

    // Soft Delete: Restore a trashed special menu
    public function restore($id)
    {
        $entry = SpecialMenu::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Special menu restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed special menu
    public function forceDelete($id)
    {
        $entry = SpecialMenu::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Special menu permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Special menu is not trashed.');
    }
}
