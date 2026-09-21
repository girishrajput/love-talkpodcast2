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
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('target_audience', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('billing_period', 20)->default('year');
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('badge', 100)->nullable();
            $table->json('benefits')->nullable();
            $table->string('razorpay_plan_id', 255)->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
