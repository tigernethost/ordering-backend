<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'branches';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    protected $fillable = ['name', 'slug', 'location', 'lalamove_loc_code', 'phone', 'is_store', 'longitude', 'latitude'];

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

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'branch_employee');
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function products()
    {
        return $this->morphToMany(Product::class, 'productable')
                    ->withPivot('slots')
                    ->withPivot('default_slots')
                    ->withTimestamps();
    }


    public function branchIntervals()
    {
        return $this->belongsToMany(BranchOrderIntervals::class, 'branch_interval', 'branch_id', 'order_interval_id');
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_branch', 'branch_id', 'user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'branch_category')->withPivot('slots')->withPivot('default_slots')->withPivot('is_shown');
    }



    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

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
            // Append a counter to the slug if it already exists
            $slug = $originalSlug . '-' . $counter++;
        }
    
        $this->attributes['slug'] = $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
