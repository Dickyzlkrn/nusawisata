<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get destinations in this province.
     *
     * @return HasMany<Destination>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    /**
     * Get destinations in this province scoped to the active dataset.
     *
     * @return HasMany<Destination>
     */
    public function activeDestinations(): HasMany
    {
        $activeVersion = DatasetVersion::getActive();

        $relation = $this->hasMany(Destination::class);

        if ($activeVersion) {
            $relation->where(function ($q) use ($activeVersion) {
                $q->where('dataset_version_id', $activeVersion->id)
                    ->orWhereNull('dataset_version_id');
            });
        }

        return $relation;
    }

    /**
     * Scope query to only include provinces that have destinations in the active dataset.
     */
    public function scopeForActiveDataset(Builder $query): Builder
    {
        return $query->whereHas('destinations', fn ($q) => $q->forActiveDataset());
    }
}
