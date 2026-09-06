<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\MlRun;

class ActiveMlRunResolver
{
    /**
     * Cached active run in memory per request lifecycle.
     */
    protected ?MlRun $resolvedRun = null;

    /**
     * Track whether resolution has occurred.
     */
    protected bool $hasResolved = false;

    /**
     * Resolve the active ML run.
     */
    public function getActiveRun(): ?MlRun
    {
        if (! $this->hasResolved) {
            $this->resolvedRun = MlRun::getActive();
            $this->hasResolved = true;
        }

        return $this->resolvedRun;
    }

    /**
     * Determine if an active ML run exists.
     */
    public function hasActiveRun(): bool
    {
        return $this->getActiveRun() !== null;
    }

    /**
     * Resolve the dataset version associated with the active ML run.
     */
    public function getActiveDataset(): ?DatasetVersion
    {
        $run = $this->getActiveRun();

        if ($run && $run->dataset_version_id) {
            return $run->datasetVersion ?? DatasetVersion::find($run->dataset_version_id);
        }

        return DatasetVersion::getActive();
    }

    /**
     * Get active K value.
     */
    public function getActiveK(): int
    {
        $run = $this->getActiveRun();

        return (int) ($run?->k ?? $run?->parameters['k'] ?? 3);
    }

    /**
     * Get active metrics.
     *
     * @return array<string, mixed>
     */
    public function getActiveMetrics(): array
    {
        $run = $this->getActiveRun();
        if (! $run) {
            return [];
        }

        return [
            'k' => $run->k,
            'inertia' => $run->inertia,
            'silhouette' => $run->silhouette_score,
            'davies_bouldin' => $run->davies_bouldin_score,
            'calinski_harabasz' => $run->calinski_harabasz_score,
            'mae' => $run->mae,
            'rmse' => $run->rmse,
            'precision_at_k' => $run->precision_at_k,
            'recall_at_k' => $run->recall_at_k,
            'iterations' => $run->iterations,
            'seed' => $run->seed,
        ];
    }

    /**
     * Get active centroids.
     *
     * @return array<int, array<string, float>>
     */
    public function getActiveCentroids(): array
    {
        $run = $this->getActiveRun();

        return $run?->summary['centroids'] ?? [];
    }

    /**
     * Get active cluster distribution.
     *
     * @return array<int, int>
     */
    public function getActiveClusterDistribution(): array
    {
        $run = $this->getActiveRun();

        return $run?->summary['cluster_distribution'] ?? [];
    }

    /**
     * Get active PCA projection points.
     *
     * @return array<int, array{user_id: int, name: string, cluster_id: int, x: float, y: float}>
     */
    public function getActivePcaData(): array
    {
        $run = $this->getActiveRun();

        return $run?->summary['pca_data'] ?? [];
    }

    /**
     * Reset in-memory cache.
     */
    public function clearCache(): void
    {
        $this->resolvedRun = null;
        $this->hasResolved = false;
    }
}
