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
        Schema::create('recommendation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('dataset_version_id')->nullable()->constrained('dataset_versions')->nullOnDelete();
            $table->foreignId('ml_run_id')->nullable()->constrained('ml_runs')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('current_stage')->default('loading_profile');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('cluster_id')->nullable();
            $table->unsignedInteger('neighbor_count')->default(0);
            $table->unsignedInteger('recommendation_count')->default(0);
            $table->boolean('is_cold_start')->default(false);
            $table->json('result_payload')->nullable();
            $table->json('stages_log')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_runs');
    }
};
