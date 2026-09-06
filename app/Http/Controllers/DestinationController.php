<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations with filtering and search.
     */
    public function index(Request $request): View
    {
        $query = Destination::forActiveDataset()->with('province');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('province', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($provinceSlug = $request->input('province')) {
            $query->whereHas('province', fn ($q) => $q->where('slug', $provinceSlug));
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $sort = $request->input('sort', 'popular');
        match ($sort) {
            'rating' => $query->orderByDesc('google_rating'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('review_count'),
        };

        $destinations = $query->paginate(12)->withQueryString();

        $provinces = Province::forActiveDataset()->orderBy('name')->get();
        $categories = Destination::forActiveDataset()->whereNotNull('category')->distinct()->pluck('category');

        return view('pages.destinations.index', compact('destinations', 'provinces', 'categories'));
    }

    /**
     * Display the specified destination details.
     */
    public function show(Destination $destination): View
    {
        $destination->load(['province', 'ratings.user', 'popularities']);

        // User's existing rating if logged in
        $userRating = auth()->check()
            ? $destination->ratings->firstWhere('user_id', auth()->id())
            : null;

        // Similar destinations from same province or category (scoped to active dataset)
        $relatedDestinations = Destination::forActiveDataset()->with('province')
            ->where('id', '!=', $destination->id)
            ->where(function ($q) use ($destination) {
                $q->where('province_id', $destination->province_id)
                    ->orWhere('category', $destination->category);
            })
            ->take(3)
            ->get();

        return view('pages.destinations.show', compact('destination', 'userRating', 'relatedDestinations'));
    }
}
