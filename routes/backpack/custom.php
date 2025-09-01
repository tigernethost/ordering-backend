<?php

use App\Http\Controllers\Admin\AttendanceLogCrudController;
use App\Http\Controllers\Admin\OrderCrudController;
use App\Http\Controllers\Admin\OrderReportCrudController;
use App\Http\Controllers\Admin\SalesReportCrudController;
use App\Http\Controllers\Admin\UserCrudController;
use App\Http\Controllers\AttendanceReportController;
use App\Models\OrderReport;
use Illuminate\Support\Facades\Route;


// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('role', 'RoleCrudController');
    Route::crud('attendance', 'AttendanceCrudController');
    Route::crud('device', 'DeviceCrudController');
    Route::crud('employee', 'EmployeeCrudController');
    Route::crud('employee-biometric', 'EmployeeBiometricCrudController');
    Route::crud('branch', 'BranchCrudController');
    Route::crud('department', 'DepartmentCrudController');
    if (config('biometrics.enabled')) {
        Route::crud('attendance-log', 'AttendanceLogCrudController');

        // Attendance Report (depends on biometric attendance data)
        Route::get('attendance-report/pdf', [AttendanceReportController::class, 'exportPdf'])->name('attendance.report.pdf');
        Route::get('attendance-log/download-pdf', [AttendanceLogCrudController::class, 'downloadPdfReport'])->name('attendance.download_pdf');
        Route::get('attendance-log/download-xls', [AttendanceLogCrudController::class, 'downloadXlsReport'])->name('attendance.download_xls');
    }

    // Route::get('attendance-report', [App\Http\Controllers\AttendanceReportController::class, 'index'])->name('attendance.report');
    Route::get('attendance-report', [App\Http\Controllers\AttendanceReportController::class, 'index'])->name('attendance.report');
    Route::get('branch/{branch}/fetch-attendance', [App\Http\Controllers\Admin\BranchCrudController::class, 'fetchAttendance'])->name('admin.branch.fetchAttendance');
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::crud('customer', 'CustomerCrudController');
    // Customer Soft Delete Operations
    Route::post('customer/{id}/restore', [\App\Http\Controllers\Admin\CustomerCrudController::class, 'restore'])->name('customer.restore');
    Route::delete('customer/{id}/force-delete', [\App\Http\Controllers\Admin\CustomerCrudController::class, 'forceDelete'])->name('customer.forceDelete');
    Route::crud('product', 'ProductCrudController');
    Route::crud('category', 'CategoryCrudController');
    // Category Soft Delete Operations
    Route::post('category/{id}/restore', [\App\Http\Controllers\Admin\CategoryCrudController::class, 'restore'])->name('category.restore');
    Route::delete('category/{id}/force-delete', [\App\Http\Controllers\Admin\CategoryCrudController::class, 'forceDelete'])->name('category.forceDelete');

    // Branch Soft Delete Operations
    Route::post('branch/{id}/restore', [\App\Http\Controllers\Admin\BranchCrudController::class, 'restore'])->name('branch.restore');
    Route::delete('branch/{id}/force-delete', [\App\Http\Controllers\Admin\BranchCrudController::class, 'forceDelete'])->name('branch.forceDelete');


    Route::get('admin/attendance-log/download-pdf', [\App\Http\Controllers\Admin\AttendanceLogCrudController::class, 'downloadPdf']);
    Route::get('admin/attendance-log/download-pdf', [\App\Http\Controllers\Admin\AttendanceLogCrudController::class, 'downloadPdfReport']);
    
    Route::crud('branch-order-intervals', 'BranchOrderIntervalsCrudController');
    Route::crud('payment-category', 'PaymentCategoryCrudController');
    Route::crud('payment-method', 'PaymentMethodCrudController');
    Route::crud('order', 'OrderCrudController');
    Route::post('order/change-status', [OrderCrudController::class, 'changeOrderStatus']);
    Route::get('order/{orderId}/print', [OrderCrudController::class, 'printOrder']);

    // Soft Delete Operations
    Route::post('payment-method/{id}/restore', [\App\Http\Controllers\Admin\PaymentMethodCrudController::class, 'restore'])->name('payment-method.restore');
    Route::delete('payment-method/{id}/force-delete', [\App\Http\Controllers\Admin\PaymentMethodCrudController::class, 'forceDelete'])->name('payment-method.forceDelete');
    Route::post('payment-category/{id}/restore', [\App\Http\Controllers\Admin\PaymentCategoryCrudController::class, 'restore'])->name('payment-category.restore');
    Route::delete('payment-category/{id}/force-delete', [\App\Http\Controllers\Admin\PaymentCategoryCrudController::class, 'forceDelete'])->name('payment-category.forceDelete');
    Route::post('special-menu/{id}/restore', [\App\Http\Controllers\Admin\SpecialMenuCrudController::class, 'restore'])->name('special-menu.restore');
    Route::delete('special-menu/{id}/force-delete', [\App\Http\Controllers\Admin\SpecialMenuCrudController::class, 'forceDelete'])->name('special-menu.forceDelete');
    Route::post('department/{id}/restore', [\App\Http\Controllers\Admin\DepartmentCrudController::class, 'restore'])->name('department.restore');
    Route::delete('department/{id}/force-delete', [\App\Http\Controllers\Admin\DepartmentCrudController::class, 'forceDelete'])->name('department.forceDelete');
    Route::post('employee-biometric/{id}/restore', [\App\Http\Controllers\Admin\EmployeeBiometricCrudController::class, 'restore'])->name('employee-biometric.restore');
    Route::delete('employee-biometric/{id}/force-delete', [\App\Http\Controllers\Admin\EmployeeBiometricCrudController::class, 'forceDelete'])->name('employee-biometric.forceDelete');
    Route::post('branch-order-intervals/{id}/restore', [\App\Http\Controllers\Admin\BranchOrderIntervalsCrudController::class, 'restore'])->name('branch-order-intervals.restore');
    Route::delete('branch-order-intervals/{id}/force-delete', [\App\Http\Controllers\Admin\BranchOrderIntervalsCrudController::class, 'forceDelete'])->name('branch-order-intervals.forceDelete');
    Route::post('order/{id}/restore', [\App\Http\Controllers\Admin\OrderCrudController::class, 'restore'])->name('order.restore');
    Route::delete('order/{id}/force-delete', [\App\Http\Controllers\Admin\OrderCrudController::class, 'forceDelete'])->name('order.forceDelete');

    //Route::crud('attendance-report', 'AttendanceReportCrudController');
    Route::crud('special-menu', 'SpecialMenuCrudController');
    Route::crud('paynamics-payment-method', 'PaynamicsPaymentMethodCrudController');
    Route::crud('paynamics-payment-category', 'PaynamicsPaymentCategoryCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('user', 'UserCrudController');
    Route::get('user/{id}/reset-password', [UserCrudController::class, 'resetPassword']);

    Route::crud('order-report', 'OrderReportCrudController');
    Route::get('order-report/download-pdf', [OrderReportCrudController::class, 'downloadPdf'])->name('order-report.download-pdf');
    Route::get('order-report/export-xls', [OrderReportCrudController::class, 'exportXls'])->name('order-report.export-xls');
    Route::crud('sales-report', 'SalesReportCrudController');
    Route::get('sales-report/print/{startDate}/{endDate}', [SalesReportCrudController::class, 'printSalesReport']);
    Route::get('sales-report/export-xls/{startDate}/{endDate}', [SalesReportCrudController::class, 'exportSalesReportToExcel']);
    Route::get('sales-report/get', [SalesReportCrudController::class, 'getSalesReport']);
    if (config('biometrics.enabled')) {
        Route::crud('human-resource', 'HumanResourceCrudController');
    }
    Route::crud('lalamove-place-order', 'LalamovePlaceOrderCrudController');

    // Products Import/Template
    Route::get('product/import', [\App\Http\Controllers\Admin\ProductCrudController::class, 'importForm'])->name('product.import.form');
    Route::post('product/import', [\App\Http\Controllers\Admin\ProductCrudController::class, 'import'])->name('product.import');
    Route::get('product/template', [\App\Http\Controllers\Admin\ProductCrudController::class, 'downloadTemplate'])->name('product.template');

    // Product Soft Delete Operations
    Route::post('product/{id}/restore', [\App\Http\Controllers\Admin\ProductCrudController::class, 'restore'])->name('product.restore');
    Route::delete('product/{id}/force-delete', [\App\Http\Controllers\Admin\ProductCrudController::class, 'forceDelete'])->name('product.forceDelete');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
