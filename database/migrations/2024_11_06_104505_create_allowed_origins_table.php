<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllowedOriginsTable extends Migration
{
    public function up()
    {
        Schema::create('allowed_origins', function (Blueprint $table) {
            $table->id();
            $table->string('origin_url')->unique(); // Store origin URL
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('allowed_origins');
    }
}
