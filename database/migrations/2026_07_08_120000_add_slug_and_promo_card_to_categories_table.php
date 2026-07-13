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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('promo_title')->nullable()->after('description');
            $table->text('promo_description')->nullable()->after('promo_title');
            $table->string('promo_link_text')->nullable()->after('promo_description');
            $table->string('promo_link_href')->nullable()->after('promo_link_text');
            $table->string('promo_bg_type')->default('pearl')->after('promo_link_href');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['slug', 'promo_title', 'promo_description', 'promo_link_text', 'promo_link_href', 'promo_bg_type']);
        });
    }
};
