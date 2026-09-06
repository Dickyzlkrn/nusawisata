<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;

class RatingController extends Controller
{
    /**
     * Store or update a user rating for a destination.
     */
    public function store(StoreRatingRequest $request): RedirectResponse
    {
        $activeDatasetId = DatasetVersion::getActive()?->id;

        $rating = Rating::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'destination_id' => $request->destination_id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
                'dataset_version_id' => $activeDatasetId,
            ]
        );

        // Update destination review count if newly created
        $destination = Destination::find($request->destination_id);
        if ($destination && $rating->wasRecentlyCreated) {
            $destination->increment('review_count');
        }

        return back()->with('success', 'Ulasan dan rating Anda berhasil disimpan!');
    }

    /**
     * Remove the specified rating.
     */
    public function destroy(Rating $rating): RedirectResponse
    {
        if ($rating->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $destinationId = $rating->destination_id;
        $rating->delete();

        $destination = Destination::find($destinationId);
        if ($destination && $destination->review_count > 0) {
            $destination->decrement('review_count');
        }

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
