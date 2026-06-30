<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->integer('order')->default(0);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['faq_category_id']);
            $table->dropColumn('faq_category_id');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
        });

        Schema::dropIfExists('faq_categories');
    }

    public function down(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('faq_categories')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->foreignId('faq_category_id')->constrained('faq_categories')->onDelete('cascade');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'order']);
        });
    }
};
