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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('razorpay_payment_id', 255)->unique();
            $table->string('razorpay_order_id', 255)->nullable();
            $table->string('razorpay_subscription_id', 255)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['captured', 'failed', 'refunded', 'pending']);
            $table->string('payment_method', 50)->default('upi');
            $table->dateTime('paid_at')->useCurrent();
            $table->timestamps();

            $table->index('razorpay_order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
