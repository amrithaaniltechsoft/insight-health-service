<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('service_name');
            $table->text('service_overview')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('appointment')->nullable();
            $table->string('faq_link')->nullable();
            $table->text('description1')->nullable();
            $table->text('description2')->nullable();
            $table->string('video_link')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
