<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\ActiveMlRunResolver;
use App\Services\CollaborativeFilteringService;
use App\Services\KMeansService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected KMeansService $kMeansService,
        protected CollaborativeFilteringService $cfService,
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Display the admin panel dashboard with real analytics and visualizations.
     */
    public function index(): View
    {
        $totalDestinations = Destination::forActiveDataset()->count();
        $totalProvinces = Province::forActiveDataset()->count();
        $totalUsers = User::where('role', 'user')->count();
        $totalRatings = Rating::forActiveDataset()->count();

        // Active dataset & active ML run
        $activeDataset = $this->activeMlRunResolver->getActiveDataset() ?? DatasetVersion::getActive();
        $activeMlRun = $this->activeMlRunResolver->getActiveRun();
        $latestKmeans = $activeMlRun;
        $latestCf = $activeMlRun;

        // 1. Destination Map Data: All destinations with valid coordinates (active dataset)
        $mapDestinations = Destination::forActiveDataset()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->with('province')
            ->get()
            ->map(fn ($dest) => [
                'id' => $dest->id,
                'name' => $dest->name,
                'lat' => (float) $dest->latitude,
                'lng' => (float) $dest->longitude,
                'province' => $dest->province?->name ?? 'Indonesia',
                'province_id' => $dest->province_id,
                'category' => $dest->category ?? 'Umum',
                'rating' => $dest->google_rating ? (float) $dest->google_rating : null,
                'url' => route('destinations.show', $dest),
            ])
            ->values();

        $mapProvinces = $mapDestinations->pluck('province')->unique()->sort()->values();
        $mapCategories = $mapDestinations->pluck('category')->unique()->sort()->values();

        // 2. K-Means Visualization Data (Distribution, 2D PCA, Centroids, Metrics)
        $kmeansData = $this->kMeansService->getLatestVisualizationData();
        $clusterCounts = $kmeansData['clusters'];
        if (empty($clusterCounts)) {
            $clusterCounts = User::where('role', 'user')
                ->whereNotNull('cluster_id')
                ->selectRaw('cluster_id, count(*) as count')
                ->groupBy('cluster_id')
                ->pluck('count', 'cluster_id')
                ->all();
        }

        // 3. Collaborative Filtering: Rating distribution (1★ - 5★) and Matrix Sparsity (active dataset)
        $ratingDistribution = Rating::forActiveDataset()
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->all();

        for ($s = 1; $s <= 5; $s++) {
            if (! isset($ratingDistribution[$s])) {
                $ratingDistribution[$s] = 0;
            }
        }
        ksort($ratingDistribution);

        $matrixStats = $this->cfService->getMatrixStatistics();

        // 4. Recent community reviews (active dataset)
        $recentRatings = Rating::forActiveDataset()
            ->with(['user', 'destination.province'])
            ->latest()
            ->take(5)
            ->get();

        // 5. Top destinations by rating & reviews (active dataset)
        $topDestinations = Destination::forActiveDataset()->with('province')
            ->orderByDesc('google_rating')
            ->orderByDesc('review_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalDestinations',
            'totalProvinces',
            'totalUsers',
            'totalRatings',
            'activeDataset',
            'activeMlRun',
            'latestKmeans',
            'latestCf',
            'mapDestinations',
            'mapProvinces',
            'mapCategories',
            'clusterCounts',
            'kmeansData',
            'ratingDistribution',
            'matrixStats',
            'recentRatings',
            'topDestinations'
        ));
    }
}
