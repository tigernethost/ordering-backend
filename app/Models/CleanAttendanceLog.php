<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleanAttendanceLog extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'clean_attendance_logs';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['employee_id', 'emp_id', 'date', 'check_in', 'break_out', 'break_in', 'check_out', 'break_out', 'is_night_shift', 'status', 'missing_parts'];
    protected $casts = [
        'missing_parts' => 'array'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

  


}