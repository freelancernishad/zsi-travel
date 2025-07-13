<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightPricingsTable extends Migration
{
    public function up()
    {
        Schema::create('flight_pricings', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_key')->unique(); // Non-human-readable unique ID
            $table->text('full_offer_encoded')->nullable();
            $table->longText('flight_offer_json');
            $table->longText('pricing_response')->nullable();
            $table->longText('seatmap_response')->nullable();
            $table->longText('ancillary_response')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flight_pricings');
    }
}
