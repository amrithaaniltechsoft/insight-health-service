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
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('appointment_date', 'idx_appointments_date');
            $table->index('status', 'idx_appointments_status');
            $table->index('payment_status', 'idx_appointments_payment_status');
            $table->index(['appointment_date', 'status'], 'idx_appointments_date_status');
            $table->index(['staff_id', 'appointment_date'], 'idx_appointments_staff_date');
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->index('is_read', 'idx_notif_is_read');
            $table->index(['user_id', 'is_read'], 'idx_notif_user_read');
            $table->index('created_at', 'idx_notif_created_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'idx_payments_status');
            $table->index('created_at', 'idx_payments_created_at');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index(['first_name', 'last_name'], 'idx_patients_name');
            $table->index('status', 'idx_patients_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_date');
            $table->dropIndex('idx_appointments_status');
            $table->dropIndex('idx_appointments_payment_status');
            $table->dropIndex('idx_appointments_date_status');
            $table->dropIndex('idx_appointments_staff_date');
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_is_read');
            $table->dropIndex('idx_notif_user_read');
            $table->dropIndex('idx_notif_created_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_created_at');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_name');
            $table->dropIndex('idx_patients_status');
        });
    }
};
