<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN, so rebuild the table
        DB::statement('CREATE TABLE "stream_alert_tracking_new" (
            "id" integer primary key autoincrement not null,
            "alert_rule_id" integer not null,
            "stream_twitch_id" varchar not null,
            "streamer_login" varchar not null,
            "triggered_at" datetime,
            foreign key("alert_rule_id") references "alert_rules"("id") on delete cascade
        )');

        DB::statement('INSERT INTO "stream_alert_tracking_new" SELECT * FROM "stream_alert_tracking"');
        Schema::drop('stream_alert_tracking');
        DB::statement('ALTER TABLE "stream_alert_tracking_new" RENAME TO "stream_alert_tracking"');
        DB::statement('CREATE UNIQUE INDEX "stream_alert_tracking_alert_rule_id_stream_twitch_id_unique" ON "stream_alert_tracking" ("alert_rule_id", "stream_twitch_id")');
    }

    public function down(): void
    {
        // Not reversible — triggered_at stays nullable
    }
};
