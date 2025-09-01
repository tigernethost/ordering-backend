<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Exports\ProductTemplateExport;
/**
 * Class ProductCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Product::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product');
        CRUD::setEntityNameStrings('product', 'products');

        // Register buttons in List operation to ensure they appear
        $this->crud->operation('list', function () {
            $this->crud->addButtonFromView('top', 'product_template', 'product_template', 'beginning');
            $this->crud->addButtonFromView('top', 'product_import', 'product_import', 'beginning');
            $this->crud->addButtonFromView('bottom', 'product_template_bottom', 'product_template', 'beginning');
            $this->crud->addButtonFromView('bottom', 'product_import_bottom', 'product_import', 'beginning');
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Top/Bottom buttons: Template download and Import
        $this->crud->addButtonFromView('top', 'product_template', 'product_template', 'beginning');
        $this->crud->addButtonFromView('top', 'product_import', 'product_import', 'beginning');
        $this->crud->addButtonFromView('bottom', 'product_template_bottom', 'product_template', 'beginning');
        $this->crud->addButtonFromView('bottom', 'product_import_bottom', 'product_import', 'beginning');
        // Row buttons: Restore / Force Delete (shown conditionally in blade when trashed)
        $this->crud->addButtonFromView('line', 'restore_entry', 'restore_entry', 'end');
        $this->crud->addButtonFromView('line', 'force_delete_entry', 'force_delete_entry', 'end');
        CRUD::column('name');
        CRUD::column('price');
        CRUD::column('stock');
        CRUD::column('sku');
        CRUD::column('barcode');
        CRUD::column('qr_code');
        CRUD::addColumn([
            'name' => 'weight_in_grams',
            'label' => 'Weight (g)',
            'type' => 'number',
            'decimals' => 2, // Only use this if it's decimal
            'suffix' => ' g',
        ]);

        CRUD::addColumn([
            'name' => 'length_cm',
            'label' => 'Length (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);
        CRUD::addColumn([
            'name' => 'width_cm',
            'label' => 'Width (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);
        CRUD::addColumn([
            'name' => 'height_cm',
            'label' => 'Height (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);
        

        CRUD::addColumn([
            'name' => 'image', 
            'label' => 'Image',
            'type' => 'image',
            'height' => '50px',
            'width' => '50px',
        ]);
        

        CRUD::addColumn([
            'name' => 'category_id', 
            'label' => 'Category',
            'type' => 'select2',
            'entity' => 'category',
            'attribute' => 'name',
        ]);
        
        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'boolean',
        ]);
        CRUD::column('created_at')->type('datetime');

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
        // CRUD::setValidation(ProductRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

        // Basic Information
        CRUD::addField([
            'name' => 'basic_info_separator',
            'type' => 'custom_html',
            'value' => '<h5 style="margin-top:10px">Basic Information</h5><hr>'
        ]);
        CRUD::field('name');
        CRUD::field('description')->type('ckeditor');

        // Inventory & Identifiers
        CRUD::addField([
            'name' => 'inventory_separator',
            'type' => 'custom_html',
            'value' => '<h5 style="margin-top:10px">Inventory & Identifiers</h5><hr>'
        ]);
        CRUD::field('price');
        CRUD::field('stock');
        CRUD::field([
            'name' => 'sku',
            'type' => 'text',
            'hint' => 'Optional stock keeping unit (unique per product recommended).'
        ]);
        CRUD::field([
            'name' => 'barcode',
            'type' => 'text',
            'label' => 'Barcode',
            'hint' => 'Optional barcode value (e.g., EAN/UPC).'
        ]);
        CRUD::field([
            'name' => 'qr_code',
            'type' => 'text',
            'label' => 'QR Code',
            'hint' => 'Optional QR code content (text/URL).'
        ]);

        CRUD::field([
            'name' => 'category_id', 
            'label' => 'Category',
            'type' => 'select2',
            'entity' => 'category',
            'attribute' => 'name',
            'model' => '\App\Models\Category',
        ]);

        CRUD::addField([
            'name'  => 'weight_in_grams',
            'label' => 'Weight (grams)',
            'type'  => 'number',
            'attributes' => [
                'step' => '0.01', // allows decimals
                'min'  => '0',
            ],
        ]);

        // Dimensions
        CRUD::addField([
            'name' => 'dimensions_separator',
            'type' => 'custom_html',
            'value' => '<h5 style="margin-top:10px">Dimensions (cm)</h5><hr>'
        ]);
        CRUD::addField([
            'name'  => 'length_cm',
            'label' => 'Length (cm)',
            'type'  => 'number',
            'wrapper' => [
                'class' => 'form-group col-12 col-md-4'
            ],
            'attributes' => [
                'step' => '0.01',
                'min'  => '0',
            ],
        ]);
        CRUD::addField([
            'name'  => 'width_cm',
            'label' => 'Width (cm)',
            'type'  => 'number',
            'wrapper' => [
                'class' => 'form-group col-12 col-md-4'
            ],
            'attributes' => [
                'step' => '0.01',
                'min'  => '0',
            ],
        ]);
        CRUD::addField([
            'name'  => 'height_cm',
            'label' => 'Height (cm)',
            'type'  => 'number',
            'wrapper' => [
                'class' => 'form-group col-12 col-md-4'
            ],
            'attributes' => [
                'step' => '0.01',
                'min'  => '0',
            ],
        ]);

        CRUD::field([
            'label' => 'Product Image',
            'name' => 'image',
            'type' => 'image',
            'disk' => 'spaces',
            'upload' => true,
            'crop' => true, 
            'aspect_ratio' => 1, 
        ]);

        CRUD::field([
            'name' => 'is_special', 
            'label' => 'Special',
            'type' => 'boolean'
        ]);

        CRUD::field([
            'name' => 'is_hot', 
            'label' => 'Hot',
            'type' => 'boolean'
        ]);

        CRUD::field([
            'name' => 'is_top_selling', 
            'label' => 'Selling',
            'type' => 'boolean'
        ]);

        CRUD::field([
            'name' => 'is_active', 
            'label' => 'Active',
            'type' => 'boolean'
        ]);
        

    }


    public function setupShowOperation()
    {
        CRUD::column('name');
        CRUD::column('price');
        CRUD::column('stock');
        CRUD::column('sku');
        CRUD::column('slug');
        
        CRUD::addColumn([
            'name' => 'category_id', 
            'label' => 'Category',
            'type' => 'select2',
            'entity' => 'category',
            'attribute' => 'name',

        ]);

        CRUD::addColumn([
            'name' => 'image', 
            'label' => 'Image',
            'type' => 'image',
        ]);

        CRUD::addColumn([
            'name' => 'image_large', 
            'label' => 'Image Large',
            'type' => 'image',
        ]);

        CRUD::addColumn([
            'name' => 'image_medium', 
            'label' => 'Image Medium',
            'type' => 'image',
        ]);


        CRUD::addColumn([
            'name' => 'image_small', 
            'label' => 'Image Small',
            'type' => 'image',
        ]);

        CRUD::addColumn([
            'name' => 'image_thumbnail', 
            'label' => 'Image Thumbnail',
            'type' => 'image',
        ]);

        CRUD::addColumn([
            'name' => 'barcode',
            'label' => 'Barcode',
            'type' => 'text',
        ]);
        CRUD::addColumn([
            'name' => 'qr_code',
            'label' => 'QR Code',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'weight_in_grams',
            'label' => 'Weight (g)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' g',
        ]);
        CRUD::addColumn([
            'name' => 'length_cm',
            'label' => 'Length (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);
        CRUD::addColumn([
            'name' => 'width_cm',
            'label' => 'Width (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);
        CRUD::addColumn([
            'name' => 'height_cm',
            'label' => 'Height (cm)',
            'type' => 'number',
            'decimals' => 2,
            'suffix' => ' cm',
        ]);

        CRUD::addColumn([
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

    // Custom: Import/Template
    public function importForm()
    {
        return view('admin.products.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new ProductsImport, $request->file('file'));

        
        
        return redirect()->back()->with('success', 'Products imported successfully.');
    }

    public function downloadTemplate()
    {
        $filename = 'product_import_template.xlsx';
        return Excel::download(new ProductTemplateExport, $filename);
    }

    // Soft Delete: Restore a trashed product
    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        if ($product->trashed()) {
            $product->restore();
        }
        return redirect()->back()->with('success', 'Product restored successfully.');
    }

    // Soft Delete: Permanently delete a trashed product
    public function forceDelete($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        if ($product->trashed()) {
            $product->forceDelete();
            return redirect()->back()->with('success', 'Product permanently deleted.');
        }
        return redirect()->back()->with('warning', 'Product is not trashed.');
    }

}

