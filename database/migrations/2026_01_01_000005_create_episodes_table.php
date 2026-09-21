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
        Schema::create('episodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->unsignedInteger('episode_number')->unique();
            $table->string('slug', 255)->unique();
            $table->text('description');
            $table->text('audio_url');
            $table->unsignedInteger('audio_duration'); // seconds
            $table->text('cover_image')->nullable();
            $table->enum('language', ['english', 'hindi', 'bilingual'])->default('english');
            $table->json('tags')->nullable();
            $table->dateTime('publish_date')->useCurrent();
            $table->boolean('is_published')->default(true);
            $table->enum('access_type', ['FREE', 'PREMIUM'])->default('FREE');
            $table->unsignedInteger('preview_duration')->default(60);
            $table->mediumText('transcript_en')->nullable();
            $table->mediumText('transcript_hi')->nullable();
            $table->unsignedInteger('listens_count')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('access_type');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
