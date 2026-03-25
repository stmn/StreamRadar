<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->unique();
            $table->string('name');
            $table->string('box_art_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('use_global_filters')->default(true);
            $table->unsignedInteger('min_viewers')->nullable();
            $table->json('languages')->nullable();
            $table->json('keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
