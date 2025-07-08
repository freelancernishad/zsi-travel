<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPackagesTable extends Migration
{
    public function up()
    {
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('ends_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('business_name')->nullable();
            $table->string('stripe_subscription_id')->nullable()->comment('Stripe subscription ID for recurring payments');
            $table->string('stripe_customer_id')->nullable()->comment('Stripe customer ID for recurring payments');
            $table->string('payment_method_type', 50)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->integer('card_exp_month')->nullable();
            $table->integer('card_exp_year')->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('iban_last_four', 4)->nullable();
            $table->string('account_holder_type', 50)->nullable();
            $table->string('account_last_four', 4)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->string('status')->default('active')->comment('Subscription status: active, canceled, expired');
            $table->timestamp('canceled_at')->nullable()->comment('Timestamp when the subscription was canceled');
            $table->timestamp('next_billing_at')->nullable()->comment('Next billing date for recurring subscriptions');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_packages');
    }
}
