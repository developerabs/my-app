<?php

use App\Http\Controllers\Landlord\PaymentCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/payment/bkash/callback',[PaymentCallbackController::class,'bkashCallback'])->name('payment.bkash.callback');
Route::get('/payment/success/{payment}',[PaymentCallbackController::class,'stripeSuccess'])->name('payment.stripe.success');
Route::get('/payment/cancel/{payment}',[PaymentCallbackController::class,'stripeCancel'])->name('payment.stripe.cancel');