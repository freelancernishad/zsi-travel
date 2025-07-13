<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueKeyToFlightBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->uuid('unique_key')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn('unique_key');
        });
    }
}

