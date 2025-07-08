<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Gateway\Stripe\StripeController;
use App\Http\Controllers\Api\Global\StripeSubscriptionController;
use App\Http\Controllers\Api\Gateway\Stripe\StripeWebhookReCallController;



Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);


Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
Route::post('/stripe/confirm-payment-intent', [StripeController::class, 'confirmPaymentIntent']);




// Check subscription status by userPackage ID
Route::get('/subscription/status/{userPackageId}', [StripeSubscriptionController::class, 'checkSubscriptionStatus']);

// Cancel subscription by userPackage ID
Route::post('/subscription/cancel/{userPackageId}', [StripeSubscriptionController::class, 'cancelSubscription']);

// Pause a subscription by userPackage ID
Route::post('/subscription/pause/{userPackageId}', [StripeSubscriptionController::class, 'pausePaymentCollection']);

// Reactivate subscription by userPackage ID
Route::post('/subscription/reactivate/{userPackageId}', [StripeSubscriptionController::class, 'reactivatePaymentCollection']);


Route::get('/stripe/transactions/{userId}', [StripeSubscriptionController::class, 'getCustomerTransactions']);
Route::get('/stripe/events/{userId}', [StripeSubscriptionController::class, 'getCustomerEvents']);

Route::post('/stripe/recall-webhook/{userId}/{transactionId}', [StripeSubscriptionController::class, 'recallWebhook']);

Route::get('get-stripe-invoice/{customerId}', [StripeWebhookReCallController::class, 'getInvoiceDetailsByCustomerId']);

Route::get('send-stripe-webhook/{invoiceId}', [StripeWebhookReCallController::class, 'testWebhook']);


