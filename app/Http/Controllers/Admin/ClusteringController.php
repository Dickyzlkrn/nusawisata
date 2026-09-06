<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Rating;
use App\Models\User;
use App\Services\KMeansService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClusteringController extends Controller
{
    public function __construct(
        protected KMeansService $kMeansService
    ) {}

    /**
     * Display K-Means Clustering overview, evaluation metrics, and configuration.
     * Supports switching between Destination Clustering (Price & Rating) and User Clustering.
     */
    public function index(Request $request): View
    {
        $mode = $request->query('mode', 'users');
        if (! in_array($mode, ['destinations', 'users'], true)) {
            $mode = 'users';
        }

        $users = User::where('role', 'user')->withCount(['ratings' => fn ($q) => $q->forActiveDataset()])->get();
        $totalRatings = Rating::forActiveDataset()->count();

        if ($mode === 'destinations') {
            $destinations = Destination::forActiveDataset()->get();
            $clusterDistribution = $destinations->whereNotNull('cluster_id')
                ->groupBy('cluster_id')
                ->map(fn ($group) => $group->count())
                ->sortKeys();

            $unclusteredCount = $destinations->whereNull('cluster_id')->count();

            // Evaluate candidate K values (K=2 to K=6)
            $candidates = $this->kMeansService->evaluateDestinationKCandidates(2, 6);

            $lastResult = session('cluster_result');
            if (! $lastResult || ($lastResult['mode'] ?? '') !== 'destinations') {
                $visData = $this->kMeansService->getLatestDestinationVisualizationData();
                $lastResult = [
                    'mode' => 'destinations',
                    'k' => $visData['k'],
                    'iterations' => $visData['metrics']['iterations'] ?? 0,
                    'converged' => $visData['metrics']['converged'] ?? true,
                    'total_destinations' => $visData['total_destinations'] ?? $destinations->count(),
                    'clusters' => $visData['clusters'],
                    'centroids' => $visData['centroids'],
                    'metrics' => $visData['metrics'],
                    'scatter_data' => $visData['scatter_data'],
                    'features' => KMeansService::DESTINATION_FEATURES,
                    'excluded_metadata' => KMeansService::DESTINATION_EXCLUDED_COLUMNS,
                ];
            }

            // Cluster profiles for destinations
            $clusterProfiles = [];
            foreach ($clusterDistribution as $cId => $cnt) {
                $cDest = $destinations->where('cluster_id', $cId);
                $clusterProfiles[$cId] = [
                    'count' => $cnt,
                    'avg_price' => round((float) $cDest->avg('price'), 0),
                    'avg_rating' => round((float) $cDest->avg('google_rating'), 2),
                    'sample_names' => $cDest->take(3)->pluck('name')->all(),
                    'categories' => $cDest->groupBy('category')->map->count()->sortDesc()->take(2)->keys()->all(),
                ];
            }

            $runHistory = MlRun::where('algorithm', 'kmeans_destinations')
                ->with('datasetVersion')
                ->latest()
                ->take(8)
                ->get();

            $clusteredEntities = Destination::forActiveDataset()
                ->whereNotNull('cluster_id')
                ->orderBy('cluster_id')
                ->take(100)
                ->get();
        } else {
            // Mode = users
            $clusterDistribution = $users->whereNotNull('cluster_id')
                ->groupBy('cluster_id')
                ->map(fn ($group) => $group->count())
                ->sortKeys();

            $unclusteredCount = $users->whereNull('cluster_id')->count();

            // Evaluate candidate K values (K=2 to K=5)
            $candidates = $this->kMeansService->evaluateKCandidates(2, 5);

            $lastResult = session('cluster_result');
            if (! $lastResult || ($lastResult['mode'] ?? '') !== 'users') {
                $visData = $this->kMeansService->getLatestVisualizationData();
                $lastResult = [
                    'mode' => 'users',
                    'k' => $visData['k'],
                    'iterations' => $visData['metrics']['iterations'] ?? 0,
                    'converged' => $visData['metrics']['converged'] ?? true,
                    'total_users' => $users->whereNotNull('cluster_id')->count(),
                    'clusters' => $visData['clusters'],
                    'centroids' => $visData['centroids'],
                    'metrics' => $visData['metrics'],
                    'pca_data' => $visData['pca_data'],
                ];
            }

            // User cluster profiles
            $clusterProfiles = [];
            foreach ($clusterDistribution as $cId => $cnt) {
                $cUsers = $users->where('cluster_id', $cId);
                $cUserIds = $cUsers->pluck('id');

                $topCategory = Rating::forActiveDataset()
                    ->whereIn('user_id', $cUserIds)
                    ->join('destinations', 'ratings.destination_id', '=', 'destinations.id')
                    ->selectRaw('destinations.category, count(*) as count')
                    ->groupBy('destinations.category')
                    ->orderByDesc('count')
                    ->value('destinations.category') ?? 'N/A';

                $topProvince = Rating::forActiveDataset()
                    ->whereIn('user_id', $cUserIds)
                    ->join('destinations', 'ratings.destination_id', '=', 'destinations.id')
                    ->join('provinces', 'destinations.province_id', '=', 'provinces.id')
                    ->selectRaw('provinces.name, count(*) as count')
                    ->groupBy('provinces.name')
                    ->orderByDesc('count')
                    ->value('provinces.name') ?? 'N/A';

                $avgClusterRating = Rating::forActiveDataset()->whereIn('user_id', $cUserIds)->avg('rating') ?: 0;
                $avgClusterCount = $cUsers->avg('ratings_count') ?: 0;

                $clusterProfiles[$cId] = [
                    'users_count' => $cnt,
                    'avg_rating' => round($avgClusterRating, 2),
                    'avg_ratings_count' => round($avgClusterCount, 1),
                    'top_category' => $topCategory,
                    'top_province' => $topProvince,
                ];
            }

            $runHistory = MlRun::where('algorithm', 'kmeans')
                ->with('datasetVersion')
                ->latest()
                ->take(8)
                ->get();

            $clusteredEntities = User::whereNotNull('cluster_id')
                ->withCount('ratings')
                ->orderBy('cluster_id')
                ->take(100)
                ->get();
        }

        return view('admin.clustering.index', compact(
            'mode',
            'users',
            'totalRatings',
            'clusterDistribution',
            'unclusteredCount',
            'candidates',
            'lastResult',
            'clusterProfiles',
            'runHistory',
            'clusteredEntities'
        ));
    }

    /**
     * Execute K-Means clustering algorithm for destinations or users.
     */
    public function run(Request $request): RedirectResponse
    {
        $mode = $request->input('mode', 'destinations');
        $iterations = (int) $request->input('iterations', 50);
        $seed = (int) $request->input('seed', 42);

        if ($mode === 'destinations') {
            $k = (int) $request->input('k', 4);
            $useLog = (bool) $request->input('use_log_price', false);
            $result = $this->kMeansService->clusterDestinations($k, $iterations, $seed, true, null, $useLog);
            $result['mode'] = 'destinations';

            return redirect()->route('admin.clustering.index', ['mode' => 'destinations'])
                ->with('cluster_result', $result)
                ->with('success', "Klasterisasi Destinasi K-Means (Fitur: price, destination_rating) berhasil dijalankan dengan K={$k}. Silhouette: {$result['metrics']['silhouette']}, Davies-Bouldin: {$result['metrics']['davies_bouldin']}.");
        }

        $k = (int) $request->input('k', 3);
        $featureSet = (string) $request->input('feature_set', 'all_12');
        $result = $this->kMeansService->run($k, $iterations, $seed, true, null, $featureSet);
        $result['mode'] = 'users';

        return redirect()->route('admin.clustering.index', ['mode' => 'users'])
            ->with('cluster_result', $result)
            ->with('success', "Algoritma K-Means Pengguna berhasil dijalankan dengan K={$k} dalam {$result['iterations']} iterasi. Silhouette: {$result['metrics']['silhouette']}, Davies-Bouldin: {$result['metrics']['davies_bouldin']}.");
    }
}
