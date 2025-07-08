<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialMediaLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_media_links', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Name of the platform, e.g., Facebook, Twitter
            $table->string('url'); // The URL link to the social media page
            $table->string('icon')->nullable(); // Path to icon (URL or file path)
            $table->string('hover_icon')->nullable(); // For hover icon
            $table->integer('index_no')->nullable(); // For custom sorting
            $table->boolean('status')->default(true); // For enabling/disabling links
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_media_links');
    }
}
