<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_alert_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->string('stream_twitch_id');
            $table->string('streamer_login');
            $table->timestamp('triggered_at')->nullable();
            $table->unique(['alert_rule_id', 'stream_twitch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_alert_tracking');
    }
};
