<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('type')->default('notes')->after('description');
            $table->string('icon_url')->nullable()->after('type');
            $table->integer('sort_order')->default(0)->after('icon_url');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['type', 'icon_url', 'sort_order']);
        });
    }
};
