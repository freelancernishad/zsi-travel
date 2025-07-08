<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomPackageRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_package_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            // Add foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->string('name'); // Name of the requester
            $table->string('email'); // Email of the requester
            $table->string('business')->nullable(); // Business name (optional)
            $table->string('phone')->nullable(); // Phone number (optional)
            $table->string('website')->nullable(); // Website URL (optional)
            $table->text('service_description'); // Description of the requested service
            $table->string('status')->default('pending'); // Request status (pending, in_progress, completed, rejected)
            $table->text('admin_notes')->nullable(); // Notes added by the admin
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_package_requests');
    }
}
