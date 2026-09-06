<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationRun extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'dataset_version_id',
        'ml_run_id',
        'status',
        'current_stage',
        'progress',
        'cluster_id',
        'neighbor_count',
        'recommendation_count',
        'is_cold_start',
        'result_payload',
        'stages_log',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_cold_start' => 'boolean',
        'progress' => 'integer',
        'cluster_id' => 'integer',
        'neighbor_count' => 'integer',
        'recommendation_count' => 'integer',
        'result_payload' => 'array',
        'stages_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Update current stage and append to stages log.
     */
    public function markStage(string $stage, int $progress, string $label = ''): self
    {
        $log = $this->stages_log ?? [];
        $log[] = [
            'stage' => $stage,
            'label' => $label ?: $stage,
            'progress' => $progress,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update([
            'current_stage' => $stage,
            'progress' => $progress,
            'stages_log' => $log,
            'status' => 'processing',
        ]);

        return $this;
    }

    /**
     * Mark the run as successfully completed.
     *
     * @param  array<string, mixed>  $payload
     */
    public function complete(array $payload, int $recommendationCount = 0): self
    {
        $log = $this->stages_log ?? [];
        $log[] = [
            'stage' => 'completed',
            'label' => 'Rekomendasi siap',
            'progress' => 100,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update([
            'status' => 'completed',
            'current_stage' => 'completed',
            'progress' => 100,
            'result_payload' => $payload,
            'recommendation_count' => $recommendationCount,
            'stages_log' => $log,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the run as failed.
     */
    public function fail(string $message): self
    {
        $this->update([
            'status' => 'failed',
            'current_stage' => 'failed',
            'error_message' => $message,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Target user for this recommendation run.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Active dataset version used in this calculation.
     */
    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    /**
     * Associated K-Means / ML model run.
     */
    public function mlRun(): BelongsTo
    {
        return $this->belongsTo(MlRun::class);
    }
}
