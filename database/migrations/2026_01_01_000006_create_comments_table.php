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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 255);
            $table->string('user_email', 255);
            $table->text('user_avatar')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('content');
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index('episode_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
