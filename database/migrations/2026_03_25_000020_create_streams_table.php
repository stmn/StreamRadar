<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->unique();
            $table->string('user_id')->index();
            $table->string('user_login');
            $table->string('user_name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->default('');
            $table->unsignedInteger('viewer_count')->default(0);
            $table->string('language', 10)->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
