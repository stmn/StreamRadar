<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('filter_source')->default('global')->after('use_global_filters');
        });

        // Migrate existing data: use_global_filters=true → 'global', false → 'custom'
        \DB::table('categories')->where('use_global_filters', false)->update(['filter_source' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('filter_source');
        });
    }
};
