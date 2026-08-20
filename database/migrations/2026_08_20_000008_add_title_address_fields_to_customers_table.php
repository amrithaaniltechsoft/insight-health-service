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
                if (!Schema::hasColumn('customers', 'title')) {
                    $table->string('title')->nullable()->after('gender');
                }
                if (!Schema::hasColumn('customers', 'address_line_1')) {
                    $table->string('address_line_1')->nullable()->after('address');
                }
                if (!Schema::hasColumn('customers', 'address_line_2')) {
                    $table->string('address_line_2')->nullable()->after('address_line_1');
                }
                if (!Schema::hasColumn('customers', 'suburb')) {
                    $table->string('suburb')->nullable()->after('address_line_2');
                }
                if (!Schema::hasColumn('customers', 'city')) {
                    $table->string('city')->nullable()->after('suburb');
                }
                if (!Schema::hasColumn('customers', 'state')) {
                    $table->string('state')->nullable()->after('city');
                }
                if (!Schema::hasColumn('customers', 'zip_code')) {
                    $table->string('zip_code')->nullable()->after('state');
                }
                if (!Schema::hasColumn('customers', 'country')) {
                    $table->string('country')->nullable()->after('zip_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn([
                    'title', 'address_line_1', 'address_line_2',
                    'suburb', 'city', 'state', 'zip_code', 'country',
                ]);
            });
        }
    }
};
