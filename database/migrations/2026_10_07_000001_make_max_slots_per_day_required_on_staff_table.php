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
        // Backfill any legacy NULL rows before tightening the column.
        DB::table('staff')->whereNull('max_slots_per_day')->update(['max_slots_per_day' => 10]);

        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedInteger('max_slots_per_day')->default(10)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedInteger('max_slots_per_day')->nullable()->default(null)->change();
        });
    }
};