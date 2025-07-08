<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // For user notifications
            $table->unsignedBigInteger('admin_id')->nullable(); // For admin notifications
            $table->string('type')->default('info'); // Notification type (e.g., info, warning, success, error)
            $table->text('message'); // Notification message
            $table->string('related_model')->nullable(); // Related model (e.g., Order, Post)
            $table->unsignedBigInteger('related_model_id')->nullable(); // ID of the related model
            $table->boolean('is_read')->default(false); // Whether the notification has been read
            $table->timestamps(); // created_at and updated_at

            // Foreign key constraints (optional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
