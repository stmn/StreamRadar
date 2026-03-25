<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist_rules', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'channel', 'keyword', 'tag'
            $table->string('value');
            $table->string('reason')->nullable();
            // Extra data for channels
            $table->string('twitch_user_id')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
        });

        // Migrate existing ignored_streamers to blacklist_rules
        if (Schema::hasTable('ignored_streamers')) {
            $streamers = \DB::table('ignored_streamers')->get();
            foreach ($streamers as $s) {
                \DB::table('blacklist_rules')->insert([
                    'type' => 'channel',
                    'value' => $s->user_login,
                    'reason' => $s->reason,
                    'twitch_user_id' => $s->twitch_user_id,
                    'profile_image_url' => $s->profile_image_url,
                    'created_at' => $s->created_at,
                    'updated_at' => $s->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_rules');
    }
};
