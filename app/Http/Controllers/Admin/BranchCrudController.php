<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BranchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Device;
use App\Models\Branch;
use App\Jobs\FetchAttendanceLogJob;
use App\Models\BranchOrderIntervals;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Class BranchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BranchCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Branch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/branch');
        CRUD::setEntityNameStrings('branch', 'branches');

        
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
        
        CRUD::setColumns([
            'name', 
            'location',
            'phone'
        ]);

        // CRUD::addColumn([
        //     'name'  => 'products',
        //     'label' => 'Products',
        //     'type'  => 'select_multiple', 
        //     'entity' => 'products',
        //     'model' => Product::class,
        //     'attribute' => 'name', 
        //     'pivot' => true,
        // ]);

        // CRUD::addColumn([
        //     'name'  => 'branchIntervals',
        //     'label' => 'Order Time', 
        //     'type'  => 'relationship', 
        //     'entity' => 'branchIntervals', 
        //     'attribute' => 'interval_name', 
        //     'model' => Branch::class,
        // ]);

        CRUD::addColumn([
            'name'  => 'is_store',
            'label' => 'Store',
            'type'  => 'check',
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
        CRUD::setValidation(BranchRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.


        CRUD::field('name');
        CRUD::field('location');
        CRUD::field('phone');
        CRUD::field('lalamove_loc_code')->label('Lalamove Location Code');

        // CRUD::field('latitude');
        // CRUD::field('longitude');



        CRUD::addField([   // textarea
            'name' => 'map',
            'label' => 'Set Branch Location',
            'type' => 'map',
            'wrapper' => [
                'class' => 'form-group col-md-12 mb-5'
            ],
        ]);


        // CRUD::addField([
        //     'name'      => 'products',       // Relationship field name
        //     'label'     => 'Select Products', // Field label
        //     'type'      => 'checklist',       // Checklist field type
        //     'entity'    => 'products',       // Relationship method in Branch model
        //     'model'     => Product::class,   // Product model
        //     'attribute' => 'name',           // The attribute to display in the checklist
        //     'pivot'     => true,             // Use pivot for many-to-many relationship
        // ]);
        
        $products = Product::all();

        // CRUD::addField([
        //     'name' => 'products', 
        //     'label' => 'Select Products',
        //     'type' => 'branch_product',
        //     'products'  => $products,
        // ]);
    

        CRUD::addField([
            'name' => 'category_products',
            'label' => 'Categories and Products',
            'type' => 'category_products', // Name of the custom field
            'categories' => Category::with('products')->get(), // Fetch categories and their products
        ]);

        CRUD::addField([
            'name'  => 'branchIntervals', 
            'label' => 'Order Intervals', 
            'type'  => 'select2_multiple',
            'attribute' => 'interval_name', 
            'pivot' => true,
        ]);

        CRUD::addField([
            'name'  => 'is_store',
            'label' => 'Store',
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
    
        // Load the existing data for the branch being edited
        $entry = $this->crud->getCurrentEntry();
        $entry->load(['categories', 'products']);
    }
    

    protected function setupShowOperation()
    {
        CRUD::setColumns([
            'name', 
            'location',
        ]);

        CRUD::addColumn([
            'name'  => 'products',
            'label' => 'Products',
            'type'  => 'select_multiple', 
            'entity' => 'products',
            'model' => Product::class,
            'attribute' => 'name', 
            'pivot' => true,
        ]);

        CRUD::addColumn([
            'name'  => 'branchIntervals',
            'label' => 'Order Time', 
            'type'  => 'relationship', 
            'entity' => 'branchIntervals', 
            'attribute' => 'interval_name', 
            'model' => Branch::class,
        ]);

        CRUD::addColumn([
            'name'  => 'is_store',
            'label' => 'Store',
            'type'  => 'check',
        ]);
        
    }


    protected function store(Request $request)
    {
        $response = $this->traitStore(); // Save branch using Backpack's default store
        $branch = $this->crud->getCurrentEntry(); // Get the branch entry that was just created

        $branch->latitude = $request->input('latitude');
        $branch->longitude = $request->input('longitude');
        $branch->save();
    
        // Save categories and their slots and "is shown"
        if ($request->has('categories')) {
            $categories = $request->input('categories'); // Categories selected by the user
            $slots = $request->input('slots', []); // Slots for each category
            $isShown = $request->input('is_shown', []); // "Is Shown" values
            $syncData = [];
    
            foreach ($categories as $categoryId) {
                $syncData[$categoryId] = [
                    'slots' => $slots[$categoryId] ?? 0, // Default to 0 if no slot is provided
                    'default_slots' => $slots[$categoryId] ?? 0, // Default slots to same as slots
                    'is_shown' => isset($isShown[$categoryId]) ? 1 : 0 // Set 'is_shown' to 1 if checked, otherwise 0
                ];
            }
    
            $branch->categories()->sync($syncData); // Sync categories with pivot data
        }
    
        // Save products (if required for another table)
        if ($request->has('products')) {
            $products = $request->input('products'); // Products grouped by category
            foreach ($products as $categoryId => $productIds) {
                foreach ($productIds as $productId) {
                    $branch->products()->attach($productId, [
                        'slots' => $slots[$categoryId] ?? 0
                    ]);
                }
            }
        }
    
        return $response;
    }
    
    

    protected function update(Request $request)
    {
        $response = $this->traitUpdate(); // Save branch using Backpack's default update
        $branch = $this->crud->getCurrentEntry(); // Get the branch entry being updated

        $branch->latitude = $request->input('latitude');
        $branch->longitude = $request->input('longitude');
        $branch->save();

        // Update categories and their slots and "is shown"
        if ($request->has('categories')) {
            $categories = $request->input('categories'); // Categories selected by the user
            $slots = $request->input('slots', []); // Slots for each category
            $isShown = $request->input('is_shown', []); // "Is Shown" values
            $syncData = [];

            foreach ($categories as $categoryId) {
                $syncData[$categoryId] = [
                    'slots' => $slots[$categoryId] ?? 0, // Default to 0 if no slot is provided
                    'default_slots' => $slots[$categoryId] ?? 0, // Default slots to same as slots
                    'is_shown' => isset($isShown[$categoryId]) ? 1 : 0 // Set 'is_shown' to 1 if checked, otherwise 0
                ];
            }

            $branch->categories()->sync($syncData); // Sync categories with pivot data
        }

        // Update products
        if ($request->has('products')) {
            $products = $request->input('products'); // Products grouped by category
            $productSyncData = [];

            foreach ($products as $categoryId => $productIds) {
                foreach ($productIds as $productId) {
                    $productSyncData[$productId] = [
                        'slots' => $slots[$categoryId] ?? 0
                    ];
                }
            }

            $branch->products()->sync($productSyncData); // Sync products with pivot data
        }

        return $response;
    }
    
    

    // Soft Delete: Restore a trashed branch
    public function restore($id)
    {
        $entry = Branch::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->restore();
        }
        return redirect()->back()->with('success', 'Branch restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed branch
    public function forceDelete($id)
    {
        $entry = Branch::withTrashed()->findOrFail($id);
        if ($entry->trashed()) {
            $entry->forceDelete();
            return redirect()->back()->with('success', 'Branch permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Branch is not trashed.');
    }
}
