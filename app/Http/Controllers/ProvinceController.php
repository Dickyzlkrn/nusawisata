<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of all Indonesian provinces.
     */
    public function index(Request $request): View
    {
        $query = Province::forActiveDataset()
            ->withCount(['destinations' => fn ($q) => $q->forActiveDataset()]);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $provinces = $query->orderBy('name')->get();

        return view('pages.provinces.index', compact('provinces'));
    }

    /**
     * Display destinations belonging to a specific province.
     */
    public function show(Province $province): View
    {
        $province->loadCount(['destinations' => fn ($q) => $q->forActiveDataset()]);

        $topDestinations = $province->activeDestinations()
            ->with('province')
            ->orderByDesc('google_rating')
            ->orderByDesc('review_count')
            ->take(3)
            ->get();

        $destinations = $province->activeDestinations()
            ->with('province')
            ->orderByDesc('google_rating')
            ->paginate(9);

        // Prepare map markers for destinations in this province (active dataset only)
        $mapMarkers = $province->activeDestinations()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn ($d) => [
                'lat' => (float) $d->latitude,
                'lng' => (float) $d->longitude,
                'name' => $d->name,
                'description' => $d->category.' &bull; Rating: '.$d->google_rating,
            ])
            ->all();

        return view('pages.provinces.show', compact('province', 'topDestinations', 'destinations', 'mapMarkers'));
    }
}
