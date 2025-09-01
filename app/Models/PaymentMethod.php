<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class PaymentMethod extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'payment_methods';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['name', 'payment_category_id', 'url', 'logo', 'active'];
    // protected $hidden = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class);
    }

    public function multisysPayment()
    {
        return $this->hasOne(MultisysPayment::class);
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getLogoAttribute($value)
    {
        if ($value) {
            $disk = Storage::disk('spaces');
            $expiration = now()->addMinutes(5); 

            $temporaryUrl = $disk->temporaryUrl($value, $expiration);
            return $temporaryUrl;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setLogoAttribute($value)
    {
        try {
            if ($value) {
                if (preg_match('/^data:image\/(\w+);base64,/', $value, $type)) {
                    $value = substr($value, strpos($value, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
    
                    if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                        throw new \Exception('invalid image type');
                    }
    
                    $value = base64_decode($value);
    
                    if ($value === false) {
                        throw new \Exception('base64_decode failed');
                    }
                } else {
                    throw new \Exception('did not match data URI with image data');
                }
    
                // Use PHP's native functionality to generate a random filename
                $filename = uniqid() . '.' . $type;
                $tmpFilePath = sys_get_temp_dir() . '/' . $filename;
                file_put_contents($tmpFilePath, $value); // Save the decoded image to a temporary file
    
                // Store the file to DigitalOcean Spaces
                $disk = 'spaces';
                //$destination_path = "announcements";
                $destination_path = "multisys/payment-methods";
                $storedPath = Storage::disk($disk)->putFile($destination_path, new File($tmpFilePath));
    
                $this->attributes['logo'] = $storedPath;
    
                // Clean up the temporary file
                @unlink($tmpFilePath);
            }
        } catch (\Throwable $th) {
            \Log::error('Set logo error', ['message' => $th->getMessage()]);
            throw $th;
        }
        
    }
}
