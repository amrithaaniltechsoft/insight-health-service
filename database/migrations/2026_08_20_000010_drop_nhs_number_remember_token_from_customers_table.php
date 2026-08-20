<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'nhs_number')) {
                    $table->dropColumn('nhs_number');
                }
                if (Schema::hasColumn('customers', 'remember_token')) {
                    $table->dropColumn('remember_token');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('nhs_number')->nullable()->after('address_line_2');
                $table->rememberToken();
            });
        }
    }
};
