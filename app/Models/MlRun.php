<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MlRun extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dataset_version_id',
        'is_active',
        'algorithm',
        'status',
        'progress',
        'current_stage',
        'parameters',
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
        'metrics',
        'summary',
        'started_at',
        'finished_at',
        'completed_at',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'progress' => 'integer',
            'parameters' => 'array',
            'features' => 'array',
            'k' => 'integer',
            'iterations' => 'integer',
            'seed' => 'integer',
            'inertia' => 'float',
            'silhouette_score' => 'float',
            'davies_bouldin_score' => 'float',
            'calinski_harabasz_score' => 'float',
            'mae' => 'float',
            'rmse' => 'float',
            'precision_at_k' => 'float',
            'recall_at_k' => 'float',
            'metrics' => 'array',
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include the active ML run.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include completed ML runs.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get the single active ML run, or fallback to the latest completed if none is set active yet.
     */
    public static function getActive(): ?self
    {
        $active = static::active()->first();

        if (! $active) {
            $active = static::completed()->latest('id')->first();
            if ($active) {
                // Auto-mark as active for consistency
                $active->update(['is_active' => true]);
            }
        }

        return $active;
    }

    /**
     * Check if this ML run is currently active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Activate this ML run, archiving/deactivating previously active runs.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            // Deactivate all other runs
            static::where('is_active', true)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);

            $this->update([
                'is_active' => true,
            ]);

            // Synchronize active dataset version if linked
            if ($this->dataset_version_id) {
                $dataset = DatasetVersion::find($this->dataset_version_id);
                if ($dataset && ! $dataset->isActive()) {
                    $dataset->activate();
                }
            }

            // Synchronize user cluster assignments if stored in summary
            $assignments = $this->summary['user_assignments'] ?? $this->summary['cluster_assignments'] ?? [];
            if (! empty($assignments)) {
                foreach ($assignments as $uId => $data) {
                    $cId = is_array($data) ? ($data['cluster_id'] ?? null) : (int) $data;
                    $dist = is_array($data) ? ($data['distance'] ?? null) : null;
                    if ($cId !== null) {
                        User::where('id', is_array($data) ? ($data['user_id'] ?? $uId) : $uId)->update([
                            'cluster_id' => $cId,
                            'cluster_distance' => $dist,
                        ]);
                    }
                }
            }

            Cache::flush();
        });
    }

    /**
     * Dataset version associated with this ML run.
     */
    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    /**
     * Recommendation runs associated with this ML run.
     */
    public function recommendationRuns(): HasMany
    {
        return $this->hasMany(RecommendationRun::class);
    }
}
