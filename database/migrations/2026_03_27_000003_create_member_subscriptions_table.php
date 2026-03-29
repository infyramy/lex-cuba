<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('package_name');           // snapshot at time of assignment
            $table->decimal('package_price', 8, 2);  // snapshot at time of assignment
            $table->timestamp('subscribed_at');
            $table->timestamp('expires_at');          // auto-calculated or admin override
            $table->string('notes')->nullable();      // admin internal note
            $table->timestamps();

            // One subscription per member
            $table->unique('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
    }
};
