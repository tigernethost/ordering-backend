<?php

use App\Events\NewOrderPlaced;
use App\Http\Controllers\Api\LalamoveController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MultisysPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentControllerV2;
use App\Http\Controllers\PaynamicsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserSyncController;
use App\Livewire\ViewOrder;
use App\Models\MultisysPayment;
use App\Models\Order;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/get-attendance', );

Route::post('/device/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'receiveRecords'])->middleware('throttle:iclock');

// Route::get('/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'handleCallbackGet']);

Route::get('/device/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'handshake'])->middleware('throttle:iclock');


Route::get('/iclock/getrequest', [\App\Http\Controllers\AttendanceCallbackController::class, 'getRequest']);




Route::get('/device/iclock/getrequest', [\App\Http\Controllers\AttendanceCallbackController::class, 'getRequest'])->middleware('throttle:iclock');

Route::get('/device/iclock/devicecmd', [\App\Http\Controllers\AttendanceCallbackController::class, 'devicecmd'])->middleware('throttle:iclock');

// Route::post('/users/register', [UserSyncController::class, 'registerAndSync']);


Route::post('/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'receiveRecords'])->middleware('throttle:iclock');

// Route::get('/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'handleCallbackGet']);

Route::get('/iclock/cdata', [\App\Http\Controllers\AttendanceCallbackController::class, 'handshake'])->middleware('throttle:iclock');

Route::get('/iclock/getrequest', [\App\Http\Controllers\AttendanceCallbackController::class, 'getRequest'])->middleware('throttle:iclock');

Route::get('/iclock/devicecmd', [\App\Http\Controllers\AttendanceCallbackController::class, 'devicecmd'])->middleware('throttle:iclock');

// Checkout

Route::get('checkout', [PaymentController::class, 'index']);
Route::post('checkout/submit', [PaymentController::class, 'submit'])->name('checkout.submit');


/*
|--------------------------------------------------------------------------
| (MULTISYS WEBHOOKS)
|--------------------------------------------------------------------------
*/
Route::post('/online_payment/multisys/response', [MultisysPaymentController::class, 'webhookResponse'])-> name('online_payment.multisys.response');
Route::get('online_payment/confirmation', [MultisysPaymentController::class, 'confirmation']);


/*
|--------------------------------------------------------------------------
| (PAYNAMICS WEBHOOKS)
|--------------------------------------------------------------------------
*/
Route::get('online-payment/paynamics/response/{request_id}', [PaynamicsController::class, 'responseURL'])->name('online_payment.response_url');
Route::post('online-payment/paynamics/response', [PaynamicsController::class, 'webhookResponse'])->name('online_payment.post_webhook_response');
Route::post('online-payment/paynamics/notification', [PaynamicsController::class, 'webhookNotification'])->name('online_payment.post_webhook_notification');
Route::post('online-payment/paynamics/cancel', [PaynamicsController::class, 'webhookCancel'])->name('online_payment.post_webhook_cancel');
Route::get('online-payment/paynamics/cancel', [PaynamicsController::class, 'cancelURL'])->name('online_payment.get_webhook_cancel');

Route::get('online-payment/instructions/{request_id}', [PaymentControllerV2::class, 'showInstructions'])->name('online_payment.instructions');


/*
|--------------------------------------------------------------------------
| (LALAMOVE WEBHOOKS)
|--------------------------------------------------------------------------
*/

Route::post('lalamove/response', [LalamoveController::class, 'webhookResponse']);


// Livewire

Route::get('admin/order/{id}/view', ViewOrder::class)->name('admin.order.view');