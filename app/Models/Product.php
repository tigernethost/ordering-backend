<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class Product extends Model implements HasMedia
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;
    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'products';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'sku',
        'barcode',
        'qr_code',
        'image',
        'category_id',
        'is_active',
        'image_large',
        'image_medium',
        'image_small',
        'image_thumbnail',
        'is_special',
        'is_hot',
        'is_top_selling',
        'weight_in_grams',
        'length_cm',
        'width_cm',
        'height_cm'
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::deleting(function ($product) {
    //         $product->deleteImages();
    //     });
    // }

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($model) {
            // Only delete files on force delete, not on soft delete
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                if ($model->image) {
                    Storage::disk('spaces')->delete($model->attributes['image']);
                }
                if ($model->image_large) {
                    Storage::disk('spaces')->delete($model->attributes['image_large']);
                }

                if ($model->image_medium) {
                    Storage::disk('spaces')->delete($model->attributes['image_medium']);
                }

                if ($model->image_small) {
                    Storage::disk('spaces')->delete($model->attributes['image_small']);
                }

                if ($model->image_thumbnail) {
                    Storage::disk('spaces')->delete($model->attributes['image_thumbnail']);
                }
            }
        });


    }


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branches()
    {
        return $this->morphedByMany(Branch::class, 'productable')
                    ->withPivot('slots')
                    ->withPivot('default_slots')
                    ->withTimestamps();
    }


    public function branchOrderIntervals()
    {
        return $this->belongsToMany(BranchOrderIntervals::class, 'product_order_interval', 'product_id', 'order_interval_id');

    }


    public function specialMenus()
    {
        return $this->belongsToMany(SpecialMenu::class, 'special_menu_product');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeActive ($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeInActive ($query)
    {
        return $query->where('is_active', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    
        // Generate the initial slug
        $slug = Str::slug($value);
    
        // Check for uniqueness
        $originalSlug = $slug;
        $counter = 1;
    
        while (static::where('slug', $slug)
        ->where('id', '!=', $this->id)
        ->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
    
        $this->attributes['slug'] = $slug;
    }
    

    public function getImageAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(60); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);

            $temporaryUrl = str_replace(
                config('filesystems.disks.spaces.endpoint'),
                env('DO_SPACES_CDN_ENDPOINT'),
                $temporaryUrl
            );

            \Log::info("temporaryUrl", ['temporaryUrl' => $temporaryUrl]);
            return $temporaryUrl;
        }

        
        return null;
    }


    public function getImageLargeAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(5); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);

            $temporaryUrl = str_replace(
                config('filesystems.disks.spaces.endpoint'),
                env('DO_SPACES_CDN_ENDPOINT'),
                $temporaryUrl
            );
            return $temporaryUrl;
        }

        return null;
    }


    public function getImageMediumAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(5); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);

            $temporaryUrl = str_replace(
                config('filesystems.disks.spaces.endpoint'),
                env('DO_SPACES_CDN_ENDPOINT'),
                $temporaryUrl
            );
            return $temporaryUrl;
        }

        return null;
    }

    public function getImageSmallAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(5); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);

            $temporaryUrl = str_replace(
                config('filesystems.disks.spaces.endpoint'),
                env('DO_SPACES_CDN_ENDPOINT'),
                $temporaryUrl
            );
            return $temporaryUrl;
        }

        return null;
    }

    public function getImageThumbnailAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(5); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);
            $temporaryUrl = str_replace(
                config('filesystems.disks.spaces.endpoint'),
                env('DO_SPACES_CDN_ENDPOINT'),
                $temporaryUrl
            );
            return $temporaryUrl;
        }

        return null;
    }

    
    
    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */


    
    public function setImageAttribute($value)
    {
        try {
            if ($value) {
                $tmpFilePath = '';
    
                // Check if the value is a base64 encoded image
                if (preg_match('/^data:image\/(\w+);base64,/', $value, $type)) {
                    $value = substr($value, strpos($value, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
    
                    if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                        //throw new \Exception('Invalid image type');
                        return;
                    }
    
                    $value = base64_decode($value);
    
                    if ($value === false) {
                        //throw new \Exception('base64_decode failed');
                        return;
                    }
    
                    // Generate a random filename
                    $filename = uniqid() . '.' . $type;
                    $tmpFilePath = sys_get_temp_dir() . '/' . $filename;
                    file_put_contents($tmpFilePath, $value); // Save the decoded image to a temporary file
                } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                    // If the value is a URL, download the file
                    $type = pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $type;
                    $tmpFilePath = sys_get_temp_dir() . '/' . $filename;
    
                    $fileContents = file_get_contents($value);
    
                    if ($fileContents === false) {
                        //throw new \Exception('Failed to download image from URL');
                        return;
                    }
    
                    file_put_contents($tmpFilePath, $fileContents);
                } else {
                    //throw new \Exception('Unsupported image format');
                    \Log::info("Unsupported image format");
                    return;
                }
    
                // Create an image manager instance with the GD driver
                $manager = new ImageManager(new Driver());
    
                // Store the original image
                $disk = 'spaces';
                $destination_path = "products/original";
                $storedPath = Storage::disk($disk)->putFile($destination_path, new File($tmpFilePath));
    
                if ($storedPath === false) {
                    \Log::info("File upload failed");
                    return;
                    //throw new \Exception('File upload failed');
                }
    
                $this->attributes['image'] = $storedPath;
    
                // Resize and store other formats
                $sizes = [
                    'large' => [1024, 768],
                    'medium' => [800, 600],
                    'small' => [400, 300],
                    'thumbnail' => [150, 150],
                ];
    
                foreach ($sizes as $key => [$width, $height]) {
                    // Read and resize the image
                    $image = $manager->read($tmpFilePath)
                        ->scale(width: $width);
    
                    $resizedFilePath = sys_get_temp_dir() . '/' . uniqid() . "_{$key}." . $type;
                    $image->save($resizedFilePath);
    
                    $destination_resized_path = "products/{$key}";
                    $storedResizedPath = Storage::disk($disk)->putFileAs(
                        $destination_resized_path,
                        new File($resizedFilePath),
                        $filename
                    );
    
                    if ($storedResizedPath === false) {
                        \Log::info("Failed to upload {$key} version");
                        return;
                        //throw new \Exception("Failed to upload {$key} version");
                    }
    
                    // Assign the path to the respective column in the database
                    $this->attributes["image_{$key}"] = $storedResizedPath;
    
                    // Clean up the temporary resized file
                    @unlink($resizedFilePath);
                }
    
                // Clean up the original temporary file
                @unlink($tmpFilePath);
            }
        } catch (\Throwable $th) {
            \Log::error('setImageAttribute error', ['message' => $th->getMessage()]);
            throw $th;
        }
    }
    
    
    
}
