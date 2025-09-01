<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LalamovePlaceOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

}
