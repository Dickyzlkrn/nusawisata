<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Collection;

class CollaborativeFilteringService
{
    /**
     * Get destination recommendations for a user using User-Based Collaborative Filtering
     * assisted by K-Means cluster segmentation.
     *
     * @param  User  $user  Target user
     * @param  int  $limit  Number of recommendations to return
     * @param  int  $topNNeighbors  Number of nearest neighbors to consider
     * @param  bool  $withinCluster  Whether to filter peers inside the user's cluster
     * @return array{
     *     recommendations: Collection<int, Destination>,
     *     target_user_id: int,
     *     cluster_id: ?int,
     *     neighbors_used: array<int, array{user_id: int, name: string, similarity: float, cluster_id: ?int}>,
     *     predicted_ratings: array<int, float>,
     *     is_fallback: bool,
     *     algorithm: string,
     *     explanation: string
     * }
     */
    public function recommendForUser(
        User $user,
        int $limit = 6,
        int $topNNeighbors = 10,
        bool $withinCluster = true,
        ?array $candidateDestinationIds = null
    ): array {
        // 1. Get destinations already rated by target user
        $userRatings = Rating::forActiveDataset()
            ->where('user_id', $user->id)
            ->pluck('rating', 'destination_id')
            ->map(fn ($r) => (float) $r)
            ->all();

        $ratedDestIds = array_keys($userRatings);

        // If candidate destinations were requested but the set is completely empty, return empty right away
        if ($candidateDestinationIds !== null && empty($candidateDestinationIds)) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Tidak ditemukan destinasi yang sesuai dengan kriteria filter yang dipilih.',
                $candidateDestinationIds
            );
        }

        // Cold-start check: User has no ratings
        if (empty($userRatings)) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Pengguna belum memiliki riwayat rating (Cold-Start). Menyajikan destinasi terpopuler.',
                $candidateDestinationIds
            );
        }

        // 2. Select peer candidate users
        $peerQuery = User::where('id', '!=', $user->id)
            ->where('role', 'user')
            ->whereHas('ratings', fn ($q) => $q->forActiveDataset());

        if ($withinCluster && $user->cluster_id !== null) {
            $peerQuery->where('cluster_id', $user->cluster_id);
        }

        $peers = $peerQuery->with(['ratings' => fn ($q) => $q->forActiveDataset()])->get();

        // If cluster has too few peers with ratings, fallback to all users with ratings
        if ($peers->count() < 3 && $withinCluster) {
            $peers = User::where('id', '!=', $user->id)
                ->where('role', 'user')
                ->whereHas('ratings', fn ($q) => $q->forActiveDataset())
                ->with(['ratings' => fn ($q) => $q->forActiveDataset()])
                ->get();
        }

        if ($peers->isEmpty()) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Tidak ditemukan wisatawan pembanding. Menyajikan destinasi berating tertinggi.',
                $candidateDestinationIds
            );
        }

        // 3. Calculate Cosine Similarity with each peer
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

            $sim = $this->calculateCosineSimilarity($userRatings, $pRatings);
            if ($sim > 0.0) {
                $similarities[$peer->id] = $sim;
            }
        }

        if (empty($similarities)) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Tidak terdapat kesamaan pola rating dengan pengguna lain. Menyajikan destinasi unggulan.',
                $candidateDestinationIds
            );
        }

        // 4. Select Top-N Nearest Neighbors
        arsort($similarities);
        $topNeighborIds = array_slice(array_keys($similarities), 0, $topNNeighbors, true);

        $neighborsUsed = [];
        foreach ($topNeighborIds as $nId) {
            $nUser = $peerRatingsMap[$nId]['user'];
            $neighborsUsed[] = [
                'user_id' => $nId,
                'name' => $nUser->name,
                'similarity' => round($similarities[$nId], 4),
                'cluster_id' => $nUser->cluster_id,
            ];
        }

        // 5. Predict ratings for unvisited destinations
        // Formula: predicted_rating(u, i) = sum(sim(u, v) * r(v, i)) / sum(sim(u, v))
        $itemNumerator = [];
        $itemDenominator = [];

        foreach ($topNeighborIds as $neighborId) {
            $sim = $similarities[$neighborId];
            $neighborRatings = $peerRatingsMap[$neighborId]['ratings'];

            foreach ($neighborRatings as $destId => $ratingVal) {
                if (in_array($destId, $ratedDestIds, true)) {
                    continue; // Skip items already rated by target user
                }

                // Filter constraint: only predict for destinations within the candidate set (if filtered)
                if ($candidateDestinationIds !== null && ! in_array($destId, $candidateDestinationIds, true)) {
                    continue;
                }

                $itemNumerator[$destId] = ($itemNumerator[$destId] ?? 0.0) + ($sim * $ratingVal);
                $itemDenominator[$destId] = ($itemDenominator[$destId] ?? 0.0) + abs($sim);
            }
        }

        if (empty($itemNumerator)) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Tetangga terdekat belum menilai destinasi baru yang sesuai kriteria filter Anda.',
                $candidateDestinationIds
            );
        }

        $predictedRatings = [];
        foreach ($itemNumerator as $destId => $num) {
            $denom = $itemDenominator[$destId];
            if ($denom > 1e-9) {
                $pred = $num / $denom;
                // Clamp prediction between 1.0 and 5.0
                $predictedRatings[$destId] = round(min(5.0, max(1.0, $pred)), 2);
            }
        }

        arsort($predictedRatings);
        $topDestIds = array_slice(array_keys($predictedRatings), 0, $limit, true);

        if (empty($topDestIds)) {
            return $this->buildFallbackRecommendations(
                $ratedDestIds,
                $limit,
                $user->id,
                $user->cluster_id,
                'Tidak ada skor prediksi valid untuk filter ini. Menyajikan destinasi terbaik.',
                $candidateDestinationIds
            );
        }

        // Fetch destination models and preserve predicted ranking order
        $destinations = Destination::forActiveDataset()
            ->with('province')
            ->whereIn('id', $topDestIds)
            ->get()
            ->sortByDesc(fn ($d) => $predictedRatings[$d->id] ?? 0.0)
            ->values();

        // Attach predicted metadata to models
        $rank = 1;
        foreach ($destinations as $dest) {
            $dest->predicted_rating = $predictedRatings[$dest->id] ?? $dest->google_rating;
            $dest->recommendation_rank = $rank++;
            $dest->recommendation_reason = 'Direkomendasikan karena wisatawan dengan preferensi serupa di kelompok Anda memberi nilai tinggi.';
            $dest->is_fallback = false;
        }

        $clusterText = $user->cluster_id ? "Klaster {$user->cluster_id}" : 'Komunitas';
        $filterNotice = $candidateDestinationIds !== null ? ' yang memenuhi kriteria pencarian' : '';
        $explanation = "Rekomendasi dihitung menggunakan User-Based Collaborative Filtering dengan {$topNNeighbors} wisatawan terdekat di {$clusterText}{$filterNotice}.";

        return [
            'recommendations' => $destinations,
            'target_user_id' => $user->id,
            'cluster_id' => $user->cluster_id,
            'neighbors_used' => $neighborsUsed,
            'predicted_ratings' => $predictedRatings,
            'is_fallback' => false,
            'algorithm' => 'K-Means + User-Based Collaborative Filtering',
            'explanation' => $explanation,
        ];
    }

    /**
     * Calculate Cosine Similarity between two user rating vectors.
     *
     * @param  array<int, float>  $vec1  [destination_id => rating]
     * @param  array<int, float>  $vec2  [destination_id => rating]
     * @return float Similarity in range [0.0, 1.0]
     */
    public function calculateCosineSimilarity(array $vec1, array $vec2): float
    {
        $commonIds = array_intersect(array_keys($vec1), array_keys($vec2));

        // Require at least 2 co-rated items for meaningful similarity
        if (count($commonIds) < 2) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        foreach ($commonIds as $id) {
            $r1 = $vec1[$id];
            $r2 = $vec2[$id];

            $dotProduct += ($r1 * $r2);
            $norm1 += ($r1 * $r1);
            $norm2 += ($r2 * $r2);
        }

        if ($norm1 <= 1e-9 || $norm2 <= 1e-9) {
            return 0.0;
        }

        $sim = $dotProduct / (sqrt($norm1) * sqrt($norm2));

        return max(0.0, min(1.0, (float) $sim));
    }

    /**
     * Build Cold-Start Fallback recommendations with honest labeling and candidate scoping.
     *
     * @param  array<int>  $ratedDestIds
     * @param  ?array<int>  $candidateDestinationIds
     */
    protected function buildFallbackRecommendations(
        array $ratedDestIds,
        int $limit,
        int $userId,
        ?int $clusterId,
        string $reason,
        ?array $candidateDestinationIds = null
    ): array {
        if ($candidateDestinationIds !== null && empty($candidateDestinationIds)) {
            return [
                'recommendations' => collect(),
                'target_user_id' => $userId,
                'cluster_id' => $clusterId,
                'neighbors_used' => [],
                'predicted_ratings' => [],
                'is_fallback' => true,
                'algorithm' => 'Candidate Empty',
                'explanation' => 'Tidak ditemukan destinasi yang cocok dengan kriteria filter yang Anda tentukan.',
            ];
        }

        $query = Destination::forActiveDataset()
            ->with('province')
            ->whereNotIn('id', $ratedDestIds);

        if ($candidateDestinationIds !== null) {
            $query->whereIn('id', $candidateDestinationIds);
        }

        $destinations = $query->topRated($limit)->get();

        $rank = 1;
        $predictedRatings = [];

        foreach ($destinations as $dest) {
            $dest->predicted_rating = (float) $dest->google_rating;
            $dest->recommendation_rank = $rank++;
            $dest->recommendation_reason = 'Destinasi terpopuler berdasarkan ulasan wisatawan nusantara (Cold-Start Fallback).';
            $dest->is_fallback = true;
            $predictedRatings[$dest->id] = (float) $dest->google_rating;
        }

        return [
            'recommendations' => $destinations,
            'target_user_id' => $userId,
            'cluster_id' => $clusterId,
            'neighbors_used' => [],
            'predicted_ratings' => $predictedRatings,
            'is_fallback' => true,
            'algorithm' => 'Cold-Start Popularity Fallback',
            'explanation' => $reason,
        ];
    }

    /**
     * Get rating matrix metadata and sparsity statistics for Admin ML Dashboard.
     *
     * @return array{
     *     total_users: int,
     *     total_destinations: int,
     *     total_ratings: int,
     *     matrix_size: int,
     *     sparsity_percent: float,
     *     density_percent: float,
     *     avg_ratings_per_user: float,
     *     avg_ratings_per_destination: float
     * }
     */
    public function getMatrixStatistics(?DatasetVersion $datasetVersion = null): array
    {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $userQuery = User::where('role', 'user')->whereHas('ratings', function ($q) use ($versionId) {
            if ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            }
        });
        $destQuery = Destination::whereHas('ratings', function ($q) use ($versionId) {
            if ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            }
        });
        $ratingQuery = Rating::query();
        if ($versionId) {
            $ratingQuery->where(function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            });
        }

        $totalUsers = $userQuery->count();
        $totalDestinations = $destQuery->count();
        $totalRatings = $ratingQuery->count();

        $matrixSize = $totalUsers * $totalDestinations;
        $sparsity = $matrixSize > 0 ? (1.0 - ($totalRatings / $matrixSize)) * 100.0 : 100.0;
        $density = 100.0 - $sparsity;

        return [
            'total_users' => $totalUsers,
            'total_destinations' => $totalDestinations,
            'total_ratings' => $totalRatings,
            'matrix_size' => $matrixSize,
            'sparsity_percent' => round($sparsity, 2),
            'density_percent' => round($density, 2),
            'avg_ratings_per_user' => $totalUsers > 0 ? round($totalRatings / $totalUsers, 1) : 0.0,
            'avg_ratings_per_destination' => $totalDestinations > 0 ? round($totalRatings / $totalDestinations, 1) : 0.0,
        ];
    }

    /**
     * Evaluate Collaborative Filtering accuracy using Train-Test Split.
     * Computes MAE, RMSE, Precision@K, and Recall@K.
     *
     * @param  float  $testRatio  Ratio of ratings held out for test (e.g. 0.20 for 80/20 split)
     * @param  int  $kThreshold  Rank threshold for Precision@K and Recall@K
     * @param  float  $relevanceRatingThreshold  Rating threshold to consider item relevant (e.g. 4.0)
     * @return array{
     *     mae: float,
     *     rmse: float,
     *     precision_at_k: float,
     *     recall_at_k: float,
     *     test_ratings_count: int,
     *     evaluated_users_count: int
     * }
     */
    public function evaluateModel(
        float $testRatio = 0.20,
        int $kThreshold = 5,
        float $relevanceRatingThreshold = 4.0,
        ?DatasetVersion $datasetVersion = null
    ): array {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $ratingQuery = Rating::query();
        if ($versionId) {
            $ratingQuery->where(function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            });
        }

        // Fetch all ratings grouped by user
        $allRatings = $ratingQuery->get()->groupBy('user_id');

        $trainRatings = [];
        $testRatings = [];
        $testCount = 0;
        $evaluatedUsers = 0;

        foreach ($allRatings as $uId => $ratings) {
            // Need at least 4 ratings for meaningful train/test split
            if ($ratings->count() < 4) {
                continue;
            }

            $userRatingList = $ratings->values()->all();
            $numTest = max(1, (int) round(count($userRatingList) * $testRatio));

            // Seeded deterministic split
            mt_srand($uId);
            shuffle($userRatingList);

            $testSlice = array_slice($userRatingList, 0, $numTest);
            $trainSlice = array_slice($userRatingList, $numTest);

            $trainMap = [];
            foreach ($trainSlice as $r) {
                $trainMap[$r->destination_id] = (float) $r->rating;
            }

            $testMap = [];
            foreach ($testSlice as $r) {
                $testMap[$r->destination_id] = (float) $r->rating;
                $testCount++;
            }

            $trainRatings[$uId] = $trainMap;
            $testRatings[$uId] = $testMap;
            $evaluatedUsers++;
        }

        if ($testCount === 0 || $evaluatedUsers === 0) {
            return [
                'mae' => 0.0,
                'rmse' => 0.0,
                'precision_at_k' => 0.0,
                'recall_at_k' => 0.0,
                'test_ratings_count' => 0,
                'evaluated_users_count' => 0,
            ];
        }

        $absoluteErrors = [];
        $squaredErrors = [];
        $precisions = [];
        $recalls = [];

        foreach ($testRatings as $targetUserId => $actualTestItems) {
            $targetTrain = $trainRatings[$targetUserId];

            // 1. Calculate similarity with all other users' training sets
            $similarities = [];
            foreach ($trainRatings as $otherUserId => $otherTrain) {
                if ($otherUserId === $targetUserId) {
                    continue;
                }

                $sim = $this->calculateCosineSimilarity($targetTrain, $otherTrain);
                if ($sim > 0.0) {
                    $similarities[$otherUserId] = $sim;
                }
            }

            if (empty($similarities)) {
                continue;
            }

            arsort($similarities);
            $topNeighbors = array_slice($similarities, 0, 10, true);

            // 2. Predict scores for test items
            $predictedScores = [];

            foreach ($actualTestItems as $destId => $actualRating) {
                $num = 0.0;
                $denom = 0.0;

                foreach ($topNeighbors as $nId => $sim) {
                    if (isset($trainRatings[$nId][$destId])) {
                        $num += ($sim * $trainRatings[$nId][$destId]);
                        $denom += abs($sim);
                    }
                }

                if ($denom > 1e-9) {
                    $predicted = $num / $denom;
                } else {
                    // Fallback to target user's training average
                    $predicted = count($targetTrain) > 0 ? (array_sum($targetTrain) / count($targetTrain)) : 3.5;
                }

                $clamped = min(5.0, max(1.0, $predicted));
                $predictedScores[$destId] = $clamped;

                $error = abs($clamped - $actualRating);
                $absoluteErrors[] = $error;
                $squaredErrors[] = ($error * $error);
            }

            // 3. Precision@K and Recall@K calculation
            arsort($predictedScores);
            $topKDestIds = array_slice(array_keys($predictedScores), 0, $kThreshold);

            $relevantInTest = array_filter($actualTestItems, fn ($r) => $r >= $relevanceRatingThreshold);
            $totalRelevant = count($relevantInTest);

            $relevantRecommended = 0;
            foreach ($topKDestIds as $kDestId) {
                if (isset($actualTestItems[$kDestId]) && $actualTestItems[$kDestId] >= $relevanceRatingThreshold) {
                    $relevantRecommended++;
                }
            }

            if (count($topKDestIds) > 0) {
                $precisions[] = $relevantRecommended / count($topKDestIds);
            }

            if ($totalRelevant > 0) {
                $recalls[] = $relevantRecommended / $totalRelevant;
            }
        }

        $mae = count($absoluteErrors) > 0 ? (array_sum($absoluteErrors) / count($absoluteErrors)) : 0.0;
        $rmse = count($squaredErrors) > 0 ? sqrt(array_sum($squaredErrors) / count($squaredErrors)) : 0.0;
        $precisionAtK = count($precisions) > 0 ? (array_sum($precisions) / count($precisions)) : 0.0;
        $recallAtK = count($recalls) > 0 ? (array_sum($recalls) / count($recalls)) : 0.0;

        return [
            'mae' => round($mae, 4),
            'rmse' => round($rmse, 4),
            'precision_at_k' => round($precisionAtK * 100, 2),
            'recall_at_k' => round($recallAtK * 100, 2),
            'test_ratings_count' => count($absoluteErrors),
            'evaluated_users_count' => $evaluatedUsers,
        ];
    }

    /**
     * Strict Train-Only Recommendation Evaluation (Zero Data Leakage Protocol).
     *
     * In this protocol:
     * 1. Ratings are split 80:20 (train/test) per user.
     * 2. User representations for clustering are calculated SOLELY from the train partition.
     * 3. K-Means is fitted exclusively on train features.
     * 4. Peer nearest neighbors are searched within the train-derived clusters.
     * 5. Predictions are evaluated strictly on held-out test ratings.
     *
     * @param  float  $testRatio  Ratio of ratings held out for test (e.g. 0.20)
     * @param  int  $kThreshold  Rank threshold for Precision@K and Recall@K
     * @param  float  $relevanceRatingThreshold  Rating threshold to consider item relevant (e.g. 4.0)
     * @param  int  $clusterCount  Number of user clusters (K)
     * @return array{
     *     mae: float,
     *     rmse: float,
     *     precision_at_k: float,
     *     recall_at_k: float,
     *     test_ratings_count: int,
     *     evaluated_users_count: int,
     *     protocol: string
     * }
     */
    public function evaluateModelStrictTrain(
        float $testRatio = 0.20,
        int $kThreshold = 5,
        float $relevanceRatingThreshold = 4.0,
        ?DatasetVersion $datasetVersion = null,
        int $clusterCount = 3
    ): array {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $ratingQuery = Rating::query();
        if ($versionId) {
            $ratingQuery->where(function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            });
        }

        $allRatings = $ratingQuery->get()->groupBy('user_id');

        $trainRatings = [];
        $testRatings = [];
        $trainUserFeatures = [];
        $testCount = 0;
        $evaluatedUsers = 0;

        foreach ($allRatings as $uId => $ratings) {
            if ($ratings->count() < 4) {
                continue;
            }

            $userRatingList = $ratings->values()->all();
            $numTest = max(1, (int) round(count($userRatingList) * $testRatio));

            mt_srand($uId);
            shuffle($userRatingList);

            $testSlice = array_slice($userRatingList, 0, $numTest);
            $trainSlice = array_slice($userRatingList, $numTest);

            $trainMap = [];
            $trainScores = [];
            foreach ($trainSlice as $r) {
                $trainMap[$r->destination_id] = (float) $r->rating;
                $trainScores[] = (float) $r->rating;
            }

            $testMap = [];
            foreach ($testSlice as $r) {
                $testMap[$r->destination_id] = (float) $r->rating;
                $testCount++;
            }

            $trainRatings[$uId] = $trainMap;
            $testRatings[$uId] = $testMap;
            $evaluatedUsers++;

            // Strict Train-only Feature Engineering
            $cnt = count($trainScores);
            $avg = array_sum($trainScores) / max(1, $cnt);
            $var = 0.0;
            foreach ($trainScores as $s) {
                $var += pow($s - $avg, 2);
            }
            $std = $cnt > 1 ? sqrt($var / ($cnt - 1)) : 0.0;

            $trainUserFeatures[$uId] = [
                'average_rating' => $avg,
                'rating_std' => $std,
                'log_total_ratings' => log1p($cnt),
            ];
        }

        if ($testCount === 0 || $evaluatedUsers === 0) {
            return [
                'mae' => 0.0,
                'rmse' => 0.0,
                'precision_at_k' => 0.0,
                'recall_at_k' => 0.0,
                'test_ratings_count' => 0,
                'evaluated_users_count' => 0,
                'protocol' => 'strict_train_only',
            ];
        }

        // Fit K-Means solely on train features
        $keys = ['average_rating', 'rating_std', 'log_total_ratings'];
        $n = count($trainUserFeatures);
        $means = [];
        $stds = [];
        foreach ($keys as $kName) {
            $vals = array_column($trainUserFeatures, $kName);
            $m = array_sum($vals) / $n;
            $sq = 0.0;
            foreach ($vals as $x) {
                $sq += pow($x - $m, 2);
            }
            $means[$kName] = $m;
            $stds[$kName] = sqrt($sq / $n) ?: 1.0;
        }

        $scaledTrain = [];
        foreach ($trainUserFeatures as $uId => $row) {
            $scaledTrain[$uId] = [
                'average_rating' => ($row['average_rating'] - $means['average_rating']) / $stds['average_rating'],
                'rating_std' => ($row['rating_std'] - $means['rating_std']) / $stds['rating_std'],
                'log_total_ratings' => ($row['log_total_ratings'] - $means['log_total_ratings']) / $stds['log_total_ratings'],
            ];
        }

        $k = min($clusterCount, max(2, (int) ($n / 5)));
        $ids = array_keys($scaledTrain);
        mt_srand(42);
        shuffle($ids);
        $centroids = [];
        for ($i = 1; $i <= $k; $i++) {
            $centroids[$i] = $scaledTrain[$ids[$i - 1]];
        }

        $assignments = [];
        for ($iter = 0; $iter < 40; $iter++) {
            $newAssignments = [];
            foreach ($scaledTrain as $uId => $vec) {
                $minD = PHP_FLOAT_MAX;
                $bestC = 1;
                foreach ($centroids as $cId => $cVec) {
                    $d = pow($vec['average_rating'] - $cVec['average_rating'], 2)
                        + pow($vec['rating_std'] - $cVec['rating_std'], 2)
                        + pow($vec['log_total_ratings'] - $cVec['log_total_ratings'], 2);
                    if ($d < $minD) {
                        $minD = $d;
                        $bestC = $cId;
                    }
                }
                $newAssignments[$uId] = $bestC;
            }
            if ($newAssignments === $assignments) {
                break;
            }
            $assignments = $newAssignments;

            for ($cId = 1; $cId <= $k; $cId++) {
                $m = array_keys(array_filter($assignments, fn ($c) => $c === $cId));
                if (empty($m)) {
                    continue;
                }
                $newC = array_fill_keys($keys, 0.0);
                foreach ($m as $mId) {
                    foreach ($keys as $key) {
                        $newC[$key] += $scaledTrain[$mId][$key];
                    }
                }
                foreach ($keys as $key) {
                    $newC[$key] /= count($m);
                }
                $centroids[$cId] = $newC;
            }
        }

        // Evaluate CF using train-derived clusters
        $absoluteErrors = [];
        $squaredErrors = [];
        $precisions = [];
        $recalls = [];

        foreach ($testRatings as $targetUserId => $actualTestItems) {
            $targetTrain = $trainRatings[$targetUserId];
            $targetCluster = $assignments[$targetUserId] ?? 1;

            $similarities = [];
            foreach ($trainRatings as $otherUserId => $otherTrain) {
                if ($otherUserId === $targetUserId) {
                    continue;
                }
                // Restrict to peers in the same train-derived cluster
                if (($assignments[$otherUserId] ?? null) !== $targetCluster) {
                    continue;
                }
                $sim = $this->calculateCosineSimilarity($targetTrain, $otherTrain);
                if ($sim > 0.0) {
                    $similarities[$otherUserId] = $sim;
                }
            }

            // Fallback to all peers if cluster has too few overlapping ratings
            if (empty($similarities)) {
                foreach ($trainRatings as $otherUserId => $otherTrain) {
                    if ($otherUserId === $targetUserId) {
                        continue;
                    }
                    $sim = $this->calculateCosineSimilarity($targetTrain, $otherTrain);
                    if ($sim > 0.0) {
                        $similarities[$otherUserId] = $sim;
                    }
                }
            }

            if (empty($similarities)) {
                continue;
            }

            arsort($similarities);
            $topNeighbors = array_slice($similarities, 0, 10, true);

            $predictedScores = [];
            foreach ($actualTestItems as $destId => $actualRating) {
                $num = 0.0;
                $denom = 0.0;

                foreach ($topNeighbors as $nId => $sim) {
                    if (isset($trainRatings[$nId][$destId])) {
                        $num += ($sim * $trainRatings[$nId][$destId]);
                        $denom += abs($sim);
                    }
                }

                $predicted = $denom > 1e-9 ? ($num / $denom) : (count($targetTrain) > 0 ? (array_sum($targetTrain) / count($targetTrain)) : 3.5);
                $clamped = min(5.0, max(1.0, $predicted));
                $predictedScores[$destId] = $clamped;

                $error = abs($clamped - $actualRating);
                $absoluteErrors[] = $error;
                $squaredErrors[] = ($error * $error);
            }

            arsort($predictedScores);
            $topKDestIds = array_slice(array_keys($predictedScores), 0, $kThreshold);
            $relevantInTest = array_filter($actualTestItems, fn ($r) => $r >= $relevanceRatingThreshold);
            $totalRelevant = count($relevantInTest);

            $relevantRecommended = 0;
            foreach ($topKDestIds as $kDestId) {
                if (isset($actualTestItems[$kDestId]) && $actualTestItems[$kDestId] >= $relevanceRatingThreshold) {
                    $relevantRecommended++;
                }
            }

            if (count($topKDestIds) > 0) {
                $precisions[] = $relevantRecommended / count($topKDestIds);
            }
            if ($totalRelevant > 0) {
                $recalls[] = $relevantRecommended / $totalRelevant;
            }
        }

        $mae = count($absoluteErrors) > 0 ? (array_sum($absoluteErrors) / count($absoluteErrors)) : 0.0;
        $rmse = count($squaredErrors) > 0 ? sqrt(array_sum($squaredErrors) / count($squaredErrors)) : 0.0;
        $precisionAtK = count($precisions) > 0 ? (array_sum($precisions) / count($precisions)) : 0.0;
        $recallAtK = count($recalls) > 0 ? (array_sum($recalls) / count($recalls)) : 0.0;

        return [
            'mae' => round($mae, 4),
            'rmse' => round($rmse, 4),
            'precision_at_k' => round($precisionAtK * 100, 2),
            'recall_at_k' => round($recallAtK * 100, 2),
            'test_ratings_count' => count($absoluteErrors),
            'evaluated_users_count' => $evaluatedUsers,
            'protocol' => 'strict_train_only',
        ];
    }

    /**
     * Run Ablation Study comparing 4 configurations:
     * A: CF Only (No cluster restriction)
     * B: K-Means (User Clusters) + CF
     * C: K-Means (Destination Clusters) + CF
     * D: K-Means + CF + Candidate Filter
     *
     * @return array<string, array{
     *     config_name: string,
     *     description: string,
     *     mae: float,
     *     rmse: float,
     *     precision_at_k: float,
     *     recall_at_k: float
     * }>
     */
    public function runAblationStudy(
        float $testRatio = 0.20,
        int $kThreshold = 5,
        float $relevanceRatingThreshold = 4.0,
        ?DatasetVersion $datasetVersion = null
    ): array {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $ratingQuery = Rating::query();
        if ($versionId) {
            $ratingQuery->where(function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            });
        }

        $allRatings = $ratingQuery->get()->groupBy('user_id');
        $destinations = Destination::all()->keyBy('id');
        $destClusterMap = [];
        foreach ($destinations as $d) {
            $destClusterMap[$d->id] = $d->cluster_id ?? 1;
        }

        $trainRatings = [];
        $testRatings = [];
        $trainUserFeatures = [];
        $trainUserCategories = [];

        foreach ($allRatings as $uId => $ratings) {
            if ($ratings->count() < 4) {
                continue;
            }

            $userRatingList = $ratings->values()->all();
            $numTest = max(1, (int) round(count($userRatingList) * $testRatio));

            mt_srand($uId);
            shuffle($userRatingList);

            $testSlice = array_slice($userRatingList, 0, $numTest);
            $trainSlice = array_slice($userRatingList, $numTest);

            $trainMap = [];
            $trainScores = [];
            $categories = [];
            foreach ($trainSlice as $r) {
                $trainMap[$r->destination_id] = (float) $r->rating;
                $trainScores[] = (float) $r->rating;
                if (isset($destinations[$r->destination_id])) {
                    $categories[$destinations[$r->destination_id]->category] = true;
                }
            }

            $testMap = [];
            foreach ($testSlice as $r) {
                $testMap[$r->destination_id] = (float) $r->rating;
            }

            $trainRatings[$uId] = $trainMap;
            $testRatings[$uId] = $testMap;
            $trainUserCategories[$uId] = array_keys($categories);

            $cnt = count($trainScores);
            $avg = array_sum($trainScores) / max(1, $cnt);
            $var = 0.0;
            foreach ($trainScores as $s) {
                $var += pow($s - $avg, 2);
            }
            $std = $cnt > 1 ? sqrt($var / ($cnt - 1)) : 0.0;

            $trainUserFeatures[$uId] = [
                'average_rating' => $avg,
                'rating_std' => $std,
                'log_total_ratings' => log1p($cnt),
            ];
        }

        // Strict Train-Only User Clustering
        $keys = ['average_rating', 'rating_std', 'log_total_ratings'];
        $nUsers = count($trainUserFeatures);
        $means = [];
        $stds = [];
        foreach ($keys as $kName) {
            $vals = array_column($trainUserFeatures, $kName);
            $m = $nUsers > 0 ? (array_sum($vals) / $nUsers) : 0.0;
            $sq = 0.0;
            foreach ($vals as $x) {
                $sq += pow($x - $m, 2);
            }
            $means[$kName] = $m;
            $stds[$kName] = $nUsers > 0 ? (sqrt($sq / $nUsers) ?: 1.0) : 1.0;
        }

        $scaledTrain = [];
        foreach ($trainUserFeatures as $uId => $row) {
            $scaledTrain[$uId] = [
                'average_rating' => ($row['average_rating'] - $means['average_rating']) / $stds['average_rating'],
                'rating_std' => ($row['rating_std'] - $means['rating_std']) / $stds['rating_std'],
                'log_total_ratings' => ($row['log_total_ratings'] - $means['log_total_ratings']) / $stds['log_total_ratings'],
            ];
        }

        $kUsers = 3;
        $ids = array_keys($scaledTrain);
        mt_srand(42);
        shuffle($ids);
        $userCentroids = [];
        for ($i = 1; $i <= $kUsers && $i <= count($ids); $i++) {
            $userCentroids[$i] = $scaledTrain[$ids[$i - 1]];
        }

        $userAssignments = [];
        for ($iter = 0; $iter < 40; $iter++) {
            $newAssignments = [];
            foreach ($scaledTrain as $uId => $vec) {
                $minD = PHP_FLOAT_MAX;
                $bestC = 1;
                foreach ($userCentroids as $cId => $cVec) {
                    $d = pow($vec['average_rating'] - $cVec['average_rating'], 2)
                        + pow($vec['rating_std'] - $cVec['rating_std'], 2)
                        + pow($vec['log_total_ratings'] - $cVec['log_total_ratings'], 2);
                    if ($d < $minD) {
                        $minD = $d;
                        $bestC = $cId;
                    }
                }
                $newAssignments[$uId] = $bestC;
            }
            if ($newAssignments === $userAssignments) {
                break;
            }
            $userAssignments = $newAssignments;
            for ($cId = 1; $cId <= $kUsers; $cId++) {
                $m = array_keys(array_filter($userAssignments, fn ($c) => $c === $cId));
                if (empty($m)) {
                    continue;
                }
                $newC = array_fill_keys($keys, 0.0);
                foreach ($m as $mId) {
                    foreach ($keys as $key) {
                        $newC[$key] += $scaledTrain[$mId][$key];
                    }
                }
                foreach ($keys as $key) {
                    $newC[$key] /= count($m);
                }
                $userCentroids[$cId] = $newC;
            }
        }

        $evaluateConfig = function (string $mode) use (
            $trainRatings,
            $testRatings,
            $userAssignments,
            $destClusterMap,
            $trainUserCategories,
            $destinations,
            $kThreshold,
            $relevanceRatingThreshold
        ): array {
            $absoluteErrors = [];
            $squaredErrors = [];
            $precisions = [];
            $recalls = [];

            foreach ($testRatings as $targetUserId => $actualTestItems) {
                $targetTrain = $trainRatings[$targetUserId];
                $targetCluster = $userAssignments[$targetUserId] ?? 1;

                $similarities = [];
                foreach ($trainRatings as $otherUserId => $otherTrain) {
                    if ($otherUserId === $targetUserId) {
                        continue;
                    }

                    if ($mode === 'kmeans_user' || $mode === 'kmeans_filtered') {
                        if (($userAssignments[$otherUserId] ?? null) !== $targetCluster) {
                            continue;
                        }
                    }

                    $sim = $this->calculateCosineSimilarity($targetTrain, $otherTrain);
                    if ($sim > 0.0) {
                        $similarities[$otherUserId] = $sim;
                    }
                }

                if (empty($similarities)) {
                    foreach ($trainRatings as $otherUserId => $otherTrain) {
                        if ($otherUserId === $targetUserId) {
                            continue;
                        }
                        $sim = $this->calculateCosineSimilarity($targetTrain, $otherTrain);
                        if ($sim > 0.0) {
                            $similarities[$otherUserId] = $sim;
                        }
                    }
                }

                if (empty($similarities)) {
                    continue;
                }

                arsort($similarities);
                $topNeighbors = array_slice($similarities, 0, 10, true);

                $predictedScores = [];
                foreach ($actualTestItems as $destId => $actualRating) {
                    $num = 0.0;
                    $denom = 0.0;
                    $destCluster = $destClusterMap[$destId] ?? null;

                    foreach ($topNeighbors as $nId => $sim) {
                        if (isset($trainRatings[$nId][$destId])) {
                            $weight = $sim;
                            if ($mode === 'kmeans_dest') {
                                $overlapCount = 0;
                                foreach ($trainRatings[$nId] as $item => $r) {
                                    if (($destClusterMap[$item] ?? null) === $destCluster) {
                                        $overlapCount++;
                                    }
                                }
                                $weight *= (1.0 + 0.1 * min(5, $overlapCount));
                            }
                            $num += ($weight * $trainRatings[$nId][$destId]);
                            $denom += abs($weight);
                        }
                    }

                    $predicted = $denom > 1e-9 ? ($num / $denom) : (count($targetTrain) > 0 ? (array_sum($targetTrain) / count($targetTrain)) : 3.5);
                    $clamped = min(5.0, max(1.0, $predicted));
                    $predictedScores[$destId] = $clamped;

                    $err = abs($clamped - $actualRating);
                    $absoluteErrors[] = $err;
                    $squaredErrors[] = ($err * $err);
                }

                arsort($predictedScores);
                $candidatePreds = $predictedScores;

                if ($mode === 'kmeans_filtered') {
                    $userCats = $trainUserCategories[$targetUserId] ?? [];
                    $filteredPreds = [];
                    foreach ($candidatePreds as $dId => $score) {
                        $dCat = $destinations[$dId]->category ?? '';
                        if (in_array($dCat, $userCats, true)) {
                            $filteredPreds[$dId] = $score;
                        }
                    }
                    if (! empty($filteredPreds)) {
                        $candidatePreds = $filteredPreds;
                    }
                }

                $topKDestIds = array_slice(array_keys($candidatePreds), 0, $kThreshold);
                $relevantInTest = array_filter($actualTestItems, fn ($r) => $r >= $relevanceRatingThreshold);
                $totalRelevant = count($relevantInTest);

                $relevantRecommended = 0;
                foreach ($topKDestIds as $kDestId) {
                    if (isset($actualTestItems[$kDestId]) && $actualTestItems[$kDestId] >= $relevanceRatingThreshold) {
                        $relevantRecommended++;
                    }
                }

                if (count($topKDestIds) > 0) {
                    $precisions[] = $relevantRecommended / count($topKDestIds);
                }
                if ($totalRelevant > 0) {
                    $recalls[] = $relevantRecommended / $totalRelevant;
                }
            }

            return [
                'mae' => count($absoluteErrors) > 0 ? round(array_sum($absoluteErrors) / count($absoluteErrors), 4) : 0.0,
                'rmse' => count($squaredErrors) > 0 ? round(sqrt(array_sum($squaredErrors) / count($squaredErrors)), 4) : 0.0,
                'precision_at_k' => count($precisions) > 0 ? round((array_sum($precisions) / count($precisions)) * 100, 2) : 0.0,
                'recall_at_k' => count($recalls) > 0 ? round((array_sum($recalls) / count($recalls)) * 100, 2) : 0.0,
            ];
        };

        $cfOnly = $evaluateConfig('cf_only');
        $userKMeansCf = $evaluateConfig('kmeans_user');
        $destKMeansCf = $evaluateConfig('kmeans_dest');
        $filteredCf = $evaluateConfig('kmeans_filtered');

        return [
            'cf_only' => [
                'config_name' => 'CF Only (Baseline)',
                'description' => 'User-Based Collaborative Filtering tanpa batasan klasterisasi.',
                'mae' => $cfOnly['mae'],
                'rmse' => $cfOnly['rmse'],
                'precision_at_k' => $cfOnly['precision_at_k'],
                'recall_at_k' => $cfOnly['recall_at_k'],
            ],
            'kmeans_user_cf' => [
                'config_name' => 'K-Means User + CF (Proposed)',
                'description' => 'Segmentasi pengguna berbasis rating behavior (Strict Zero-Leakage) + Collaborative Filtering.',
                'mae' => $userKMeansCf['mae'],
                'rmse' => $userKMeansCf['rmse'],
                'precision_at_k' => $userKMeansCf['precision_at_k'],
                'recall_at_k' => $userKMeansCf['recall_at_k'],
            ],
            'kmeans_dest_cf' => [
                'config_name' => 'K-Means Destination + CF (Exploratory)',
                'description' => 'Klasterisasi destinasi (Harga & Rating) sebagai pembobot neighbor overlap + Collaborative Filtering.',
                'mae' => $destKMeansCf['mae'],
                'rmse' => $destKMeansCf['rmse'],
                'precision_at_k' => $destKMeansCf['precision_at_k'],
                'recall_at_k' => $destKMeansCf['recall_at_k'],
            ],
            'kmeans_cf_filtered' => [
                'config_name' => 'K-Means User + CF + Category Filter',
                'description' => 'Klasterisasi Pengguna Two-Stage dengan filter preferensi kategori riwayat pelatihan.',
                'mae' => $filteredCf['mae'],
                'rmse' => $filteredCf['rmse'],
                'precision_at_k' => $filteredCf['precision_at_k'],
                'recall_at_k' => $filteredCf['recall_at_k'],
            ],
        ];
    }
}
