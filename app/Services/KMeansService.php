<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KMeansService
{
    /**
     * Feature names used in baseline 12-feature user clustering.
     *
     * @var array<string>
     */
    protected array $featureKeys = [
        'total_ratings',
        'average_rating',
        'rating_std',
        'min_rating',
        'max_rating',
        'rating_5_ratio',
        'rating_4_ratio',
        'rating_3_ratio',
        'rating_low_ratio',
        'unique_categories',
        'unique_provinces',
        'avg_price',
    ];

    /**
     * Feature sets available for user clustering experimentation.
     *
     * @var array<string, array<string>>
     */
    public const FEATURE_SETS = [
        'all_12' => [
            'total_ratings',
            'average_rating',
            'rating_std',
            'min_rating',
            'max_rating',
            'rating_5_ratio',
            'rating_4_ratio',
            'rating_3_ratio',
            'rating_low_ratio',
            'unique_categories',
            'unique_provinces',
            'avg_price',
        ],
        'non_redundant_8' => [
            'total_ratings',
            'average_rating',
            'rating_std',
            'rating_5_ratio',
            'rating_4_ratio',
            'rating_3_ratio',
            'unique_categories',
            'avg_price',
        ],
        'behavior_core_5' => [
            'log_total_ratings',
            'average_rating',
            'rating_std',
            'category_diversity_ratio',
            'log_avg_price',
        ],
        'strict_behavior_3' => [
            'average_rating',
            'rating_std',
            'log_total_ratings',
        ],
    ];

    /**
     * Features strictly used for destination clustering.
     *
     * @var array<string>
     */
    public const DESTINATION_FEATURES = [
        'price',
        'destination_rating',
    ];

    /**
     * Metadata columns explicitly excluded from destination clustering.
     *
     * @var array<string>
     */
    public const DESTINATION_EXCLUDED_COLUMNS = [
        'user_id',
        'place_id',
        'place_name',
        'province',
        'category',
        'place_ratings',
        'visitor_count',
        'popularity_score',
        'popular_day',
        'popular_hour',
    ];

    /**
     * Run K-Means Clustering on Users based on rating behavior.
     *
     * @param  int  $k  Number of clusters
     * @param  int  $maxIterations  Maximum iterations
     * @param  int  $seed  Random seed for reproducibility
     * @param  bool  $persist  Whether to persist results to database
     * @param  string  $featureSet  Feature set to use ('all_12', 'non_redundant_8', 'behavior_core_5', 'strict_behavior_3')
     * @param  int  $nInit  Number of initializations with K-Means++
     * @return array{
     *     k: int,
     *     iterations: int,
     *     converged: bool,
     *     total_users: int,
     *     clusters: array<int, int>,
     *     centroids: array<int, array<string, float>>,
     *     centroids_scaled: array<int, array<string, float>>,
     *     metrics: array{
     *         inertia: float,
     *         silhouette: float,
     *         davies_bouldin: float,
     *         calinski_harabasz: float
     *     },
     *     features: array<string>,
     *     pca_data: array<int, array{user_id: int, name: string, cluster_id: int, x: float, y: float}>,
     *     user_assignments: array<int, array{user_id: int, name: string, cluster_id: int, distance: float}>
     * }
     */
    public function run(
        int $k = 3,
        int $maxIterations = 50,
        int $seed = 42,
        bool $persist = true,
        ?DatasetVersion $datasetVersion = null,
        string $featureSet = 'all_12',
        int $nInit = 10
    ): array {
        $usersData = $this->buildUserFeatures($datasetVersion);
        $activeKeys = self::FEATURE_SETS[$featureSet] ?? $this->featureKeys;

        if (count($usersData) < $k) {
            return [
                'k' => $k,
                'iterations' => 0,
                'converged' => false,
                'total_users' => count($usersData),
                'clusters' => [],
                'centroids' => [],
                'centroids_scaled' => [],
                'metrics' => [
                    'inertia' => 0.0,
                    'silhouette' => 0.0,
                    'davies_bouldin' => 0.0,
                    'calinski_harabasz' => 0.0,
                ],
                'features' => $activeKeys,
                'pca_data' => [],
                'user_assignments' => [],
            ];
        }

        $userIds = array_keys($usersData);
        $rawMatrix = [];
        $userNames = [];
        foreach ($usersData as $uId => $d) {
            $rawMatrix[$uId] = [];
            foreach ($activeKeys as $key) {
                $rawMatrix[$uId][$key] = (float) ($d['features'][$key] ?? 0.0);
            }
            $userNames[$uId] = $d['name'];
        }

        // Standardize features (Z-Score normalization)
        $scaledResult = $this->scaleFeatures($rawMatrix, $activeKeys);
        $scaledMatrix = $scaledResult['scaled'];
        $means = $scaledResult['means'];
        $stds = $scaledResult['stds'];

        // Execute core K-Means with K-Means++ and multi-start
        $core = $this->executeKMeansCore($scaledMatrix, $activeKeys, $k, $maxIterations, $seed, $nInit);

        $assignments = $core['assignments'];
        $distances = $core['distances'];
        $centroids = $core['centroids'];
        $iterations = $core['iterations'];
        $converged = $core['converged'];
        $inertia = $core['inertia'];
        $silhouette = $core['silhouette'];
        $daviesBouldin = $core['davies_bouldin'];
        $calinskiHarabasz = $core['calinski_harabasz'];

        // Convert scaled centroids back to original feature scale for human interpretation
        $unscaledCentroids = [];
        foreach ($centroids as $cId => $cVec) {
            $unscaledCentroids[$cId] = [];
            foreach ($activeKeys as $fKey) {
                $val = ($cVec[$fKey] * $stds[$fKey]) + $means[$fKey];
                $unscaledCentroids[$cId][$fKey] = round($val, 2);
            }
        }

        // Calculate 2D PCA projection for scatter plot visualization
        $pcaData = $this->calculatePca2D($scaledMatrix, $assignments, $userNames, $activeKeys);

        // Cluster counts
        $clusterCounts = [];
        for ($cId = 1; $cId <= $k; $cId++) {
            $clusterCounts[$cId] = count(array_filter($assignments, fn ($c) => $c === $cId));
        }

        // Format user assignments
        $userAssignmentList = [];
        foreach ($assignments as $uId => $cId) {
            $userAssignmentList[] = [
                'user_id' => $uId,
                'name' => $userNames[$uId],
                'cluster_id' => $cId,
                'distance' => $distances[$uId] ?? 0.0,
            ];
        }

        // Persist to database if enabled
        if ($persist) {
            $this->persistClusters($assignments, $distances);

            MlRun::create([
                'dataset_version_id' => $datasetVersion?->id ?? DatasetVersion::getActive()?->id,
                'algorithm' => 'kmeans',
                'parameters' => [
                    'k' => $k,
                    'max_iterations' => $maxIterations,
                    'seed' => $seed,
                    'feature_set' => $featureSet,
                    'n_init' => $nInit,
                    'initialization' => 'kmeans++',
                ],
                'metrics' => [
                    'inertia' => round($inertia, 4),
                    'silhouette' => round($silhouette, 4),
                    'davies_bouldin' => round($daviesBouldin, 4),
                    'calinski_harabasz' => round($calinskiHarabasz, 4),
                    'iterations' => $iterations,
                    'converged' => $converged,
                ],
                'summary' => [
                    'total_users' => count($usersData),
                    'cluster_distribution' => $clusterCounts,
                    'centroids' => $unscaledCentroids,
                    'pca_data' => $pcaData,
                    'feature_set' => $featureSet,
                ],
                'status' => 'completed',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        return [
            'k' => $k,
            'iterations' => $iterations,
            'converged' => $converged,
            'total_users' => count($usersData),
            'clusters' => $clusterCounts,
            'centroids' => $unscaledCentroids,
            'centroids_scaled' => $centroids,
            'metrics' => [
                'inertia' => round($inertia, 4),
                'silhouette' => round($silhouette, 4),
                'davies_bouldin' => round($daviesBouldin, 4),
                'calinski_harabasz' => round($calinskiHarabasz, 4),
            ],
            'features' => $activeKeys,
            'pca_data' => $pcaData,
            'user_assignments' => $userAssignmentList,
        ];
    }

    /**
     * Run K-Means Clustering on Destinations strictly using price and destination_rating.
     * Excludes user_id, place_id, place_name, province, category, place_ratings,
     * visitor_count, popularity_score, popular_day, popular_hour.
     *
     * @param  int  $k  Number of clusters (default 4)
     * @param  int  $maxIterations  Maximum iterations
     * @param  int  $seed  Random seed
     * @param  bool  $persist  Whether to save cluster_id and cluster_distance to destinations table
     * @param  bool  $useLogPrice  Whether to use log1p(price) for highly skewed price distribution
     * @param  int  $nInit  Number of K-Means++ restarts
     * @return array{
     *     k: int,
     *     total_destinations: int,
     *     features: array<string>,
     *     excluded_metadata: array<string>,
     *     scaler: string,
     *     iterations: int,
     *     converged: bool,
     *     clusters: array<int, int>,
     *     centroids: array<int, array<string, float>>,
     *     centroids_scaled: array<int, array<string, float>>,
     *     metrics: array{
     *         inertia: float,
     *         silhouette: float,
     *         davies_bouldin: float,
     *         calinski_harabasz: float
     *     },
     *     scatter_data: array<int, array{destination_id: int, name: string, category: string, price: float, destination_rating: float, x_scaled: float, y_scaled: float, cluster_id: int, distance: float}>,
     *     destination_assignments: array<int, array{destination_id: int, name: string, cluster_id: int, distance: float}>
     * }
     */
    public function clusterDestinations(
        int $k = 4,
        int $maxIterations = 50,
        int $seed = 42,
        bool $persist = true,
        ?DatasetVersion $datasetVersion = null,
        bool $useLogPrice = false,
        int $nInit = 10,
        bool $recordMlRun = true
    ): array {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $destQuery = Destination::query();
        if ($versionId) {
            $version = DatasetVersion::find($versionId);
            if ($version && $version->destinations()->exists()) {
                $destQuery->where('dataset_version_id', $versionId)->orWhereNull('dataset_version_id');
            } else {
                $destQuery->whereHas('ratings', fn ($q) => $q->where('dataset_version_id', $versionId))
                    ->orWhereNull('dataset_version_id');
            }
        }

        $destinations = $destQuery->get();
        if ($destinations->count() < $k) {
            $destinations = Destination::all();
        }

        $rawMatrix = [];
        $meta = [];
        foreach ($destinations as $d) {
            $priceVal = (float) ($d->price ?? 0.0);
            $rawMatrix[$d->id] = [
                'price' => $useLogPrice ? log1p($priceVal) : $priceVal,
                'destination_rating' => (float) ($d->google_rating ?? 4.0),
            ];
            $meta[$d->id] = [
                'id' => $d->id,
                'name' => $d->name,
                'category' => $d->category,
                'price' => $priceVal,
                'destination_rating' => (float) ($d->google_rating ?? 4.0),
            ];
        }

        $keys = self::DESTINATION_FEATURES;
        $n = count($rawMatrix);

        if ($n < $k) {
            return [
                'k' => $k,
                'total_destinations' => $n,
                'features' => $keys,
                'excluded_metadata' => self::DESTINATION_EXCLUDED_COLUMNS,
                'scaler' => 'StandardScaler',
                'iterations' => 0,
                'converged' => false,
                'clusters' => [],
                'centroids' => [],
                'centroids_scaled' => [],
                'metrics' => [
                    'inertia' => 0.0,
                    'silhouette' => 0.0,
                    'davies_bouldin' => 0.0,
                    'calinski_harabasz' => 0.0,
                ],
                'scatter_data' => [],
                'destination_assignments' => [],
            ];
        }

        // Standardize strictly with StandardScaler
        $scaledResult = $this->scaleFeatures($rawMatrix, $keys);
        $scaledMatrix = $scaledResult['scaled'];
        $means = $scaledResult['means'];
        $stds = $scaledResult['stds'];

        // Core K-Means with K-Means++ and multi-start
        $core = $this->executeKMeansCore($scaledMatrix, $keys, $k, $maxIterations, $seed, $nInit);

        $assignments = $core['assignments'];
        $distances = $core['distances'];
        $centroids = $core['centroids'];
        $iterations = $core['iterations'];
        $converged = $core['converged'];
        $inertia = $core['inertia'];
        $silhouette = $core['silhouette'];
        $daviesBouldin = $core['davies_bouldin'];
        $calinskiHarabasz = $core['calinski_harabasz'];

        // Convert scaled centroids back to original units (Rupiah & 1-5 rating)
        $unscaledCentroids = [];
        for ($cId = 1; $cId <= $k; $cId++) {
            $scaledPrice = $centroids[$cId]['price'] ?? 0.0;
            $scaledRating = $centroids[$cId]['destination_rating'] ?? 0.0;

            if ($useLogPrice) {
                $origPrice = exp(($scaledPrice * $stds['price']) + $means['price']) - 1.0;
            } else {
                $origPrice = ($scaledPrice * $stds['price']) + $means['price'];
            }
            $origRating = ($scaledRating * $stds['destination_rating']) + $means['destination_rating'];

            $unscaledCentroids[$cId] = [
                'price' => round(max(0.0, $origPrice), 2),
                'destination_rating' => round(min(5.0, max(1.0, $origRating)), 2),
            ];
        }

        // Prepare 2D scatter data directly on Price vs Rating space
        $scatterData = [];
        $destinationAssignments = [];
        foreach ($scaledMatrix as $id => $vec) {
            $cId = $assignments[$id] ?? 1;
            $dist = $distances[$id] ?? 0.0;

            $point = [
                'destination_id' => $id,
                'name' => $meta[$id]['name'] ?? "Destinasi #{$id}",
                'category' => $meta[$id]['category'] ?? 'Wisata',
                'price' => $meta[$id]['price'] ?? 0.0,
                'destination_rating' => $meta[$id]['destination_rating'] ?? 4.0,
                'x_scaled' => round($vec['price'], 3),
                'y_scaled' => round($vec['destination_rating'], 3),
                'cluster_id' => $cId,
                'distance' => $dist,
            ];

            $scatterData[] = $point;
            $destinationAssignments[] = [
                'destination_id' => $id,
                'name' => $meta[$id]['name'] ?? "Destinasi #{$id}",
                'cluster_id' => $cId,
                'distance' => $dist,
            ];
        }

        // Cluster counts and degenerate/singleton cluster detection
        $clusterCounts = [];
        $hasDegenerateCluster = false;
        $degenerateClusters = [];
        for ($cId = 1; $cId <= $k; $cId++) {
            $cnt = count(array_filter($assignments, fn ($c) => $c === $cId));
            $clusterCounts[$cId] = $cnt;
            if ($cnt <= 1) {
                $hasDegenerateCluster = true;
                $degenerateClusters[] = $cId;
            }
        }

        $degenerateWarning = $hasDegenerateCluster
            ? 'Klaster '.implode(', ', $degenerateClusters).' terdeteksi sebagai degenerate cluster / outlier singleton (n <= 1). Destinasi pada klaster ini terpisah karena karakteristik harga ekstrem (misal: Raja Ampat Rp 500.000) dan bukan merupakan kelompok pola umum.'
            : null;

        // Persist to database if enabled
        if ($persist) {
            $this->persistDestinationClusters($assignments, $distances);

            if ($recordMlRun) {
                MlRun::create([
                    'dataset_version_id' => $versionId,
                    'algorithm' => 'kmeans_destinations',
                    'parameters' => [
                        'k' => $k,
                        'max_iterations' => $maxIterations,
                        'seed' => $seed,
                        'n_init' => $nInit,
                        'scaler' => 'StandardScaler',
                        'features' => $keys,
                        'excluded_metadata' => self::DESTINATION_EXCLUDED_COLUMNS,
                        'use_log_price' => $useLogPrice,
                    ],
                    'metrics' => [
                        'inertia' => round($inertia, 4),
                        'silhouette' => round($silhouette, 4),
                        'davies_bouldin' => round($daviesBouldin, 4),
                        'calinski_harabasz' => round($calinskiHarabasz, 4),
                        'iterations' => $iterations,
                        'converged' => $converged,
                    ],
                    'summary' => [
                        'module_type' => 'exploratory_destination_analysis',
                        'is_primary_research_method' => false,
                        'research_context' => 'Analisis klaster destinasi merupakan eksperimen eksploratori tambahan dan BUKAN pengganti metodologi utama (User K-Means + User-Based Collaborative Filtering).',
                        'has_degenerate_cluster' => $hasDegenerateCluster,
                        'degenerate_warning' => $degenerateWarning,
                        'degenerate_clusters' => $degenerateClusters,
                        'total_destinations' => $n,
                        'cluster_distribution' => $clusterCounts,
                        'centroids' => $unscaledCentroids,
                        'scatter_data' => $scatterData,
                        'features' => $keys,
                        'excluded_columns' => self::DESTINATION_EXCLUDED_COLUMNS,
                    ],
                    'status' => 'completed',
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);
            }
        }

        return [
            'module_type' => 'exploratory_destination_analysis',
            'is_primary_research_method' => false,
            'research_context' => 'Analisis klaster destinasi merupakan eksperimen eksploratori tambahan dan BUKAN pengganti metodologi utama (User K-Means + User-Based Collaborative Filtering).',
            'has_degenerate_cluster' => $hasDegenerateCluster,
            'degenerate_warning' => $degenerateWarning,
            'degenerate_clusters' => $degenerateClusters,
            'k' => $k,
            'total_destinations' => $n,
            'features' => $keys,
            'excluded_metadata' => self::DESTINATION_EXCLUDED_COLUMNS,
            'scaler' => 'StandardScaler',
            'iterations' => $iterations,
            'converged' => $converged,
            'clusters' => $clusterCounts,
            'centroids' => $unscaledCentroids,
            'centroids_scaled' => $centroids,
            'metrics' => [
                'inertia' => round($inertia, 4),
                'silhouette' => round($silhouette, 4),
                'davies_bouldin' => round($daviesBouldin, 4),
                'calinski_harabasz' => round($calinskiHarabasz, 4),
            ],
            'scatter_data' => $scatterData,
            'destination_assignments' => $destinationAssignments,
        ];
    }

    /**
     * Reusable K-Means optimization core supporting K-Means++ and multi-start restarts.
     *
     * @param  array<int, array<string, float>>  $scaledMatrix
     * @param  array<string>  $keys
     * @return array{
     *     assignments: array<int, int>,
     *     distances: array<int, float>,
     *     centroids: array<int, array<string, float>>,
     *     iterations: int,
     *     converged: bool,
     *     inertia: float,
     *     silhouette: float,
     *     davies_bouldin: float,
     *     calinski_harabasz: float
     * }
     */
    protected function executeKMeansCore(
        array $scaledMatrix,
        array $keys,
        int $k,
        int $maxIterations,
        int $seed,
        int $nInit = 10
    ): array {
        $ids = array_keys($scaledMatrix);
        $bestInertia = PHP_FLOAT_MAX;
        $bestAssignments = [];
        $bestDistances = [];
        $bestCentroids = [];
        $bestIterations = 0;
        $bestConverged = false;

        $restarts = max(1, $nInit);

        for ($init = 0; $init < $restarts; $init++) {
            $initSeed = $seed + ($init * 17);
            $centroids = $this->kmeansPlusPlusInit($scaledMatrix, $k, $initSeed, $keys);

            $assignments = [];
            $distances = [];
            $iterCount = 0;
            $converged = false;

            for ($iter = 0; $iter < $maxIterations; $iter++) {
                $iterCount++;
                $newAssignments = [];
                $newDistances = [];

                foreach ($scaledMatrix as $id => $point) {
                    $minDist = PHP_FLOAT_MAX;
                    $bestC = 1;

                    foreach ($centroids as $cId => $cVec) {
                        $dist = $this->euclideanDistance($point, $cVec, $keys);
                        if ($dist < $minDist) {
                            $minDist = $dist;
                            $bestC = $cId;
                        }
                    }

                    $newAssignments[$id] = $bestC;
                    $newDistances[$id] = round($minDist, 4);
                }

                if ($newAssignments === $assignments) {
                    $converged = true;
                    break;
                }

                $assignments = $newAssignments;
                $distances = $newDistances;

                // Update centroids
                for ($cId = 1; $cId <= $k; $cId++) {
                    $members = array_keys(array_filter($assignments, fn ($c) => $c === $cId));

                    if (count($members) === 0) {
                        $randId = $ids[mt_rand(0, count($ids) - 1)];
                        $centroids[$cId] = $scaledMatrix[$randId];

                        continue;
                    }

                    $newCentroid = array_fill_keys($keys, 0.0);
                    foreach ($members as $mId) {
                        foreach ($keys as $key) {
                            $newCentroid[$key] += $scaledMatrix[$mId][$key];
                        }
                    }

                    $cnt = count($members);
                    foreach ($keys as $key) {
                        $newCentroid[$key] /= $cnt;
                    }

                    $centroids[$cId] = $newCentroid;
                }
            }

            // Calculate inertia for this restart
            $curInertia = $this->calculateInertia($scaledMatrix, $centroids, $assignments, $keys);

            if ($curInertia < $bestInertia) {
                $bestInertia = $curInertia;
                $bestAssignments = $assignments;
                $bestDistances = $distances;
                $bestCentroids = $centroids;
                $bestIterations = $iterCount;
                $bestConverged = $converged;
            }
        }

        // Calculate final evaluation metrics on the winning restart
        $silhouette = $this->calculateSilhouette($scaledMatrix, $bestAssignments, $k, $keys);
        $daviesBouldin = $this->calculateDaviesBouldin($scaledMatrix, $bestCentroids, $bestAssignments, $k, $keys);
        $calinskiHarabasz = $this->calculateCalinskiHarabasz($scaledMatrix, $bestCentroids, $bestAssignments, $k, $keys);

        return [
            'assignments' => $bestAssignments,
            'distances' => $bestDistances,
            'centroids' => $bestCentroids,
            'iterations' => $bestIterations,
            'converged' => $bestConverged,
            'inertia' => $bestInertia,
            'silhouette' => $silhouette,
            'davies_bouldin' => $daviesBouldin,
            'calinski_harabasz' => $calinskiHarabasz,
        ];
    }

    /**
     * Deterministic K-Means++ initialization algorithm based on D(x)^2 probability distribution.
     *
     * @param  array<int, array<string, float>>  $scaledMatrix
     * @param  array<string>  $keys
     * @return array<int, array<string, float>>
     */
    public function kmeansPlusPlusInit(array $scaledMatrix, int $k, int $seed, array $keys): array
    {
        mt_srand($seed);
        $ids = array_keys($scaledMatrix);
        $n = count($ids);

        if ($n === 0 || $k <= 0) {
            return [];
        }

        // 1st centroid chosen uniformly at random
        $firstId = $ids[mt_rand(0, $n - 1)];
        $centroids = [1 => $scaledMatrix[$firstId]];

        for ($c = 2; $c <= $k; $c++) {
            $distSqList = [];
            $sumDistSq = 0.0;

            foreach ($scaledMatrix as $id => $point) {
                $minDistSq = PHP_FLOAT_MAX;

                for ($prev = 1; $prev < $c; $prev++) {
                    $dSq = 0.0;
                    foreach ($keys as $key) {
                        $diff = ($point[$key] ?? 0.0) - ($centroids[$prev][$key] ?? 0.0);
                        $dSq += ($diff * $diff);
                    }
                    if ($dSq < $minDistSq) {
                        $minDistSq = $dSq;
                    }
                }

                $distSqList[$id] = $minDistSq;
                $sumDistSq += $minDistSq;
            }

            if ($sumDistSq < 1e-9) {
                $centroids[$c] = $scaledMatrix[$ids[mt_rand(0, $n - 1)]];

                continue;
            }

            $threshold = (mt_rand() / mt_getrandmax()) * $sumDistSq;
            $accumulator = 0.0;
            $selectedId = $ids[0];

            foreach ($distSqList as $id => $dSq) {
                $accumulator += $dSq;
                if ($accumulator >= $threshold) {
                    $selectedId = $id;
                    break;
                }
            }

            $centroids[$c] = $scaledMatrix[$selectedId];
        }

        return $centroids;
    }

    /**
     * Backward-compatible wrapper for controller.
     */
    public function clusterUsers(int $k = 3, int $maxIterations = 30): array
    {
        return $this->run($k, $maxIterations);
    }

    /**
     * Get visualization data for users from latest ML run or compute once.
     */
    public function getLatestVisualizationData(): array
    {
        $latestRun = MlRun::getActive() ?? MlRun::where('algorithm', 'kmeans')->latest()->first();

        if ($latestRun && ! empty($latestRun->summary['pca_data']) && ! empty($latestRun->summary['centroids'])) {
            return [
                'k' => (int) ($latestRun->parameters['k'] ?? 3),
                'metrics' => $latestRun->metrics ?? [],
                'clusters' => $latestRun->summary['cluster_distribution'] ?? [],
                'centroids' => $latestRun->summary['centroids'] ?? [],
                'pca_data' => $latestRun->summary['pca_data'] ?? [],
            ];
        }

        $runResult = $this->run(3, 30, 42, false);

        if ($latestRun) {
            $summary = $latestRun->summary ?? [];
            $summary['centroids'] = $runResult['centroids'];
            $summary['pca_data'] = $runResult['pca_data'];
            $latestRun->update(['summary' => $summary]);
        }

        return [
            'k' => $runResult['k'],
            'metrics' => $runResult['metrics'],
            'clusters' => $runResult['clusters'],
            'centroids' => $runResult['centroids'],
            'pca_data' => $runResult['pca_data'],
        ];
    }

    /**
     * Get visualization data for destination clustering.
     */
    public function getLatestDestinationVisualizationData(?DatasetVersion $datasetVersion = null): array
    {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $latestRun = MlRun::where('algorithm', 'kmeans_destinations')
            ->when($versionId, fn ($q) => $q->where('dataset_version_id', $versionId))
            ->latest()
            ->first();

        if ($latestRun && ! empty($latestRun->summary['scatter_data']) && ! empty($latestRun->summary['centroids'])) {
            return [
                'k' => (int) ($latestRun->parameters['k'] ?? 4),
                'metrics' => $latestRun->metrics ?? [],
                'clusters' => $latestRun->summary['cluster_distribution'] ?? [],
                'centroids' => $latestRun->summary['centroids'] ?? [],
                'scatter_data' => $latestRun->summary['scatter_data'] ?? [],
                'total_destinations' => (int) ($latestRun->summary['total_destinations'] ?? 0),
                'has_degenerate_cluster' => (bool) ($latestRun->summary['has_degenerate_cluster'] ?? true),
                'degenerate_warning' => $latestRun->summary['degenerate_warning'] ?? 'Klaster 4 (n=1) terdeteksi sebagai degenerate cluster / outlier singleton.',
                'module_type' => 'exploratory_destination_analysis',
                'is_primary_research_method' => false,
            ];
        }

        $runResult = $this->clusterDestinations(4, 50, 42, false, $datasetVersion);

        return [
            'k' => $runResult['k'],
            'metrics' => $runResult['metrics'],
            'clusters' => $runResult['clusters'],
            'centroids' => $runResult['centroids'],
            'scatter_data' => $runResult['scatter_data'],
            'total_destinations' => $runResult['total_destinations'],
            'has_degenerate_cluster' => $runResult['has_degenerate_cluster'],
            'degenerate_warning' => $runResult['degenerate_warning'],
            'module_type' => 'exploratory_destination_analysis',
            'is_primary_research_method' => false,
        ];
    }

    /**
     * Evaluate multiple K candidates for users.
     */
    public function evaluateKCandidates(int $minK = 2, int $maxK = 6, string $featureSet = 'all_12'): array
    {
        $usersData = $this->buildUserFeatures();
        if (count($usersData) < $minK) {
            return [];
        }

        $results = [];
        $maxK = min($maxK, count($usersData) - 1);

        for ($k = $minK; $k <= $maxK; $k++) {
            $run = $this->run($k, 30, 42, false, null, $featureSet, 5);
            $results[$k] = [
                'k' => $k,
                'inertia' => $run['metrics']['inertia'],
                'silhouette' => $run['metrics']['silhouette'],
                'davies_bouldin' => $run['metrics']['davies_bouldin'],
                'calinski_harabasz' => $run['metrics']['calinski_harabasz'],
                'iterations' => $run['iterations'],
            ];
        }

        return $results;
    }

    /**
     * Evaluate multiple K candidates for destinations strictly on price & rating.
     */
    public function evaluateDestinationKCandidates(int $minK = 2, int $maxK = 6, ?DatasetVersion $datasetVersion = null): array
    {
        $results = [];

        for ($k = $minK; $k <= $maxK; $k++) {
            $run = $this->clusterDestinations($k, 35, 42, false, $datasetVersion, false, 5);
            $results[$k] = [
                'k' => $k,
                'inertia' => $run['metrics']['inertia'],
                'silhouette' => $run['metrics']['silhouette'],
                'davies_bouldin' => $run['metrics']['davies_bouldin'],
                'calinski_harabasz' => $run['metrics']['calinski_harabasz'],
                'iterations' => $run['iterations'],
            ];
        }

        return $results;
    }

    /**
     * Evaluate clustering stability across multiple random seeds.
     *
     * @param  int  $k  Number of clusters
     * @param  array<int>  $seeds  List of random seeds
     * @param  string  $featureSet  Feature set
     * @return array{
     *     k: int,
     *     feature_set: string,
     *     seeds_tested: array<int>,
     *     metrics_per_seed: array<int, array{silhouette: float, davies_bouldin: float, inertia: float, calinski_harabasz: float, cluster_sizes: array<int, int>}>,
     *     mean_silhouette: float,
     *     std_silhouette: float,
     *     is_stable: bool
     * }
     */
    public function evaluateClusterStability(
        int $k = 3,
        array $seeds = [42, 43, 44, 45, 46],
        string $featureSet = 'all_12',
        ?DatasetVersion $datasetVersion = null
    ): array {
        $metricsPerSeed = [];
        $silhouettes = [];

        foreach ($seeds as $seed) {
            $run = $this->run($k, 40, $seed, false, $datasetVersion, $featureSet, 10);
            $sil = $run['metrics']['silhouette'];
            $silhouettes[] = $sil;
            $metricsPerSeed[$seed] = [
                'silhouette' => $sil,
                'davies_bouldin' => $run['metrics']['davies_bouldin'],
                'inertia' => $run['metrics']['inertia'],
                'calinski_harabasz' => $run['metrics']['calinski_harabasz'],
                'cluster_sizes' => $run['clusters'],
            ];
        }

        $meanSil = count($silhouettes) > 0 ? array_sum($silhouettes) / count($silhouettes) : 0.0;
        $variance = 0.0;
        foreach ($silhouettes as $s) {
            $variance += pow($s - $meanSil, 2);
        }
        $stdSil = count($silhouettes) > 1 ? sqrt($variance / (count($silhouettes) - 1)) : 0.0;

        return [
            'k' => $k,
            'feature_set' => $featureSet,
            'seeds_tested' => $seeds,
            'metrics_per_seed' => $metricsPerSeed,
            'mean_silhouette' => round($meanSil, 4),
            'std_silhouette' => round($stdSil, 4),
            'is_stable' => $stdSil < 0.05,
        ];
    }

    /**
     * Rebuild and persist K-Means clusters for a specific dataset version.
     */
    public function rebuildForDataset(DatasetVersion $version, int $k = 3): array
    {
        // Cluster destinations using K=4 with StandardScaler
        $this->clusterDestinations(4, 50, 42, true, $version);

        // Cluster users using K=3
        return $this->run($k, 50, 42, true, $version);
    }

    /**
     * Build feature vectors for all users who have given ratings.
     *
     * @return array<int, array{name: string, features: array<string, float>}>
     */
    public function buildUserFeatures(?DatasetVersion $datasetVersion = null): array
    {
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        $users = User::where('role', 'user')
            ->with(['ratings' => function ($q) use ($versionId) {
                if ($versionId) {
                    $q->where(function ($sub) use ($versionId) {
                        $sub->where('dataset_version_id', $versionId)
                            ->orWhereNull('dataset_version_id');
                    });
                }
                $q->with('destination');
            }])
            ->get();

        $dataset = [];

        foreach ($users as $user) {
            $ratings = $user->ratings;

            if ($ratings->isEmpty()) {
                continue;
            }

            $scores = $ratings->pluck('rating')->all();
            $totalRatings = count($scores);
            $avgRating = array_sum($scores) / $totalRatings;

            $variance = 0.0;
            foreach ($scores as $s) {
                $variance += pow($s - $avgRating, 2);
            }
            $stdRating = $totalRatings > 1 ? sqrt($variance / ($totalRatings - 1)) : 0.0;

            $cnt5 = 0;
            $cnt4 = 0;
            $cnt3 = 0;
            $cntLow = 0;
            foreach ($scores as $s) {
                if ($s === 5) {
                    $cnt5++;
                } elseif ($s === 4) {
                    $cnt4++;
                } elseif ($s === 3) {
                    $cnt3++;
                } else {
                    $cntLow++;
                }
            }

            $categories = [];
            $provinces = [];
            $prices = [];
            foreach ($ratings as $r) {
                if ($r->destination) {
                    $categories[$r->destination->category] = true;
                    $provinces[$r->destination->province_id] = true;
                    $prices[] = (float) $r->destination->price;
                }
            }

            $avgPrice = count($prices) > 0 ? (array_sum($prices) / count($prices)) : 0.0;

            $dataset[$user->id] = [
                'name' => $user->name,
                'features' => [
                    'total_ratings' => (float) $totalRatings,
                    'average_rating' => round($avgRating, 3),
                    'rating_std' => round($stdRating, 3),
                    'min_rating' => (float) min($scores),
                    'max_rating' => (float) max($scores),
                    'rating_5_ratio' => round($cnt5 / $totalRatings, 3),
                    'rating_4_ratio' => round($cnt4 / $totalRatings, 3),
                    'rating_3_ratio' => round($cnt3 / $totalRatings, 3),
                    'rating_low_ratio' => round($cntLow / $totalRatings, 3),
                    'unique_categories' => (float) count($categories),
                    'unique_provinces' => (float) count($provinces),
                    'avg_price' => round($avgPrice, 2),
                    // Transformed and non-redundant variables:
                    'log_total_ratings' => round(log1p($totalRatings), 4),
                    'log_avg_price' => round(log1p($avgPrice), 4),
                    'category_diversity_ratio' => round(count($categories) / max(1, $totalRatings), 4),
                    'province_diversity_ratio' => round(count($provinces) / max(1, $totalRatings), 4),
                ],
            ];
        }

        return $dataset;
    }

    /**
     * Standardize features using Z-score (mean=0, std=1).
     *
     * @param  array<int, array<string, float>>  $matrix
     * @param  ?array<string>  $keys
     * @return array{
     *     scaled: array<int, array<string, float>>,
     *     means: array<string, float>,
     *     stds: array<string, float>
     * }
     */
    public function scaleFeatures(array $matrix, ?array $keys = null): array
    {
        $keys = $keys ?? $this->featureKeys;
        $n = count($matrix);
        if ($n === 0) {
            return ['scaled' => [], 'means' => [], 'stds' => []];
        }

        $means = [];
        $stds = [];

        foreach ($keys as $key) {
            $sum = 0.0;
            foreach ($matrix as $row) {
                $sum += ($row[$key] ?? 0.0);
            }
            $means[$key] = $sum / $n;
        }

        foreach ($keys as $key) {
            $sumSq = 0.0;
            foreach ($matrix as $row) {
                $diff = ($row[$key] ?? 0.0) - $means[$key];
                $sumSq += ($diff * $diff);
            }
            $variance = $sumSq / $n;
            $stds[$key] = $variance > 1e-9 ? sqrt($variance) : 1.0;
        }

        $scaled = [];
        foreach ($matrix as $id => $row) {
            $scaledRow = [];
            foreach ($keys as $key) {
                if ($stds[$key] > 1e-9) {
                    $scaledRow[$key] = ($row[$key] - $means[$key]) / $stds[$key];
                } else {
                    $scaledRow[$key] = 0.0;
                }
            }
            $scaled[$id] = $scaledRow;
        }

        return [
            'scaled' => $scaled,
            'means' => $means,
            'stds' => $stds,
        ];
    }

    /**
     * Calculate Euclidean distance between two vectors across specified keys.
     */
    protected function euclideanDistance(array $v1, array $v2, ?array $keys = null): float
    {
        $keys = $keys ?? $this->featureKeys;
        $sum = 0.0;
        foreach ($keys as $key) {
            $diff = ($v1[$key] ?? 0.0) - ($v2[$key] ?? 0.0);
            $sum += ($diff * $diff);
        }

        return sqrt($sum);
    }

    /**
     * Calculate Inertia (Within-Cluster Sum of Squares / WCSS).
     */
    public function calculateInertia(array $scaledMatrix, array $centroids, array $assignments, ?array $keys = null): float
    {
        $keys = $keys ?? $this->featureKeys;
        $inertia = 0.0;

        foreach ($scaledMatrix as $id => $vector) {
            $cId = $assignments[$id] ?? null;
            if ($cId && isset($centroids[$cId])) {
                $dist = $this->euclideanDistance($vector, $centroids[$cId], $keys);
                $inertia += ($dist * $dist);
            }
        }

        return $inertia;
    }

    /**
     * Calculate Silhouette Score across all clustered samples.
     */
    public function calculateSilhouette(array $scaledMatrix, array $assignments, int $k, ?array $keys = null): float
    {
        if ($k <= 1 || count($scaledMatrix) <= $k) {
            return 0.0;
        }

        $keys = $keys ?? $this->featureKeys;
        $clusterMembers = [];
        foreach ($assignments as $id => $cId) {
            $clusterMembers[$cId][] = $id;
        }

        $silhouetteScores = [];

        foreach ($scaledMatrix as $id => $vector) {
            $myCluster = $assignments[$id] ?? null;
            if (! $myCluster) {
                continue;
            }

            $myMembers = $clusterMembers[$myCluster] ?? [];

            if (count($myMembers) <= 1) {
                $silhouetteScores[] = 0.0;

                continue;
            }

            $distSum = 0.0;
            foreach ($myMembers as $otherId) {
                if ($otherId !== $id) {
                    $distSum += $this->euclideanDistance($vector, $scaledMatrix[$otherId], $keys);
                }
            }
            $a = $distSum / (count($myMembers) - 1);

            $b = PHP_FLOAT_MAX;
            for ($otherCluster = 1; $otherCluster <= $k; $otherCluster++) {
                if ($otherCluster === $myCluster) {
                    continue;
                }
                $otherMembers = $clusterMembers[$otherCluster] ?? [];
                if (count($otherMembers) === 0) {
                    continue;
                }

                $oDistSum = 0.0;
                foreach ($otherMembers as $otherId) {
                    $oDistSum += $this->euclideanDistance($vector, $scaledMatrix[$otherId], $keys);
                }
                $avgDist = $oDistSum / count($otherMembers);
                if ($avgDist < $b) {
                    $b = $avgDist;
                }
            }

            $denom = max($a, $b);
            $s = $denom > 1e-9 ? (($b - $a) / $denom) : 0.0;
            $silhouetteScores[] = $s;
        }

        return count($silhouetteScores) > 0 ? (array_sum($silhouetteScores) / count($silhouetteScores)) : 0.0;
    }

    /**
     * Calculate Davies-Bouldin Index.
     */
    public function calculateDaviesBouldin(array $scaledMatrix, array $centroids, array $assignments, int $k, ?array $keys = null): float
    {
        if ($k <= 1) {
            return 0.0;
        }

        $keys = $keys ?? $this->featureKeys;

        $dispersion = [];
        for ($cId = 1; $cId <= $k; $cId++) {
            $members = array_keys(array_filter($assignments, fn ($c) => $c === $cId));
            if (count($members) === 0) {
                $dispersion[$cId] = 0.0;

                continue;
            }

            $sum = 0.0;
            foreach ($members as $id) {
                $sum += $this->euclideanDistance($scaledMatrix[$id], $centroids[$cId], $keys);
            }
            $dispersion[$cId] = $sum / count($members);
        }

        $dValues = [];
        for ($i = 1; $i <= $k; $i++) {
            $maxR = 0.0;
            for ($j = 1; $j <= $k; $j++) {
                if ($i === $j) {
                    continue;
                }
                $centroidDist = $this->euclideanDistance($centroids[$i], $centroids[$j], $keys);
                if ($centroidDist > 1e-9) {
                    $r = ($dispersion[$i] + $dispersion[$j]) / $centroidDist;
                    if ($r > $maxR) {
                        $maxR = $r;
                    }
                }
            }
            $dValues[] = $maxR;
        }

        return count($dValues) > 0 ? (array_sum($dValues) / $k) : 0.0;
    }

    /**
     * Calculate Calinski-Harabasz Index (Variance Ratio Criterion).
     */
    public function calculateCalinskiHarabasz(array $scaledMatrix, array $centroids, array $assignments, int $k, ?array $keys = null): float
    {
        $n = count($scaledMatrix);
        if ($k <= 1 || $n <= $k) {
            return 0.0;
        }

        $keys = $keys ?? $this->featureKeys;

        $overallCentroid = array_fill_keys($keys, 0.0);
        foreach ($scaledMatrix as $vector) {
            foreach ($keys as $fKey) {
                $overallCentroid[$fKey] += $vector[$fKey];
            }
        }
        foreach ($keys as $fKey) {
            $overallCentroid[$fKey] /= $n;
        }

        $ssb = 0.0;
        for ($cId = 1; $cId <= $k; $cId++) {
            $cnt = count(array_filter($assignments, fn ($c) => $c === $cId));
            if ($cnt > 0 && isset($centroids[$cId])) {
                $dist = $this->euclideanDistance($centroids[$cId], $overallCentroid, $keys);
                $ssb += ($cnt * $dist * $dist);
            }
        }

        $ssw = $this->calculateInertia($scaledMatrix, $centroids, $assignments, $keys);

        if ($ssw < 1e-9) {
            return 0.0;
        }

        return ($ssb / ($k - 1)) / ($ssw / ($n - $k));
    }

    /**
     * Compute 2D Principal Component Analysis (PCA) projection for visualization.
     *
     * @param  array<int, array<string, float>>  $scaledMatrix
     * @param  array<int, int>  $assignments
     * @param  array<int, string>  $userNames
     * @param  ?array<string>  $keys
     * @return array<int, array{user_id: int, name: string, cluster_id: int, x: float, y: float}>
     */
    protected function calculatePca2D(array $scaledMatrix, array $assignments, array $userNames, ?array $keys = null): array
    {
        $keys = $keys ?? $this->featureKeys;
        $n = count($scaledMatrix);
        $d = count($keys);
        if ($n < 2) {
            return [];
        }

        $rows = array_values($scaledMatrix);

        $cov = [];
        for ($i = 0; $i < $d; $i++) {
            $keyI = $keys[$i];
            for ($j = 0; $j < $d; $j++) {
                $keyJ = $keys[$j];
                $sum = 0.0;
                for ($rowIdx = 0; $rowIdx < $n; $rowIdx++) {
                    $sum += ($rows[$rowIdx][$keyI] * $rows[$rowIdx][$keyJ]);
                }
                $cov[$i][$j] = $sum / ($n - 1);
            }
        }

        $v1 = $this->powerIteration($cov, $d);

        $lambda1 = $this->rayleighQuotient($cov, $v1, $d);
        $covDeflated = $cov;
        for ($i = 0; $i < $d; $i++) {
            for ($j = 0; $j < $d; $j++) {
                $covDeflated[$i][$j] -= ($lambda1 * $v1[$i] * $v1[$j]);
            }
        }

        $v2 = $this->powerIteration($covDeflated, $d);

        $pcaPoints = [];
        foreach ($scaledMatrix as $uId => $vector) {
            $x = 0.0;
            $y = 0.0;
            for ($i = 0; $i < $d; $i++) {
                $val = $vector[$keys[$i]] ?? 0.0;
                $x += $val * $v1[$i];
                $y += $val * $v2[$i];
            }

            $pcaPoints[] = [
                'user_id' => $uId,
                'name' => $userNames[$uId] ?? "User #{$uId}",
                'cluster_id' => $assignments[$uId] ?? 1,
                'x' => round($x, 3),
                'y' => round($y, 3),
            ];
        }

        return $pcaPoints;
    }

    /**
     * Power iteration algorithm to extract dominant eigenvector.
     */
    protected function powerIteration(array $matrix, int $d, int $maxIter = 40): array
    {
        $v = [];
        for ($i = 0; $i < $d; $i++) {
            $v[$i] = (float) rand(1, 10);
        }
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));
        for ($i = 0; $i < $d; $i++) {
            $v[$i] /= ($norm ?: 1.0);
        }

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $w = array_fill(0, $d, 0.0);
            for ($i = 0; $i < $d; $i++) {
                for ($j = 0; $j < $d; $j++) {
                    $w[$i] += $matrix[$i][$j] * $v[$j];
                }
            }

            $normW = sqrt(array_sum(array_map(fn ($x) => $x * $x, $w)));
            if ($normW < 1e-9) {
                break;
            }

            for ($i = 0; $i < $d; $i++) {
                $v[$i] = $w[$i] / $normW;
            }
        }

        return $v;
    }

    /**
     * Compute Rayleigh quotient (eigenvalue).
     */
    protected function rayleighQuotient(array $matrix, array $v, int $d): float
    {
        $num = 0.0;
        for ($i = 0; $i < $d; $i++) {
            $rowSum = 0.0;
            for ($j = 0; $j < $d; $j++) {
                $rowSum += $matrix[$i][$j] * $v[$j];
            }
            $num += $v[$i] * $rowSum;
        }

        return $num;
    }

    /**
     * Persist user cluster assignments and distances to database.
     */
    protected function persistClusters(array $assignments, array $distances): void
    {
        DB::transaction(function () use ($assignments, $distances) {
            User::whereDoesntHave('ratings')->update([
                'cluster_id' => null,
                'cluster_distance' => null,
            ]);

            foreach ($assignments as $uId => $clusterId) {
                User::where('id', $uId)->update([
                    'cluster_id' => $clusterId,
                    'cluster_distance' => $distances[$uId] ?? null,
                ]);
            }
        });
    }

    /**
     * Persist destination cluster assignments and distances to database.
     */
    protected function persistDestinationClusters(array $assignments, array $distances): void
    {
        DB::transaction(function () use ($assignments, $distances) {
            foreach ($assignments as $dId => $clusterId) {
                Destination::where('id', $dId)->update([
                    'cluster_id' => $clusterId,
                    'cluster_distance' => $distances[$dId] ?? null,
                ]);
            }
        });
    }
}
