<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('photo_url');
            $table->string('phone')->nullable()->after('gender');
            $table->string('institution')->nullable()->after('phone');
            $table->string('work_study_status')->nullable()->after('institution');
            $table->string('country')->nullable()->after('work_study_status');
            $table->string('status')->default('active')->after('is_active');
            $table->boolean('is_bypassed')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'phone',
                'institution',
                'work_study_status',
                'country',
                'status',
                'is_bypassed',
            ]);
        });
    }
};
