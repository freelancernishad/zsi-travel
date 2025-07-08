<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmadeusTokensTable extends Migration
{
    public function up()
    {
        Schema::create('amadeus_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('username')->nullable();
            $table->string('application_name')->nullable();
            $table->string('client_id')->nullable();
            $table->string('token_type')->nullable();
            $table->text('access_token');
            $table->integer('expires_in');
            $table->string('state')->nullable();
            $table->string('scope')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('amadeus_tokens');
    }
}
