<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('streamer_login')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('match_mode')->default('always'); // 'first_time' or 'always'
            $table->unsignedInteger('min_viewers')->nullable();
            $table->string('language', 10)->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('notify_browser')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
