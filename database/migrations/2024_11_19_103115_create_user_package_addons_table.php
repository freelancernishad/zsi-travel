<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPackageAddonsTable extends Migration
{
    public function up()
    {
        Schema::create('user_package_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('addon_id');
            $table->string('purchase_id'); // Unique identifier for this transaction
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('addon_id')->references('id')->on('package_addons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_package_addons');
    }
}
