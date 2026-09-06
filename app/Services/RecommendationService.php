<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Rating;
use App\Models\RecommendationRun;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function __construct(
        protected CollaborativeFilteringService $collaborativeFiltering,
        protected KMeansService $kMeans,
        protected DestinationFilterService $filterService,
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Generate an interactive recommendation run with real stage-by-stage execution.
     * Records stage progression, metrics, and results in recommendation_runs table.
     *
     * @param  array<string, mixed>  $filters
     */
    public function generateInteractiveRun(?User $user = null, int $limit = 6, array $filters = []): RecommendationRun
    {
        $activeRun = $this->activeMlRunResolver->getActiveRun();
        $activeDataset = $this->activeMlRunResolver->getActiveDataset() ?? DatasetVersion::getActive();

        $hasActiveFilters = $this->filterService->hasActiveFilters($filters);
        $candidateIds = $hasActiveFilters ? $this->filterService->getCandidateDestinationIds($filters) : null;
        $candidateCount = $candidateIds !== null ? count($candidateIds) : null;

        // Resolve user cluster from active ML run if available, otherwise fallback to user's saved cluster
        $userCluster = null;
        if ($user) {
            $userAssignments = $activeRun?->summary['user_assignments'] ?? [];
            if (isset($userAssignments[$user->id])) {
                $userCluster = is_array($userAssignments[$user->id])
                    ? ($userAssignments[$user->id]['cluster_id'] ?? null)
                    : (int) $userAssignments[$user->id];
            }
            $userCluster = $userCluster ?? $user->cluster_id ?? 1;
        }

        // Create run record in pending/processing state
        $run = RecommendationRun::create([
            'user_id' => $user?->id,
            'dataset_version_id' => $activeDataset?->id,
            'ml_run_id' => $activeRun?->id,
            'status' => 'processing',
            'current_stage' => 'loading_profile',
            'progress' => 10,
            'cluster_id' => $userCluster,
            'is_cold_start' => false,
            'started_at' => now(),
        ]);

        try {
            // STEP 1: Loading User Profile & Candidate Validation (15%)
            $stageDesc = $hasActiveFilters
                ? "Memvalidasi preferensi filter ({$candidateCount} destinasi kandidat)"
                : 'Memuat profil pengguna dan riwayat ulasan';
            $run->markStage('loading_profile', 15, $stageDesc);

            // Handle empty candidate set
            if ($candidateIds !== null && empty($candidateIds)) {
                $payload = [
                    'recommendations' => [],
                    'is_fallback' => true,
                    'is_cold_start' => false,
                    'algorithm' => 'Candidate Empty',
                    'cluster_id' => $user?->cluster_id,
                    'neighbors_used' => [],
                    'predicted_ratings' => [],
                    'filters' => $filters,
                    'candidate_count' => 0,
                    'explanation' => 'Tidak ditemukan destinasi yang sesuai dengan kriteria filter yang Anda tentukan. Silakan atur ulang filter Anda.',
                ];

                $run->complete($payload, 0);

                return $run;
            }

            // Handle guest user
            if (! $user) {
                $guestQuery = Destination::forActiveDataset()->with('province');
                if ($candidateIds !== null) {
                    $guestQuery->whereIn('id', $candidateIds);
                }
                $popular = $guestQuery->topRated($limit)->get();
                $recommendationsList = $this->formatDestinationsPayload($popular, true);

                $payload = [
                    'recommendations' => $recommendationsList,
                    'is_fallback' => true,
                    'is_cold_start' => true,
                    'algorithm' => 'Top Community Rated (Tamu/Guest)',
                    'cluster_id' => null,
                    'neighbors_used' => [],
                    'predicted_ratings' => $popular->pluck('google_rating', 'id')->all(),
                    'filters' => $filters,
                    'candidate_count' => $candidateCount,
                    'explanation' => 'Silakan masuk ke akun Anda agar sistem dapat menganalisis preferensi ulasan Anda.',
                ];

                $run->complete($payload, count($recommendationsList));

                return $run;
            }

            // Fetch user's ratings in the active dataset
            $userRatings = Rating::forActiveDataset()
                ->where('user_id', $user->id)
                ->pluck('rating', 'destination_id')
                ->map(fn ($r) => (float) $r)
                ->all();

            // STEP 2: Feature Engineering & Cold-Start Check (30%)
            $run->markStage('feature_engineering', 30, 'Menganalisis pola preferensi perjalanan');

            if (empty($userRatings)) {
                // Cold-Start: user has no ratings
                $run->update(['is_cold_start' => true]);
                $run->markStage('cold_start', 50, 'Deteksi preferensi awal: Pengguna belum memiliki riwayat rating (Cold-Start)');

                $coldQuery = Destination::forActiveDataset()->with('province');
                if ($candidateIds !== null) {
                    $coldQuery->whereIn('id', $candidateIds);
                }
                $popular = $coldQuery->topRated($limit)->get();
                $recommendationsList = $this->formatDestinationsPayload($popular, true);

                $payload = [
                    'recommendations' => $recommendationsList,
                    'is_fallback' => true,
                    'is_cold_start' => true,
                    'algorithm' => 'Destinasi Populer Nusantara (Cold-Start)',
                    'cluster_id' => null,
                    'neighbors_used' => [],
                    'predicted_ratings' => $popular->pluck('google_rating', 'id')->all(),
                    'filters' => $filters,
                    'candidate_count' => $candidateCount,
                    'explanation' => 'Anda belum memberikan rating ulasan destinasi. Kami menyajikan destinasi unggulan terpopuler di Indonesia sesuai filter sebagai rekomendasi awal perjalanan Anda.',
                ];

                $run->complete($payload, count($recommendationsList));

                return $run;
            }

            // STEP 3: K-Means Clustering Assignment (45%)
            $run->markStage('clustering', 45, 'Menentukan klaster segmentasi wisatawan');
            $clusterId = $userCluster ?? $user->cluster_id ?? 1;
            $run->update(['cluster_id' => $clusterId]);

            // STEP 4: Candidate Neighbors Selection (60%)
            $run->markStage('finding_neighbors', 60, 'Mencari wisatawan dengan preferensi serupa di klaster yang sama');
            $peerQuery = User::where('id', '!=', $user->id)
                ->where('role', 'user')
                ->where('cluster_id', $clusterId)
                ->whereHas('ratings', fn ($q) => $q->forActiveDataset());

            $peers = $peerQuery->with(['ratings' => fn ($q) => $q->forActiveDataset()])->get();

            // Fallback to all rated users if cluster too small
            if ($peers->count() < 3) {
                $peers = User::where('id', '!=', $user->id)
                    ->where('role', 'user')
                    ->whereHas('ratings', fn ($q) => $q->forActiveDataset())
                    ->with(['ratings' => fn ($q) => $q->forActiveDataset()])
                    ->get();
            }

            // STEP 5: Cosine Similarity Calculation (75%)
            $run->markStage('calculating_similarity', 75, 'Menghitung kemiripan preferensi (Cosine Similarity)');
            $similarities = [];
            $peerRatingsMap = [];

            foreach ($peers as $peer) {
                $pRatings = $peer->ratings->pluck('rating', 'destination_id')
                    ->map(fn ($r) => (float) $r)
                    ->all();

                $peerRatingsMap[$peer->id] = [
                    'user' => $peer,
                    'ratings' => $pRatings,
                ];

                $sim = $this->collaborativeFiltering->calculateCosineSimilarity($userRatings, $pRatings);
                if ($sim > 0.0) {
                    $similarities[$peer->id] = $sim;
                }
            }

            // If no similar users found, fallback
            $ratedDestIds = array_keys($userRatings);
            if (empty($similarities)) {
                $fallbackQuery = Destination::forActiveDataset()
                    ->with('province')
                    ->whereNotIn('id', $ratedDestIds);
                if ($candidateIds !== null) {
                    $fallbackQuery->whereIn('id', $candidateIds);
                }
                $fallbackDest = $fallbackQuery->topRated($limit)->get();

                $recList = $this->formatDestinationsPayload($fallbackDest, true);
                $payload = [
                    'recommendations' => $recList,
                    'is_fallback' => true,
                    'is_cold_start' => false,
                    'algorithm' => 'Top Rated Fallback',
                    'cluster_id' => $clusterId,
                    'neighbors_used' => [],
                    'predicted_ratings' => $fallbackDest->pluck('google_rating', 'id')->all(),
                    'filters' => $filters,
                    'candidate_count' => $candidateCount,
                    'explanation' => 'Tidak ditemukan kemiripan ulasan langsung. Menyajikan destinasi terbaik sesuai filter yang belum Anda kunjungi.',
                ];

                $run->complete($payload, count($recList));

                return $run;
            }

            arsort($similarities);
            $topNeighbors = array_slice(array_keys($similarities), 0, 10, true);

            $neighborsUsed = [];
            foreach ($topNeighbors as $peerId) {
                $peerObj = $peerRatingsMap[$peerId]['user'];
                $neighborsUsed[] = [
                    'user_id' => $peerId,
                    'name' => $peerObj->name,
                    'similarity' => round($similarities[$peerId], 4),
                    'cluster_id' => $peerObj->cluster_id,
                ];
            }
            $run->update(['neighbor_count' => count($neighborsUsed)]);

            // STEP 6: Weighted Rating Prediction (88%)
            $run->markStage('predicting_ratings', 88, 'Memprediksi nilai kecocokan destinasi wisata');
            $candidateScores = [];
            $simSums = [];

            foreach ($topNeighbors as $peerId) {
                $sim = $similarities[$peerId];
                $pRatings = $peerRatingsMap[$peerId]['ratings'];

                foreach ($pRatings as $destId => $rating) {
                    if (in_array($destId, $ratedDestIds, true)) {
                        continue;
                    }

                    // Candidate destination constraint
                    if ($candidateIds !== null && ! in_array($destId, $candidateIds, true)) {
                        continue;
                    }

                    $candidateScores[$destId] = ($candidateScores[$destId] ?? 0.0) + ($sim * $rating);
                    $simSums[$destId] = ($simSums[$destId] ?? 0.0) + $sim;
                }
            }

            $predictedRatings = [];
            foreach ($candidateScores as $destId => $scoreSum) {
                $denom = $simSums[$destId] ?? 1.0;
                if ($denom > 1e-9) {
                    $predictedRatings[$destId] = round($scoreSum / $denom, 2);
                }
            }

            // STEP 7: Ranking & Top-N Selection (95%)
            $run->markStage('ranking', 95, 'Menyusun peringkat rekomendasi terbaik (Top-N)');
            arsort($predictedRatings);
            $topDestIds = array_slice(array_keys($predictedRatings), 0, $limit, true);

            if (empty($topDestIds)) {
                $fallbackQuery = Destination::forActiveDataset()
                    ->with('province')
                    ->whereNotIn('id', $ratedDestIds);
                if ($candidateIds !== null) {
                    $fallbackQuery->whereIn('id', $candidateIds);
                }
                $fallbackDest = $fallbackQuery->topRated($limit)->get();

                $recList = $this->formatDestinationsPayload($fallbackDest, true);
                $payload = [
                    'recommendations' => $recList,
                    'is_fallback' => true,
                    'is_cold_start' => false,
                    'algorithm' => 'Top Rated Fallback',
                    'cluster_id' => $clusterId,
                    'neighbors_used' => $neighborsUsed,
                    'predicted_ratings' => $fallbackDest->pluck('google_rating', 'id')->all(),
                    'filters' => $filters,
                    'candidate_count' => $candidateCount,
                    'explanation' => 'Menyajikan destinasi teratas yang belum pernah Anda beri penilaian sesuai kriteria filter.',
                ];

                $run->complete($payload, count($recList));

                return $run;
            }

            $destinations = Destination::forActiveDataset()
                ->with('province')
                ->whereIn('id', $topDestIds)
                ->get()
                ->sortByDesc(fn ($d) => $predictedRatings[$d->id] ?? 0.0)
                ->values();

            $rank = 1;
            foreach ($destinations as $d) {
                $d->predicted_rating = $predictedRatings[$d->id] ?? (float) $d->google_rating;
                $d->recommendation_rank = $rank++;
                $d->recommendation_reason = 'Direkomendasikan berdasarkan penilaian positif wisatawan berpreferensi serupa di klaster Anda.';
                $d->is_fallback = false;
            }

            $recommendationsList = $this->formatDestinationsPayload($destinations, false);

            // STEP 8: Completed (100%)
            $filterText = $hasActiveFilters ? ' yang disesuaikan dengan kriteria pencarian' : '';
            $payload = [
                'recommendations' => $recommendationsList,
                'is_fallback' => false,
                'is_cold_start' => false,
                'algorithm' => 'K-Means Clustering + User-Based Collaborative Filtering',
                'cluster_id' => $clusterId,
                'neighbors_used' => $neighborsUsed,
                'predicted_ratings' => $predictedRatings,
                'filters' => $filters,
                'candidate_count' => $candidateCount,
                'explanation' => "Rekomendasi dihitung menggunakan kemiripan ulasan wisatawan lain di Klaster {$clusterId}{$filterText}.",
            ];

            $run->complete($payload, count($recommendationsList));

            return $run;
        } catch (Exception $e) {
            $run->fail($e->getMessage());

            return $run;
        }
    }

    /**
     * Helper to format destinations for JSON payload and presentation.
     *
     * @param  Collection<int, Destination>  $destinations
     * @return array<int, array<string, mixed>>
     */
    protected function formatDestinationsPayload(Collection $destinations, bool $isFallback): array
    {
        $list = [];
        $rank = 1;

        foreach ($destinations as $dest) {
            $list[] = [
                'id' => $dest->id,
                'name' => $dest->name,
                'slug' => $dest->slug,
                'category' => $dest->category,
                'price' => (float) $dest->price,
                'formatted_price' => $dest->formatted_price,
                'image' => $dest->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=80',
                'province' => $dest->province?->name ?? 'Indonesia',
                'google_rating' => (float) $dest->google_rating,
                'predicted_rating' => isset($dest->predicted_rating) ? (float) $dest->predicted_rating : (float) $dest->google_rating,
                'review_count' => (int) $dest->review_count,
                'recommendation_rank' => $dest->recommendation_rank ?? $rank++,
                'recommendation_reason' => $dest->recommendation_reason ?? ($isFallback ? 'Destinasi unggulan nasional berdasarkan rating komunitas.' : 'Kecocokan preferensi tinggi.'),
                'is_fallback' => $isFallback,
            ];
        }

        return $list;
    }

    /**
     * Get personalized recommendations collection for user or fallback to top rated if guest.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Destination>
     */
    public function getRecommendations(?User $user = null, int $limit = 6, array $filters = []): Collection
    {
        $result = $this->getRecommendationResult($user, $limit, $filters);

        return $result['recommendations'];
    }

    /**
     * Get comprehensive recommendation payload with candidate filtering support.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     recommendations: Collection<int, Destination>,
     *     is_fallback: bool,
     *     algorithm: string,
     *     cluster_id: ?int,
     *     neighbors_used: array<int, array{user_id: int, name: string, similarity: float, cluster_id: ?int}>,
     *     predicted_ratings: array<int, float>,
     *     explanation: string,
     *     user: ?User,
     *     filters: array<string, mixed>,
     *     candidate_count: ?int
     * }
     */
    public function getRecommendationResult(?User $user = null, int $limit = 6, array $filters = []): array
    {
        $hasActiveFilters = $this->filterService->hasActiveFilters($filters);
        $candidateIds = $hasActiveFilters ? $this->filterService->getCandidateDestinationIds($filters) : null;
        $candidateCount = $candidateIds !== null ? count($candidateIds) : null;

        // If active filters matched 0 candidates, return empty immediately
        if ($hasActiveFilters && empty($candidateIds)) {
            return [
                'recommendations' => collect(),
                'is_fallback' => true,
                'algorithm' => 'Candidate Empty',
                'cluster_id' => $user?->cluster_id,
                'neighbors_used' => [],
                'predicted_ratings' => [],
                'explanation' => 'Tidak ditemukan destinasi yang sesuai dengan kriteria filter yang Anda pilih.',
                'user' => $user,
                'filters' => $filters,
                'candidate_count' => 0,
            ];
        }

        if ($user) {
            $cfResult = $this->collaborativeFiltering->recommendForUser(
                $user,
                $limit,
                10,
                true,
                $candidateIds
            );

            return [
                'recommendations' => $cfResult['recommendations'],
                'is_fallback' => $cfResult['is_fallback'],
                'algorithm' => $cfResult['algorithm'],
                'cluster_id' => $cfResult['cluster_id'],
                'neighbors_used' => $cfResult['neighbors_used'],
                'predicted_ratings' => $cfResult['predicted_ratings'],
                'explanation' => $cfResult['explanation'],
                'user' => $user,
                'filters' => $filters,
                'candidate_count' => $candidateCount,
            ];
        }

        // Guest user: Top rated destinations within candidates
        $guestQuery = Destination::forActiveDataset()->with('province');
        if ($candidateIds !== null) {
            $guestQuery->whereIn('id', $candidateIds);
        }

        $destinations = $guestQuery->topRated($limit)->get();
        $rank = 1;
        $predicted = [];

        foreach ($destinations as $dest) {
            $dest->predicted_rating = (float) $dest->google_rating;
            $dest->recommendation_rank = $rank++;
            $dest->recommendation_reason = 'Destinasi wisata unggulan dengan rating komunitas tertinggi.';
            $dest->is_fallback = true;
            $predicted[$dest->id] = (float) $dest->google_rating;
        }

        return [
            'recommendations' => $destinations,
            'is_fallback' => true,
            'algorithm' => 'Top Community Rated (Tamu/Guest)',
            'cluster_id' => null,
            'neighbors_used' => [],
            'predicted_ratings' => $predicted,
            'explanation' => 'Silakan masuk ke akun Anda agar sistem dapat memberikan rekomendasi terpersonalisasi berdasarkan preferensi ulasan Anda.',
            'user' => null,
            'filters' => $filters,
            'candidate_count' => $candidateCount,
        ];
    }

    /**
     * Get top rated destinations based on Google ratings & review count.
     *
     * @return Collection<int, Destination>
     */
    public function getTopRated(int $limit = 6): Collection
    {
        return Destination::forActiveDataset()
            ->with('province')
            ->topRated($limit)
            ->get();
    }

    /**
     * Get popular destinations.
     *
     * @return Collection<int, Destination>
     */
    public function getPopular(int $limit = 6): Collection
    {
        return Destination::forActiveDataset()
            ->with('province')
            ->popular($limit)
            ->get();
    }
}
