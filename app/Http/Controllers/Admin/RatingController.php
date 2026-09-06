<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Display a listing of ratings and reviews.
     */
    public function index(Request $request): View
    {
        $query = Rating::forActiveDataset()->with(['user', 'destination.province']);

        if ($stars = $request->input('rating')) {
            $query->where('rating', $stars);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('destination', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        $ratings = $query->latest()->paginate(15)->withQueryString();

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

        return view('admin.ratings.index', compact('ratings', 'ratingDistribution'));
    }

    /**
     * Remove the specified rating.
     */
    public function destroy(Rating $rating): RedirectResponse
    {
        $rating->delete();

        return redirect()->route('admin.ratings.index')
            ->with('success', 'Ulasan dan rating berhasil dihapus.');
    }
}
