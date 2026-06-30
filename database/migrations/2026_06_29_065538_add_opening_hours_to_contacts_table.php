<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('mon_fri')->nullable()->after('address');
            $table->string('saturday')->nullable()->after('mon_fri');
            $table->string('sunday')->nullable()->after('saturday');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['mon_fri', 'saturday', 'sunday']);
        });
    }
};
