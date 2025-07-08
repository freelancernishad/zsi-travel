<?php

namespace App\Http\Controllers\Api\Global;

use Exception;
use Stripe\Stripe;
use Stripe\Invoice;
use App\Models\User;
use Stripe\Subscription;
use Stripe\PaymentIntent;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class StripeSubscriptionController extends Controller
{
    public function __construct()
    {
        // Set the API key for Stripe (can be set in .env or config/services.php)
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    // Check the status of a user's subscription by userPackage ID
    public function checkSubscriptionStatus($userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage) {
            return response()->json([
                'message' => 'No subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = \Stripe\Subscription::retrieve($userPackage->stripe_subscription_id);

            // Check if the payment collection is paused
            $isPaused = !empty($subscription->pause_collection) ? true : false;

            return response()->json([
                'status' => $subscription->status,
                'payment_collection_paused' => $isPaused, // true if paused, false otherwise
                'pause_behavior' => $isPaused ? $subscription->pause_collection->behavior : null,
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Status Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error retrieving the subscription status.',
            ], 500);
        }
    }

    // Cancel an existing subscription by userPackage ID
    public function cancelSubscription(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            $subscription = \Stripe\Subscription::retrieve($userPackage->stripe_subscription_id);

            if ($subscription->status === 'canceled') {
                return response()->json([
                    'message' => 'The subscription is already canceled.',
                ], 400);
            }

            // Cancel the subscription
            $subscription->cancel();

            // Mark the UserPackage as canceled
            $userPackage->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return response()->json([
                'message' => 'Subscription canceled successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Cancellation Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error canceling the subscription.',
            ], 500);
        }
    }

    public function pausePaymentCollection(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = \Stripe\Subscription::retrieve($userPackage->stripe_subscription_id);

            // Pause the collection of payments without affecting the subscription
            $subscription->pause_collection = ['behavior' => 'mark_uncollectible']; // Or 'keep_as_draft'
            $subscription->save();

            // Update the UserPackage status to 'paused' in your system
            $userPackage->update([
                'status' => 'paused',
                'paused_at' => now(),
            ]);

            return response()->json([
                'message' => 'Payment collection paused successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Pause Payment Collection Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error pausing the payment collection.',
            ], 500);
        }
    }

    public function reactivatePaymentCollection(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'paused') {
            return response()->json([
                'message' => 'No paused subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = \Stripe\Subscription::retrieve($userPackage->stripe_subscription_id);

            // Check if payment collection is paused and unpause
            if (!empty($subscription->pause_collection)) {
                $subscription->pause_collection = null; // Unpause the payment collection
                $subscription->save();
            }

            // Update the UserPackage status to 'active'
            $userPackage->update([
                'status' => 'active',
                'paused_at' => null,
            ]);

            return response()->json([
                'message' => 'Payment collection reactivated successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Reactivate Payment Collection Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error reactivating the payment collection.',
            ], 500);
        }
    }

    // Get transactions for a user
    public function getCustomerTransactions($userId)
    {
        try {
            if (!is_numeric($userId)) {
                return response()->json([
                    'error' => 'Invalid user ID provided.'
                ], 400);
            }

            // Find the user
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'error' => 'User not found.'
                ], 404);
            }

            if (empty($user->stripe_customer_id)) {
                return response()->json([
                    'error' => 'User does not have a Stripe customer ID.'
                ], 400);
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve transactions (payment intents) for the customer
            $transactions = PaymentIntent::all([
                'customer' => $user->stripe_customer_id,
                'limit' => 10, // Fetch last 10 transactions
            ]);

            return response()->json([
                'success' => true,
                'transactions' => $transactions->data
            ], 200);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'error' => 'Stripe API error occurred.',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function getCustomerEvents($userId)
    {
        try {
            if (!is_numeric($userId)) {
                return response()->json([
                    'error' => 'Invalid user ID provided.'
                ], 400);
            }

            // Find the user
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'error' => 'User not found.'
                ], 404);
            }

            if (empty($user->stripe_customer_id)) {
                return response()->json([
                    'error' => 'User does not have a Stripe customer ID.'
                ], 400);
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the subscription details for the customer
            $subscriptions = Subscription::all(['customer' => $user->stripe_customer_id]);

            // If subscriptions exist, retrieve details for each
            $subscriptionDetails = [];
            foreach ($subscriptions->data as $subscription) {
                $invoices = Invoice::all(['customer' => $user->stripe_customer_id, 'subscription' => $subscription->id]);

                foreach ($invoices->data as $invoice) {
                    // Retrieve payment info related to this invoice
                    $paymentIntent = PaymentIntent::retrieve($invoice->payment_intent);

                    // Determine if the payment is successful or failed
                    $paymentStatus = $paymentIntent->status;
                    $paymentStatusText = $paymentStatus === 'succeeded' ? 'Paid' : ($paymentStatus === 'failed' ? 'Failed' : 'Pending');

 


                    // Store the details of the subscription, invoice, and payment
                    $subscriptionDetails[] = [
                        'event_id' => $invoice->id, // Using invoice ID as the event ID
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $paymentIntent->id,
                        'customer_id' => $user->stripe_customer_id,
                        'amount' => $invoice->amount_paid / 100, // Convert from cents to dollars
                        'currency' => $invoice->currency,
                        'created_date' => date('Y-m-d H:i:s', $invoice->created),
                        'payment_status' => $paymentStatusText,

                    ];
                }
            }

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptionDetails
            ], 200);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'error' => 'Stripe API error occurred.',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage()
            ], 500);
        }
    }





    // Recalling webhook manually
    public function recallWebhook($userId, $transactionId)
    {
        try {
            $user = User::find($userId);

            if (!$user || !$user->stripe_customer_id) {
                return response()->json(['error' => 'User not found or no Stripe customer ID.'], 400);
            }


            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the specific PaymentIntent for the transaction
            $paymentIntent = \Stripe\PaymentIntent::retrieve($transactionId);

            // Simulate the event you want to trigger
            // For example, trigger a 'payment_intent.succeeded' event
            $webhookData = [
                'id' => 'evt_test_webhook', // A dummy event ID (use a real event ID if possible)
                'object' => 'event',
                'type' => 'invoice.payment_succeeded', // Set the event type you're simulating
                'data' => [
                    'object' => $paymentIntent
                ]
            ];

            // Manually send the webhook data to your webhook endpoint (simulate webhook trigger)
            $response = Http::post('https://global.softwebsys.com/api/stripe/webhook', $webhookData);

            return response()->json([
                'message' => 'Webhook recalled successfully.',
                'response' => $response->json()
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
