<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Coupon code, e.g., "SUMMER2024"
            $table->string('type')->default('percentage'); // Type: percentage or fixed
            $table->decimal('value', 8, 2); // Coupon value, e.g., 10% or 20$
            $table->dateTime('valid_from')->nullable(); // Coupon validity start date
            $table->dateTime('valid_until')->nullable(); // Coupon validity end date
            $table->boolean('is_active')->default(true); // Is coupon active
            $table->integer('usage_limit')->nullable(); // Maximum uses
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}

