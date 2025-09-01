<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use app\Http\Requests\UserStoreCrudRequest as StoreRequest;
use app\Http\Requests\UserUpdateCrudRequest as UpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel(config('backpack.permissionmanager.models.user'));
        $this->crud->setEntityNameStrings(trans('backpack::permissionmanager.user'), trans('backpack::permissionmanager.users'));
        $this->crud->setRoute(backpack_url('user'));

        if (!backpack_auth()->user()->can('create user')) {
            $this->crud->denyAccess('create');
        }
        if (!backpack_auth()->user()->can('update user')) {
            $this->crud->denyAccess('update');
        }
        if (!backpack_auth()->user()->can('delete user')) {
            $this->crud->denyAccess('delete');
        }
    }

    public function setupListOperation()
    {
        $this->crud->addButtonFromView('line', 'reset password', 'reset_password', 'beginning');

        $this->crud->addColumns([
            [
                'name'  => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type'  => 'email',
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.roles'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'roles', // the method that defines the relationship in your Model
                'entity'    => 'roles', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.role'), // foreign key model
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.extra_permissions'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'permissions', // the method that defines the relationship in your Model
                'entity'    => 'permissions', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.permission'), // foreign key model
            ],
            [ // n-n relationship for branches
                'label'     => 'Branches', // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'branches', // the method that defines the relationship in your User model
                'entity'    => 'branches', // the method that defines the relationship in your User model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => \App\Models\Branch::class, // Replace with your actual Branch model
            ],
        ]);
    
        if (backpack_pro()) {
            // Role Filter
            $this->crud->addFilter(
                [
                    'name'  => 'role',
                    'type'  => 'dropdown',
                    'label' => trans('backpack::permissionmanager.role'),
                ],
                config('permission.models.role')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'roles', function ($query) use ($value) {
                        $query->where('role_id', '=', $value);
                    });
                }
            );
    
            // Extra Permission Filter
            $this->crud->addFilter(
                [
                    'name'  => 'permissions',
                    'type'  => 'select2',
                    'label' => trans('backpack::permissionmanager.extra_permissions'),
                ],
                config('permission.models.permission')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'permissions', function ($query) use ($value) {
                        $query->where('permission_id', '=', $value);
                    });
                }
            );
    
            // Branch Filter
            $this->crud->addFilter(
                [
                    'name'  => 'branches',
                    'type'  => 'select2',
                    'label' => 'Branches',
                ],
                \App\Models\Branch::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'branches', function ($query) use ($value) {
                        $query->where('branch_id', '=', $value);
                    });
                }
            );
        }
    }
    

    public function setupCreateOperation()
    {
        $this->addUserFields();
        $this->crud->setValidation(\App\Http\Requests\UserStoreCrudRequest::class);
    }
    
    public function setupUpdateOperation()
    {
        $this->addUserFields();
        $this->crud->setValidation(\App\Http\Requests\UserUpdateCrudRequest::class);
    }
    
    
    public function setupShowOperation()
    {
        // Automatically add the columns
        $this->crud->column('name');
        $this->crud->column('email');
        $this->crud->column([
            // Two interconnected entities
            'label'             => trans('backpack::permissionmanager.user_role_permission'),
            'field_unique_name' => 'user_role_permission',
            'type'              => 'checklist_dependency',
            'name'              => 'roles_permissions',
            'subfields'         => [
                'primary' => [
                    'label'            => trans('backpack::permissionmanager.role'),
                    'name'             => 'roles', // the method that defines the relationship in your Model
                    'entity'           => 'roles', // the method that defines the relationship in your Model
                    'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
                    'attribute'        => 'name', // foreign key attribute that is shown to user
                    'model'            => config('permission.models.role'), // foreign key model
                ],
                'secondary' => [
                    'label'          => mb_ucfirst(trans('backpack::permissionmanager.permission_singular')),
                    'name'           => 'permissions', // the method that defines the relationship in your Model
                    'entity'         => 'permissions', // the method that defines the relationship in your Model
                    'entity_primary' => 'roles', // the method that defines the relationship in your Model
                    'attribute'      => 'name', // foreign key attribute that is shown to user
                    'model'          => config('permission.models.permission'), // foreign key model,
                ],
            ],
        ]);
    
        // Add the branches column
        $this->crud->addColumn([
            'label'     => 'Branches', // Label for the column
            'type'      => 'select_multiple', // Use select_multiple for displaying multiple values
            'name'      => 'branches', // The relationship method on the User model
            'entity'    => 'branches', // The relationship method on the User model
            'attribute' => 'name', // The foreign key attribute to display
            'model'     => \App\Models\Branch::class, // The model for the related table
        ]);
    
        $this->crud->column('created_at');
        $this->crud->column('updated_at');
    }
    

    /**
     * Store a newly created resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run
    
        $response = $this->traitStore();
    
        $user = $this->crud->entry;
    
        // Sync roles
        if (request()->has('roles')) {
            $roles = array_keys(request()->get('roles', []));
            $user->syncRoles($roles); // Sync roles
        } else {
            $user->syncRoles([]); // Clear roles if none selected
        }
    
        // Manually checked permissions
        if (request()->has('permissions')) {
            $manualPermissions = array_keys(request()->get('permissions', []));
    
            // Get permissions inherited via roles
            $rolePermissions = $user->getPermissionsViaRoles()->pluck('id')->toArray();
    
            // Store only manually checked permissions
            $finalPermissions = array_diff($manualPermissions, $rolePermissions);
    
            $user->syncPermissions($finalPermissions); // Sync only manually checked permissions
        } else {
            $user->syncPermissions([]); // Clear manually assigned permissions if none selected
        }
    
        return $response;
    }
    

    /**
     * Update the specified resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        $this->crud->setRequest($this->crud->validateRequest());
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run
    
        $response = $this->traitUpdate();
    
        $user = $this->crud->entry;
    
        // Sync roles
        if (request()->has('roles')) {
            $roles = array_keys(request()->get('roles', []));
            $user->syncRoles($roles); // Sync roles
        } else {
            $user->syncRoles([]); // Clear roles if none selected
        }
    
        // Manually checked permissions
        if (request()->has('permissions')) {
            $manualPermissions = array_keys(request()->get('permissions', []));
    
            // Get permissions inherited via roles
            $rolePermissions = $user->getPermissionsViaRoles()->pluck('id')->toArray();
    
            // Store only manually checked permissions
            $finalPermissions = array_diff($manualPermissions, $rolePermissions);
    
            $user->syncPermissions($finalPermissions); // Sync only manually checked permissions
        } else {
            $user->syncPermissions([]); // Clear manually assigned permissions if none selected
        }
    
        return $response;
    }
    

    /**
     * Handle password input fields.
     */
    protected function handlePasswordInput($request)
    {
        // Remove fields not present on the user.
        $request->request->remove('password_confirmation');
        $request->request->remove('roles_show');
        $request->request->remove('permissions_show');

        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', Hash::make($request->input('password')));
        } else {
            $request->request->remove('password');
        }

        return $request;
    }

    protected function addUserFields()
    {
        $this->crud->addFields([
            [
                'name'  => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type'  => 'email',
            ],
            [
                'name'  => 'password',
                'label' => trans('backpack::permissionmanager.password'),
                'type'  => 'password',
            ],
            [
                'name'  => 'password_confirmation',
                'label' => trans('backpack::permissionmanager.password_confirmation'),
                'type'  => 'password',
            ],
            [
                'name'  => 'branches', 
                'label' => 'Branches', 
                'type'  => 'select2_multiple',
                'attribute' => 'name', 
                'pivot' => true,
            ],
            [
                // Grouped roles and permissions UI
                'name'  => 'roles_permissions',
                'type'  => 'view',
                'view'  => 'admin.roles_permissions_column', // Use the new custom Blade view
                'label' => 'User Roles & Permissions',
            ],
        ]);
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        if(!$user) {
            \Alert::warning("User Does Not Exists")->flash();
            abort(404, 'User Account Not Exists');
        }

        $user->update([
            'password' => Hash::make(env('DEFAULT_PASSWORD'))
        ]);

        \Alert::success('Password Reset Successfully')->flash();
        return redirect()->back();
    }
    
}