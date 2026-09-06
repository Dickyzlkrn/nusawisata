<?php

namespace App\Http\Controllers;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Services\ActiveDatasetResolver;
use App\Services\DestinationFilterService;
use App\Services\RecommendationService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService,
        protected DestinationFilterService $filterService,
        protected ActiveDatasetResolver $datasetResolver
    ) {}

    /**
     * Display the NusaWisata homepage.
     */
    public function index(): View
    {
        // Top 5 destinations for the handpicked grid (2 top + 3 bottom like template)
        $handpicked = Destination::forActiveDataset()->with('province')->orderByDesc('google_rating')->take(5)->get();
        $topDestinations = $handpicked->take(2);
        $bottomDestinations = $handpicked->slice(2, 3);

        // Featured highlighted destination (like Bali section in template)
        $featured = Destination::forActiveDataset()->with('province')
            ->whereHas('province', fn ($q) => $q->where('slug', 'bali'))
            ->first() ?? Destination::forActiveDataset()->with('province')->first();

        // Provinces list for preview grid (only provinces with active dataset destinations)
        $provinces = Province::forActiveDataset()
            ->withCount(['destinations' => fn ($q) => $q->forActiveDataset()])
            ->orderByDesc('destinations_count')
            ->take(8)
            ->get();

        // Top rated / deals section
        $deals = Destination::forActiveDataset()->with('province')
            ->orderByDesc('review_count')
            ->take(2)
            ->get();

        // Personalized recommendations if user logged in
        $recommendations = auth()->check()
            ? $this->recommendationService->getRecommendations(auth()->user(), 4)
            : collect();

        // Filter options for hero recommendation search bar
        $filterOptions = $this->filterService->getFilterOptions();

        // 6. Map data: All destinations with valid coordinates from active dataset
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
                'province_slug' => $dest->province?->slug ?? '',
                'category' => $dest->category ?? 'Wisata',
                'rating' => $dest->google_rating ? (float) $dest->google_rating : null,
                'review_count' => (int) ($dest->review_count ?? 0),
                'price' => $dest->formatted_price,
                'image' => $dest->image,
                'url' => route('destinations.show', $dest),
            ])
            ->values();

        $mapProvinces = $mapDestinations->pluck('province')->unique()->sort()->values();
        $mapCategories = $mapDestinations->pluck('category')->unique()->sort()->values();
        $activeDataset = DatasetVersion::getActive();

        return view('pages.home', compact(
            'topDestinations',
            'bottomDestinations',
            'featured',
            'provinces',
            'deals',
            'recommendations',
            'filterOptions',
            'mapDestinations',
            'mapProvinces',
            'mapCategories',
            'activeDataset'
        ));
    }
}
