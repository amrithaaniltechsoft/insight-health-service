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
        Schema::create('patient_clinical_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_code')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('clinician_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('report_type'); // e.g. "3D 4D SCAN", "ANOMALY SCAN"
            $table->string('display_title');
            $table->json('report_data')->nullable(); // Form fields payload
            $table->text('summary_findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('status')->default('Finalized');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_reports');
    }
};
