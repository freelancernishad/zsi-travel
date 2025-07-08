<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Top Level", "Professional"
            $table->text('description'); // A brief description of the package
            $table->decimal('price', 10, 2); // Price of the package
            $table->integer('duration_days'); // Duration of the package in days
            $table->json('features'); // JSON to store features specific to each package
            $table->enum('type', ['public', 'private'])->default('public');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
}
