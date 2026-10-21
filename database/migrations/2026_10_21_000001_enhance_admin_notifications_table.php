<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_notifications', 'clinic_id')) {
                $table->foreignId('clinic_id')->nullable()->after('user_id')->constrained('clinics')->nullOnDelete();
            }
            if (!Schema::hasColumn('admin_notifications', 'actor_id')) {
                $table->foreignId('actor_id')->nullable()->after('clinic_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('admin_notifications', 'event_type')) {
                $table->string('event_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('admin_notifications', 'priority')) {
                $table->string('priority')->default('normal')->after('event_type');
            }
            if (!Schema::hasColumn('admin_notifications', 'metadata')) {
                $table->json('metadata')->nullable()->after('link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('admin_notifications', 'actor_id')) {
                $table->dropForeign(['actor_id']);
                $table->dropColumn('actor_id');
            }
            if (Schema::hasColumn('admin_notifications', 'clinic_id')) {
                $table->dropForeign(['clinic_id']);
                $table->dropColumn('clinic_id');
            }
            $columns = [];
            if (Schema::hasColumn('admin_notifications', 'metadata')) $columns[] = 'metadata';
            if (Schema::hasColumn('admin_notifications', 'priority')) $columns[] = 'priority';
            if (Schema::hasColumn('admin_notifications', 'event_type')) $columns[] = 'event_type';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
