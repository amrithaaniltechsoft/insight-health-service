<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->foreignId('faq_category_id')->constrained('faq_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['faq_category_id']);
            $table->dropColumn('faq_category_id');
            $table->string('category');
        });
    }
};
