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
        Schema::table('blogs', function (Blueprint $table) {
            // Add new columns
            $table->string('slug')->unique()->after('title');
            $table->string('meta_title')->nullable()->after('image');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');

            // Remove unwanted columns
            $table->dropColumn(['author', 'read_time', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['slug', 'meta_title', 'meta_description', 'meta_keywords']);

            // Restore removed columns
            $table->string('author')->after('image');
            $table->integer('read_time')->default(5)->after('author');
            $table->date('date')->after('read_time');
            
        });
    }
};
