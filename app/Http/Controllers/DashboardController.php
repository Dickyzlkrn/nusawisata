<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Services\RecommendationService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Display the user dashboard with their reviews and recommendations.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Load user ratings scoped to active dataset with destination and province
        $ratings = $user->ratings()
            ->forActiveDataset()
            ->with(['destination.province'])
            ->latest()
            ->paginate(6);

        $totalRatings = Rating::forActiveDataset()->where('user_id', $user->id)->count();
        $avgRatingGiven = Rating::forActiveDataset()->where('user_id', $user->id)->avg('rating') ?: 0;

        // Personalized recommendations
        $recommendations = $this->recommendationService->getRecommendations($user, 4);

        return view('pages.dashboard', compact(
            'user',
            'ratings',
            'totalRatings',
            'avgRatingGiven',
            'recommendations'
        ));
    }
}
