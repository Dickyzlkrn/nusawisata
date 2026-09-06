<?php

namespace App\Http\Controllers;

use App\Models\RecommendationRun;
use App\Services\DestinationFilterService;
use App\Services\RecommendationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService,
        protected DestinationFilterService $filterService
    ) {}

    /**
     * Display recommendations calculated using Collaborative Filtering and K-Means.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Extract search & filter parameters
        $filters = [
            'province' => $request->query('province'),
            'category' => $request->query('category'),
            'budget' => $request->query('budget'),
            'min_rating' => $request->query('min_rating'),
            'keyword' => $request->query('keyword'),
        ];

        $filterOptions = $this->filterService->getFilterOptions();
        $hasActiveFilters = $this->filterService->hasActiveFilters($filters);
        $activeBadges = $this->filterService->getActiveFilterBadges($filters);

        $recommendationResult = $this->recommendationService->getRecommendationResult($user, 9, $filters);
        $recommendations = $recommendationResult['recommendations'];
        $candidateCount = $recommendationResult['candidate_count'] ?? null;
        $popular = $this->recommendationService->getPopular(4);

        // Fetch user's latest recommendation run if available
        $latestRun = null;
        if ($user) {
            $latestRun = RecommendationRun::where('user_id', $user->id)
                ->where('status', 'completed')
                ->latest()
                ->first();
        }

        return view('pages.recommendations.index', compact(
            'recommendations',
            'recommendationResult',
            'filterOptions',
            'filters',
            'hasActiveFilters',
            'activeBadges',
            'candidateCount',
            'popular',
            'user',
            'latestRun'
        ));
    }

    /**
     * Generate recommendation run interactively with backend stage progression and filter constraints.
     */
    public function generate(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = (int) $request->input('limit', 9);

        $filters = [
            'province' => $request->input('province'),
            'category' => $request->input('category'),
            'budget' => $request->input('budget'),
            'min_rating' => $request->input('min_rating'),
            'keyword' => $request->input('keyword'),
        ];

        // Prevent double submit if already processing
        if ($user) {
            $ongoing = RecommendationRun::where('user_id', $user->id)
                ->where('status', 'processing')
                ->where('created_at', '>=', now()->subMinutes(1))
                ->first();

            if ($ongoing) {
                return response()->json([
                    'success' => true,
                    'run_id' => $ongoing->id,
                    'status' => $ongoing->status,
                    'stage' => $ongoing->current_stage,
                    'progress' => $ongoing->progress,
                    'message' => 'Proses rekomendasi sedang berjalan...',
                ]);
            }
        }

        $run = $this->recommendationService->generateInteractiveRun($user, $limit, $filters);

        return response()->json([
            'success' => $run->status === 'completed',
            'run_id' => $run->id,
            'status' => $run->status,
            'stage' => $run->current_stage,
            'progress' => $run->progress,
            'is_cold_start' => $run->is_cold_start,
            'cluster_id' => $run->cluster_id,
            'stages_log' => $run->stages_log,
            'error_message' => $run->error_message,
        ]);
    }

    /**
     * Check status and progression of an interactive recommendation run.
     */
    public function status(RecommendationRun $run): JsonResponse
    {
        $currentUser = auth()->user();
        if ($run->user_id !== null && (! $currentUser || $run->user_id !== $currentUser->id)) {
            abort(403, 'Unauthorized access to recommendation run.');
        }

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'stage' => $run->current_stage,
            'progress' => $run->progress,
            'cluster_id' => $run->cluster_id,
            'neighbor_count' => $run->neighbor_count,
            'recommendation_count' => $run->recommendation_count,
            'is_cold_start' => $run->is_cold_start,
            'stages_log' => $run->stages_log,
            'error_message' => $run->error_message,
        ]);
    }

    /**
     * Retrieve calculated result payload of a completed recommendation run.
     */
    public function result(RecommendationRun $run): JsonResponse
    {
        $currentUser = auth()->user();
        if ($run->user_id !== null && (! $currentUser || $run->user_id !== $currentUser->id)) {
            abort(403, 'Unauthorized access to recommendation run results.');
        }

        return response()->json([
            'success' => true,
            'run' => [
                'id' => $run->id,
                'status' => $run->status,
                'cluster_id' => $run->cluster_id,
                'neighbor_count' => $run->neighbor_count,
                'recommendation_count' => $run->recommendation_count,
                'is_cold_start' => $run->is_cold_start,
                'completed_at' => $run->completed_at?->format('d M Y H:i'),
            ],
            'payload' => $run->result_payload,
        ]);
    }
}
