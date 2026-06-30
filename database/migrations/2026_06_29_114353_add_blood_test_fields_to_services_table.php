<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('title')->nullable()->after('service_name');
            $table->text('package_include')->nullable()->after('description2');
            $table->string('turn_around_time')->nullable()->after('package_include');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['title', 'package_include', 'turn_around_time']);
        });
    }
};
