<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackageDiscountsTable extends Migration
{
    public function up()
    {
        Schema::create('package_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id'); // Foreign key to packages table
            $table->integer('duration_months'); // Duration in months
            $table->decimal('discount_rate', 5, 2); // Discount rate in percentage
            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('package_discounts');
    }
}
