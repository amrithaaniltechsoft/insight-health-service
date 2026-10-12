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
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            $table->string('opening_time')->default('09:00');
            $table->string('closing_time')->default('17:00');
            $table->string('public_holidays')->nullable();
            $table->string('same_day_booking')->default('Allowed (Up to 2 hrs before)');
            $table->integer('cancellation_deadline_hours')->default(24);
            $table->text('deposit_amounts')->nullable();
            $table->text('payment_rules')->nullable();
            $table->text('refund_policies')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
