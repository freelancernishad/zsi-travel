<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id')->nullable(); // from Amadeus
            $table->string('currency', 10)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->json('flight_offer');
            $table->json('travelers');
            $table->json('contacts');
            $table->json('amadeus_response')->nullable();
            $table->string('payment_gateway')->default('stripe');
            $table->string('payment_status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_bookings');
    }
};
