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
        Schema::create('report_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_report_id')
                ->constrained('patient_clinical_reports')
                ->onDelete('cascade');
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');
            $table->string('report_type');
            $table->json('values')->nullable(); // Form field payload stored as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_values');
    }
};
