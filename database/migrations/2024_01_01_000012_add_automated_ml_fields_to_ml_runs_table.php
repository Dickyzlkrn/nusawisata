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
        Schema::table('ml_runs', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('dataset_version_id')->index();
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
            $table->string('current_stage')->default('queued')->after('progress');
            $table->json('features')->nullable()->after('parameters');
            $table->unsignedInteger('k')->nullable()->after('features');
            $table->unsignedInteger('iterations')->nullable()->after('k');
            $table->unsignedInteger('seed')->nullable()->after('iterations');
            $table->float('inertia')->nullable()->after('seed');
            $table->float('silhouette_score')->nullable()->after('inertia');
            $table->float('davies_bouldin_score')->nullable()->after('silhouette_score');
            $table->float('calinski_harabasz_score')->nullable()->after('davies_bouldin_score');
            $table->float('mae')->nullable()->after('calinski_harabasz_score');
            $table->float('rmse')->nullable()->after('mae');
            $table->float('precision_at_k')->nullable()->after('rmse');
            $table->float('recall_at_k')->nullable()->after('precision_at_k');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->text('error_message')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_runs', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'progress',
                'current_stage',
                'features',
                'k',
                'iterations',
                'seed',
                'inertia',
                'silhouette_score',
                'davies_bouldin_score',
                'calinski_harabasz_score',
                'mae',
                'rmse',
                'precision_at_k',
                'recall_at_k',
                'completed_at',
                'error_message',
            ]);
        });
    }
};
