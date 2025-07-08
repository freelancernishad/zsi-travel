<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // If you want to track which user used the coupon
            $table->timestamp('used_at')->useCurrent(); // Date and time when the coupon was used
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');
    }
}
