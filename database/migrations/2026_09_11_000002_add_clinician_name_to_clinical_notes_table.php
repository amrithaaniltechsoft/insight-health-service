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
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->string('clinician_name')->nullable()->after('clinician_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->dropColumn('clinician_name');
        });
    }
};
