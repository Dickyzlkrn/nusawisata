<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;

class ActiveDatasetResolver
{
    /**
     * Cached active dataset version in memory per request lifecycle.
     */
    protected ?DatasetVersion $resolvedDataset = null;

    /**
     * Track whether resolution has occurred.
     */
    protected bool $hasResolved = false;

    /**
     * Resolve the active dataset version.
     */
    public function getActiveDataset(): ?DatasetVersion
    {
        if (! $this->hasResolved) {
            $this->resolvedDataset = DatasetVersion::getActive();
            $this->hasResolved = true;
        }

        return $this->resolvedDataset;
    }

    /**
     * Determine if an active dataset exists.
     */
    public function hasActiveDataset(): bool
    {
        return $this->getActiveDataset() !== null;
    }

    /**
     * Get the active dataset version ID, or null.
     */
    public function getActiveDatasetId(): ?int
    {
        return $this->getActiveDataset()?->id;
    }

    /**
     * Get destination count scoped to active dataset.
     */
    public function getDestinationCount(): int
    {
        return Destination::forActiveDataset()->count();
    }

    /**
     * Get province count scoped to active dataset (provinces that have destinations in the active dataset).
     */
    public function getProvinceCount(): int
    {
        return Province::forActiveDataset()->count();
    }

    /**
     * Get rating count scoped to active dataset.
     */
    public function getRatingCount(): int
    {
        return Rating::forActiveDataset()->count();
    }

    /**
     * Get user count (users with role 'user').
     */
    public function getUserCount(): int
    {
        return User::where('role', 'user')->count();
    }

    /**
     * Reset in-memory cache (useful after dataset switch).
     */
    public function clearCache(): void
    {
        $this->resolvedDataset = null;
        $this->hasResolved = false;
    }
}
