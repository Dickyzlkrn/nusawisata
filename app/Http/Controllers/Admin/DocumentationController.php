<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\ActiveMlRunResolver;
use App\Services\CollaborativeFilteringService;
use Illuminate\Contracts\View\View;

class DocumentationController extends Controller
{
    public function __construct(
        protected CollaborativeFilteringService $cfService,
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Display the admin technical system and architecture documentation.
     */
    public function index(): View
    {
        $totalDestinations = Destination::forActiveDataset()->count();
        $totalProvinces = Province::forActiveDataset()->count();
        $totalUsers = User::count();
        $totalRatings = Rating::forActiveDataset()->count();

        $recentMlRuns = MlRun::latest()->take(6)->get();

        // Use active ML run instead of latest()
        $activeMlRun = $this->activeMlRunResolver->getActiveRun();
        $lastKmeansRun = $activeMlRun;
        $lastCfRun = $activeMlRun;

        // Matrix sparsity and stats
        $matrixStats = $this->cfService->getMatrixStatistics();

        // Cluster counts
        $clusterCounts = User::selectRaw('cluster_id, count(*) as count')
            ->groupBy('cluster_id')
            ->orderBy('cluster_id')
            ->pluck('count', 'cluster_id')
            ->toArray();

        return view('admin.documentation.index', compact(
            'totalDestinations',
            'totalProvinces',
            'totalUsers',
            'totalRatings',
            'recentMlRuns',
            'lastKmeansRun',
            'lastCfRun',
            'matrixStats',
            'clusterCounts'
        ));
    }
}
