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
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'customer_code')) {
                    $table->string('customer_code')->nullable()->after('id');
                }
                if (!Schema::hasColumn('customers', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('customers', 'address')) {
                    $table->text('address')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('customers', 'nhs_number')) {
                    $table->string('nhs_number')->nullable()->after('address');
                }
                if (!Schema::hasColumn('customers', 'medical_history')) {
                    $table->text('medical_history')->nullable()->after('nhs_number');
                }
                if (!Schema::hasColumn('customers', 'status')) {
                    $table->string('status')->default('active')->after('medical_history');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['customer_code', 'phone', 'address', 'nhs_number', 'medical_history', 'status']);
            });
        }
    }
};
