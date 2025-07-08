<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // For associating payment to a user
            $table->string('gateway')->index(); // 'stripe' or 'paypal'
            $table->string('transaction_id')->unique(); // Unique transaction ID from Stripe/PayPal
            $table->string('currency', 3)->default('USD'); // Currency code (e.g., USD, EUR)
            $table->decimal('amount', 10, 2); // Amount charged
            $table->decimal('fee', 10, 2)->nullable(); // Any transaction fee applied by the gateway
            $table->string('status')->default('pending'); // Payment status (e.g., pending, completed, failed, refunded)

            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->unsignedBigInteger('user_package_id')->nullable();

            // Make sure the referenced column exists and is unsignedBigInteger in user_packages table
            // $table->foreign('user_package_id')
            //     ->references('id')
            //     ->on('user_packages')
            //     ->onDelete('set null');
            // $table->unsignedBigInteger('coupon_id')->nullable();

            // Add foreign key constraint for coupon_id if needed
            // $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            $table->json('response_data')->nullable(); // Store full response from Stripe/PayPal for reference
            $table->string('payment_method')->nullable(); // e.g., 'card', 'paypal_balance', 'bank_transfer'
            $table->string('payer_email')->nullable(); // Email of the payer
            $table->timestamp('paid_at')->nullable(); // Timestamp when payment was completed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
