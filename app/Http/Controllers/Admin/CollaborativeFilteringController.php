<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetVersion;
use App\Models\MlRun;
use App\Models\Rating;
use App\Models\User;
use App\Services\CollaborativeFilteringService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollaborativeFilteringController extends Controller
{
    public function __construct(
        protected CollaborativeFilteringService $cfService
    ) {}

    /**
     * Display Collaborative Filtering overview, matrix stats, simulation, and evaluation.
     */
    public function index(Request $request): View
    {
        $matrixStats = $this->cfService->getMatrixStatistics();
        $activeDataset = DatasetVersion::getActive();

        // 1. Rating Distribution (1★ - 5★)
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

        // 2. Users with ratings for simulation dropdown
        $users = User::where('role', 'user')
            ->whereHas('ratings', fn ($q) => $q->forActiveDataset())
            ->withCount(['ratings' => fn ($q) => $q->forActiveDataset()])
            ->orderBy('id')
            ->take(150)
            ->get();

        $selectedUserId = (int) $request->input('user_id', $users->first()?->id ?? 1);
        $topNNeighbors = (int) $request->input('neighbors', 5);
        $topKLimit = (int) $request->input('limit', 5);

        $selectedUser = User::find($selectedUserId);
        $simulationResult = null;
        $userRatings = collect();
        $userCategoryPrefs = collect();

        if ($selectedUser) {
            $simulationResult = $this->cfService->recommendForUser(
                $selectedUser,
                $topKLimit,
                $topNNeighbors,
                true
            );

            $userRatings = Rating::forActiveDataset()
                ->where('user_id', $selectedUser->id)
                ->with('destination.province')
                ->latest()
                ->get();

            $userCategoryPrefs = Rating::forActiveDataset()
                ->where('user_id', $selectedUser->id)
                ->join('destinations', 'ratings.destination_id', '=', 'destinations.id')
                ->selectRaw('destinations.category, count(*) as count, avg(ratings.rating) as avg_rating')
                ->groupBy('destinations.category')
                ->orderByDesc('count')
                ->take(5)
                ->get();
        }

        $latestEvalRun = MlRun::where('algorithm', 'cf_evaluation')->latest()->first();
        $evaluationResult = session('evaluation_result') ?? ($latestEvalRun?->metrics);

        return view('admin.collaborative_filtering.index', compact(
            'matrixStats',
            'activeDataset',
            'ratingDistribution',
            'users',
            'selectedUser',
            'selectedUserId',
            'topNNeighbors',
            'topKLimit',
            'simulationResult',
            'userRatings',
            'userCategoryPrefs',
            'evaluationResult'
        ));
    }

    /**
     * Run simulation for chosen user and neighbor parameters.
     */
    public function simulate(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'neighbors' => 'required|integer|min:1|max:30',
            'limit' => 'required|integer|min:1|max:20',
        ]);

        return redirect()->route('admin.collaborative_filtering.index', [
            'user_id' => $request->user_id,
            'neighbors' => $request->neighbors,
            'limit' => $request->limit,
        ]);
    }

    /**
     * Run train/test split evaluation for Collaborative Filtering.
     */
    public function evaluate(Request $request): RedirectResponse
    {
        $testRatio = (float) $request->input('test_ratio', 0.20);
        $k = (int) $request->input('k_threshold', 5);

        $evalMetrics = $this->cfService->evaluateModel($testRatio, $k, 4.0);

        MlRun::create([
            'algorithm' => 'cf_evaluation',
            'parameters' => [
                'test_ratio' => $testRatio,
                'k_threshold' => $k,
                'relevance_threshold' => 4.0,
            ],
            'metrics' => $evalMetrics,
            'summary' => [
                'evaluated_users' => $evalMetrics['evaluated_users_count'],
                'test_ratings' => $evalMetrics['test_ratings_count'],
            ],
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        return redirect()->route('admin.collaborative_filtering.index')
            ->with('evaluation_result', $evalMetrics)
            ->with('success', "Evaluasi model selesai! MAE: {$evalMetrics['mae']}, RMSE: {$evalMetrics['rmse']}.");
    }
}
