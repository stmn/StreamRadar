<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            $table->json('category_ids')->nullable()->after('category_id');
        });

        // Migrate existing category_id to category_ids array
        $rules = DB::table('alert_rules')->whereNotNull('category_id')->get();
        foreach ($rules as $rule) {
            DB::table('alert_rules')->where('id', $rule->id)->update([
                'category_ids' => json_encode([$rule->category_id]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('alert_rules', function (Blueprint $table) {
            $table->dropColumn('category_ids');
        });
    }
};
