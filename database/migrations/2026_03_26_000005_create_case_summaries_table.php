<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('citation');
            $table->longText('summary_text');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('pdf_file_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['is_published', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_summaries');
    }
};
