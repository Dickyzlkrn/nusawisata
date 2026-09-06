<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dataset_version_id',
        'place_id',
        'province_id',
        'name',
        'slug',
        'category',
        'description',
        'price',
        'latitude',
        'longitude',
        'google_rating',
        'cluster_id',
        'cluster_distance',
        'review_count',
        'image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dataset_version_id' => 'integer',
            'place_id' => 'integer',
            'price' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
            'google_rating' => 'float',
            'cluster_id' => 'integer',
            'cluster_distance' => 'float',
            'review_count' => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the province of this destination.
     *
     * @return BelongsTo<Province, Destination>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get user ratings for this destination.
     *
     * @return HasMany<Rating>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get popularity scores for this destination.
     *
     * @return HasMany<DestinationPopularity>
     */
    public function popularities(): HasMany
    {
        return $this->hasMany(DestinationPopularity::class);
    }

    /**
     * Get formatted Indonesian Rupiah price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price <= 0) {
            return 'Gratis';
        }

        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    /**
     * Get effective rating (user rating average or fallback to google_rating).
     */
    public function getEffectiveRatingAttribute(): float
    {
        $avg = $this->ratings()->avg('rating');

        return $avg ? round((float) $avg, 1) : (float) $this->google_rating;
    }

    /**
     * Scope query for featured or top-rated destinations.
     */
    public function scopeTopRated(Builder $query, int $limit = 6): Builder
    {
        return $query->orderByDesc('google_rating')
            ->orderByDesc('review_count')
            ->limit($limit);
    }

    /**
     * Scope query for destinations belonging to the currently active dataset version.
     */
    public function scopeForActiveDataset(Builder $query): Builder
    {
        $activeVersion = DatasetVersion::getActive();

        if (! $activeVersion) {
            return $query;
        }

        // If this dataset version has its own destination catalog rows, scope to them
        if ($activeVersion->destinations()->exists()) {
            return $query->where(function ($q) use ($activeVersion) {
                $q->where('dataset_version_id', $activeVersion->id)
                    ->orWhereNull('dataset_version_id');
            });
        }

        // Otherwise (interaction dataset), include destinations that have ratings in this version or are global
        return $query->where(function ($q) use ($activeVersion) {
            $q->whereHas('ratings', function ($rq) use ($activeVersion) {
                $rq->where('dataset_version_id', $activeVersion->id);
            })->orWhereNull('dataset_version_id');
        });
    }

    /**
     * Scope query for popular destinations.
     */
    public function scopePopular(Builder $query, int $limit = 6): Builder
    {
        return $query->orderByDesc('review_count')
            ->limit($limit);
    }

    /**
     * Dataset version this destination belongs to.
     *
     * @return BelongsTo<DatasetVersion, Destination>
     */
    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
