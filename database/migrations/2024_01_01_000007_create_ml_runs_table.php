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
        Schema::create('ml_runs', function (Blueprint $table) {
            $table->id();
            $table->string('algorithm');
            $table->json('parameters')->nullable();
            $table->json('metrics')->nullable();
            $table->json('summary')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_runs');
    }
};
