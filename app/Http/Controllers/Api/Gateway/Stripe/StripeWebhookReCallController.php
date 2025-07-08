<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Stripe\Stripe;
use Stripe\Invoice;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class StripeWebhookReCallController extends Controller
{




    public function getInvoiceDetailsByCustomerId($customerId)
    {
        try {
            // Set your Stripe secret key
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Fetch invoices for the customer
            $invoices = Invoice::all(['customer' => $customerId]);

            // Prepare an array of invoice details
            $invoiceDetails = array_map(function ($invoice) {
                return [
                    'invoice_id' => $invoice->id,
                    'amount_due' => $invoice->amount_due / 100, // Convert from cents to dollars
                    'amount_paid' => $invoice->amount_paid / 100, // Convert from cents to dollars
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date ? \Carbon\Carbon::createFromTimestamp($invoice->due_date)->toDateTimeString() : null,
                    'billing_reason' => $invoice->billing_reason,
                    'subscription' => $invoice->subscription,
                    'customer' => $invoice->customer,
                    'created_at' => \Carbon\Carbon::createFromTimestamp($invoice->created)->toDateTimeString(),
                ];
            }, $invoices->data);

            // Return the invoice details as a response
            return response()->json([
                'invoice_details' => $invoiceDetails
            ]);

        } catch (\Exception $e) {
            // Handle any exceptions or errors
            return response()->json(['error' => 'Error fetching invoice details: ' . $e->getMessage()], 400);
        }
    }




    /**
     * Fetch the invoice.payment_succeeded body for a specific invoice ID.
     *
     * @param string $invoiceId The ID of the invoice.
     * @return array|null The event details or null if not found.
     */
    public function getInvoicePaymentSucceededBody(string $invoiceId)
    {
        // Initialize Stripe client with your secret key
        $stripe = new StripeClient(env('STRIPE_SECRET'));  // Make sure this is in your .env

        try {
            // List events with filters for invoice.payment_succeeded
            $events = $stripe->events->all([
                'limit' => 1, // You can change this to fetch more events if needed
                'type' => 'invoice.payment_succeeded', // Filter by the event type
            ]);

            // Loop through events to find the one related to the specific invoice ID
            foreach ($events->data as $event) {
                if (isset($event->data->object->id) && $event->data->object->id === $invoiceId) {
                    return $event; // Return the invoice data from the event
                }
            }

            return null; // No event found for the given invoice ID
        } catch (\Exception $e) {
            Log::error('Error fetching Stripe event:', ['message' => $e->getMessage()]);
            return null; // Return null in case of an error
        }
    }

    /**
     * Generate Stripe signature.
     *
     * @param string $payload The event JSON payload.
     * @param string $timestamp The current Unix timestamp.
     * @param string $stripeSecret Your Stripe webhook signing secret.
     * @return string The generated Stripe-Signature header.
     */
    public function generateStripeSignature(string $payload, string $timestamp, string $stripeSecret)
    {
        // Create the signature string: t=timestamp,v1=payload
        $dataToSign = 't=' . $timestamp . ',v1=' . $payload;

        // Log the signature generation for debugging
        Log::info('Generating signature:', [
            'dataToSign' => $dataToSign,
            'stripeSecret' => $stripeSecret
        ]);

        // Generate the signature using HMAC with SHA256
        return "t={$timestamp},v1=" . hash_hmac('sha256', $dataToSign, $stripeSecret);
    }

    /**
     * Send the webhook to the target URL.
     *
     * @param string $webhookUrl The URL to send the webhook to.
     * @param string $stripeSecret The Stripe webhook secret.
     * @param string $invoiceId The invoice ID to use.
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWebhook(string $webhookUrl, string $stripeSecret, string $invoiceId)
{
    // Get the invoice.payment_succeeded event body for the specific invoice
    $eventBody = $this->getInvoicePaymentSucceededBody($invoiceId);

    if (!$eventBody) {
        return response()->json(['error' => 'No event found for the given invoice ID'], 404);
    }

    // Use the event body directly as an object
    $payload = $eventBody;

    // Log the payload for debugging
    Log::info('Payload being sent:', ['payload' => $payload]);

    // Get the current Unix timestamp
    $timestamp = time();

    // Log the timestamp for debugging
    Log::info('Timestamp:', ['timestamp' => $timestamp]);

    // Generate the Stripe-Signature header
    $signature = $this->generateStripeSignature(json_encode($payload), $timestamp, $stripeSecret);

    // Log the generated signature for debugging
    Log::info('Generated Stripe Signature:', ['signature' => $signature]);

    // Send the POST request to the webhook URL with the signature
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Stripe-Signature' => $signature
    ])->post($webhookUrl, $payload);

    // Log the response or return it as JSON
    Log::info('Webhook sent response:', ['response' => $response->body()]);

    return response()->json([
        'message' => 'Webhook sent successfully.',
        'status' => $response->status(),
        'response' => $response->body()
    ]);
}

    /**
     * Test the webhook call by sending the payment_succeeded event for a specific invoice.
     *
     * @param string $invoiceId The invoice ID to use.
     * @return \Illuminate\Http\JsonResponse
     */
    public function testWebhook(string $invoiceId)
    {
        // The Stripe secret from your environment or config file
        $stripeSecret = env('STRIPE_WEBHOOK_SECRET');  // Add your Stripe secret here

        // The webhook URL to send the request to
        $webhookUrl = 'https://api.zsi.ai/api/stripe/webhook';  // Your actual endpoint URL

        // Send the webhook
        return $this->sendWebhook($webhookUrl, $stripeSecret, $invoiceId);
    }
}
