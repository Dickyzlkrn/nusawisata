<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\ActiveMlRunResolver;
use Illuminate\Contracts\View\View;

class DocumentationController extends Controller
{
    public function __construct(
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Display the public system and algorithm documentation page.
     */
    public function index(): View
    {
        $totalDestinations = Destination::forActiveDataset()->count();
        $totalProvinces = Province::forActiveDataset()->count();
        $totalRatings = Rating::forActiveDataset()->count();
        $totalUsers = User::count();

        // Fetch active ML runs for evaluation metrics
        $latestKmeansRun = $this->activeMlRunResolver->getActiveRun();
        $latestCfRun = $latestKmeansRun; // Active ML run contains both K-Means and CF metrics

        // Cluster distribution for visual persona explanation
        $clusterCounts = User::whereNotNull('cluster_id')
            ->selectRaw('cluster_id, count(*) as count')
            ->groupBy('cluster_id')
            ->orderBy('cluster_id')
            ->pluck('count', 'cluster_id')
            ->toArray();

        return view('pages.documentation.index', compact(
            'totalDestinations',
            'totalProvinces',
            'totalRatings',
            'totalUsers',
            'latestKmeansRun',
            'latestCfRun',
            'clusterCounts'
        ));
    }
}
