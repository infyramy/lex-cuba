<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['notes_access', 'case_summaries_access', 'question_bank_access']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('notes_access')->default(false)->after('duration_months');
            $table->boolean('case_summaries_access')->default(false)->after('notes_access');
            $table->boolean('question_bank_access')->default(false)->after('case_summaries_access');
        });
    }
};
