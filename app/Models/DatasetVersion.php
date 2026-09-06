<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class DatasetVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'original_filename',
        'file_path',
        'version',
        'status',
        'users_count',
        'destinations_count',
        'ratings_count',
        'provinces_count',
        'uploaded_by',
        'uploaded_at',
        'activated_at',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
        'activated_at' => 'datetime',
        'users_count' => 'integer',
        'destinations_count' => 'integer',
        'ratings_count' => 'integer',
        'provinces_count' => 'integer',
    ];

    /**
     * Scope a query to only include the active dataset version.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include ready dataset versions.
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'ready');
    }

    /**
     * Get the currently active dataset version, or null if none.
     */
    public static function getActive(): ?self
    {
        return static::active()->latest('activated_at')->first();
    }

    /**
     * Determine if this dataset version is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if this dataset version is ready for activation.
     */
    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Activate this dataset version, archiving the previously active version.
     */
    public function activate(): void
    {
        // Archive previously active versions
        static::where('status', 'active')
            ->where('id', '!=', $this->id)
            ->update([
                'status' => 'archived',
            ]);

        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Invalidate recommendation and ML caches
        Cache::flush();
    }

    /**
     * Roll back to this dataset version.
     */
    public function rollbackTo(): void
    {
        $this->activate();
    }

    /**
     * Get the user who uploaded the dataset.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get ML runs associated with this dataset version.
     */
    public function mlRuns(): HasMany
    {
        return $this->hasMany(MlRun::class);
    }

    /**
     * Get the active ML run associated with this dataset version.
     */
    public function activeMlRun(): HasOne
    {
        return $this->hasOne(MlRun::class)->where('is_active', true);
    }

    /**
     * Check if this dataset version is currently referenced by the active ML run.
     */
    public function isReferencedByActiveRun(): bool
    {
        $activeRun = MlRun::getActive();

        return $activeRun && $activeRun->dataset_version_id === $this->id;
    }

    /**
     * Get recommendation runs evaluated against this dataset version.
     */
    public function recommendationRuns(): HasMany
    {
        return $this->hasMany(RecommendationRun::class);
    }

    /**
     * Get destinations imported as part of this dataset version.
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    /**
     * Get ratings imported as part of this dataset version.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
