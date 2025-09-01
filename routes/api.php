<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HRISController;
use App\Http\Controllers\Api\LalamoveController;
use App\Http\Controllers\Api\OrderController;
use Laravel\Passport\Passport;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //Route::resource('customers', CustomerController::class);
    Route::get('user-profile', [ApiController::class, 'userProfile']);
});

// Customer 
Route::resource('customers', CustomerController::class);
Route::post('update-profile', [CustomerController::class, 'updateProfile']);

// Products
Route::get('products', [ApiController::class, 'getProducts'])->name('products');
Route::get('products/branch/{slug}', [ApiController::class, 'getProductsByBranch']);
Route::get('products/branch/{slug}/hot', [ApiController::class, 'getHotProductsByBranch']);
Route::get('product/{slug}', [ApiController::class, 'getProduct']);
Route::get('products/special-menu', [ApiController::class, 'getSpecialMenu']);
Route::get('products/hot', [ApiController::class, 'getHotProducts']);
Route::get('products/top-selling', [ApiController::class, 'getTopSellingProducts']);


Route::get('categories', [ApiController::class, 'getCategories'])->name('categories');
Route::get('category/{slug}', [ApiController::class, 'getCategory']);

// Branches
Route::get('branches', [ApiController::class, 'getBranches']);
Route::get('branch/{slug}', [ApiController::class, 'getBranch']);
Route::get('branch/{slug}/intervals', [ApiController::class, 'getBranchIntervals']);


// Payment
Route::get('payment-methods', [ApiController::class, 'getPaymentMethods']);

// Orders
Route::post('place-order', [OrderController::class, 'placeOrder']);
Route::get('my-orders', [OrderController::class, 'myOrders']);

Route::get('my-order/{transactionId}', [ApiController::class, 'myOrder'])->name('myorder');


// HRIS

Route::middleware('auth:api')->get('/get-user', [HRISController::class, 'getUser']);

Route::middleware(['client_credentials'])->group(function () {
    Route::get('/get-all-employees', [HRISController::class, 'getAllEmployees']);
    //Route::get('/get-employee-attendance', [HRISController::class, 'getEmployeeAttendance']);
    Route::get('/get-daily-attendance', [HRISController::class, 'getDailyAttendance']);
    
    // attendance dispute related
    Route::get('/employee-attendance-week/{employee_id}', [HRISController::class, 'getEmployeeAttendanceWeek']);
    Route::get('/employee-attendance-range/{employee_id}', [HRISController::class, 'getEmployeeAttendanceRange']);
    Route::get('/employee-attendance/branches', [HRISController::class, 'getBranches']);
    Route::post('/employee-attendance/dispute/approve', [HRISController::class, 'disputeApprove']);
    Route::get('/get-attendance-period', [HRISController::class, 'getAttendanceBaseOnPeriod']);
    Route::get('/get-all-branches', [HRISController::class, 'getAllBranches']);
    Route::get('/get-all-departments', [HRISController::class, 'getAllDepartments']);

    // Attendance logs
    Route::get('/get-clean-attendance', [AttendanceController::class, 'getCleanAttendance']);
    Route::get('/clean-attendance', [AttendanceController::class, 'cleanAttendanceLogs']);

    // Biometrics
    if (config('biometrics.enabled')) {
        Route::get('/get-employee-biometrics', [HRISController::class, 'getEmployeeBiometrics']); 
        Route::get('/get-attendance-logs', [HRISController::class, 'getAttendanceLogs']);
        Route::get('/get-devices', [HRISController::class, 'getDevices']); 
    }
 
});

// Firebase
Route::post('firebase/save-customer', [AuthController::class, 'saveCustomerFromLogin']);
Route::get('customer/details', [AuthController::class, 'checkUserDetails']);

// Lalamove
Route::prefix('lalamove')->group(function () {
    Route::post('quotation/regenerate/{quotationId}', [LalamoveController::class, 'regenerateQuotationId']);
    Route::get('quotations/{qoutationId}', [LalamoveController::class, 'getQuotationDetails']);
    Route::post('place-order', [LalamoveController::class, 'placeOrder']);
    Route::get('orders/{orderId}', [LalamoveController::class, 'getOrder']);
    Route::get('orders/{orderId}/drivers/{driverId}', [LalamoveController::class, 'getDriverDetails']);
    Route::get('track-order/{orderId}', [LalamoveController::class, 'trackFullOrder']);
    Route::patch('register-webhook', [LalamoveController::class, 'lalamoveWebhook']);

    // Lalamove Metadata
    Route::get('/get-city-vehicles', [LalamoveController::class, 'getCityVehicles']);
    Route::get('/get-vehicle-types', [LalamoveController::class, 'getVehicleTypesByCity']);
    Route::get('/vehicles-by-branch', [LalamoveController::class, 'getVehiclesByBranch']);
    Route::get('/get-cities', [LalamoveController::class, 'getCities']);

});

