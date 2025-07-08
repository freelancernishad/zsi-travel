<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepliesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null'); // Assuming Admin table exists
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Assuming User table exists
            $table->text('reply');
            $table->string('attachment')->nullable();
            $table->foreignId('reply_id')->nullable()->constrained('replies')->onDelete('cascade'); // Parent reply
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
}
