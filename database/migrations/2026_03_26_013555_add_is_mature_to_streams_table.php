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
        Schema::table('streams', function (Blueprint $table) {
            $table->boolean('is_mature')->default(false)->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('is_mature');
        });
    }
};
