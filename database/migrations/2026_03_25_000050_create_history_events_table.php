<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_events', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('stream_twitch_id')->nullable()->index();
            $table->string('streamer_login')->nullable();
            $table->string('streamer_name')->nullable();
            $table->string('category_name')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('viewer_count')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_events');
    }
};
