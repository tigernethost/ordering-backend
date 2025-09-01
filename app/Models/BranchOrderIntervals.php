<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchOrderIntervals extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'branch_order_intervals';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['operating_days', 'start_time', 'end_time'];
    protected $appends = ['is_available'];

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

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_interval', 'order_interval_id', 'branch_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_order_interval', 'order_interval_id', 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function getIntervalNameAttribute()
    {
        // Fetch the related product names and join them with a comma
        $productNames = $this->products->pluck('name')->implode(', ');
    
        if ($productNames) {
            $name = $this->operating_days . ' (' . $this->start . ' - ' . $this->end . ') - limited slots for products: ' . $productNames;
        } else {
            $name = $this->operating_days . ' (' . $this->start . ' - ' . $this->end . ')';
        }
    
        return $name;
    }
    
    
    public function getStartAttribute()
    {
        return Carbon::createFromFormat('H:i', $this->start_time)->format('h:i A');
    }


    public function getEndAttribute()
    {
        return Carbon::createFromFormat('H:i', $this->end_time)->format('h:i A');
    }

    public function getIsAvailableAttribute()
    {
        $currentTime = Carbon::now()->format('H:i');
        $startTime = Carbon::createFromFormat('H:i', $this->attributes['start_time'])->format('H:i');
        $endTime = Carbon::createFromFormat('H:i', $this->attributes['end_time'])->format('H:i');

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
