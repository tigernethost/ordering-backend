<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RoleRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RoleCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RoleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/role');
        CRUD::setEntityNameStrings('Role', 'Roles');

        if (!backpack_auth()->user()->can('create role')) {
            $this->crud->denyAccess('create');
        }
        if (!backpack_auth()->user()->can('update role')) {
            $this->crud->denyAccess('update');
        }
        if (!backpack_auth()->user()->can('delete role')) {
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
        CRUD::setFromDb(); // Automatically set columns from database.
    
        // Add a custom column to display permissions
        CRUD::column([
            'name' => 'permissions',
            'label' => 'Permissions',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $permissions = $entry->permissions->pluck('name')->toArray();
    
                return implode(', ', $permissions);
            },
        ]);
    }
    

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RoleRequest::class);
    
        CRUD::field('name')->type('text')->label('Name');
    
        // Add custom permission field
        CRUD::field([
            'name'  => 'permissions', 
            'type'  => 'view',
            'view'  => 'admin.permission_column',
            'label' => 'Permissions',
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

    public function store()
    {
        $response = $this->traitStore();
    
        $role = $this->crud->entry;
    
        if (request()->has('permissions')) {
            $permissions = array_keys(request()->get('permissions', []));
            $role->syncPermissions($permissions); // Sync only selected permissions
        } else {
            $role->syncPermissions([]); // Clear all permissions if none selected
        }
    
        return $response;
    }
    
    public function update()
    {
        $response = $this->traitUpdate();
    
        $role = $this->crud->entry;
    
        if (request()->has('permissions')) {
            $permissions = array_keys(request()->get('permissions', []));
            $role->syncPermissions($permissions); // Sync only selected permissions
        } else {
            $role->syncPermissions([]); // Clear all permissions if none selected
        }
    
        return $response;
    }
    
    
    
    


}
