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
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('membership_plans')->nullOnDelete();
            $table->enum('status', ['active', 'pending', 'expired', 'cancelled', 'failed', 'paused'])->default('pending');
            $table->string('razorpay_customer_id', 255)->nullable();
            $table->string('razorpay_subscription_id', 255)->nullable();
            $table->dateTime('start_date')->useCurrent();
            $table->dateTime('end_date');
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
