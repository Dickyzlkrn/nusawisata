<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class AutomatedMlRunService
{
    public function __construct(
        protected KMeansService $kMeans,
        protected CollaborativeFilteringService $collaborativeFiltering,
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Inspect dataset health, completeness, and eligibility for ML processing.
     *
     * @return array{
     *     total_rows: int,
     *     users_count: int,
     *     destinations_count: int,
     *     ratings_count: int,
     *     provinces_count: int,
     *     missing_values: int,
     *     duplicate_rows: int,
     *     invalid_ratings: int,
     *     is_eligible: bool,
     *     reasons: array<string>
     * }
     */
    public function inspectDataset(DatasetVersion $version): array
    {
        $versionId = $version->id;

        // Base rating query for this dataset version
        $ratingQuery = Rating::where(function ($q) use ($versionId) {
            $q->where('dataset_version_id', $versionId);
            // Fallback for baseline seeded dataset without dataset_version_id
            if ($versionId === 1) {
                $q->orWhereNull('dataset_version_id');
            }
        });

        $ratingsCount = (clone $ratingQuery)->count();

        $usersCount = User::where('role', 'user')
            ->whereHas('ratings', function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId);
                if ($versionId === 1) {
                    $q->orWhereNull('dataset_version_id');
                }
            })->count();

        $destinationsCount = Destination::where(function ($q) use ($versionId) {
            $q->where('dataset_version_id', $versionId);
            if ($versionId === 1) {
                $q->orWhereNull('dataset_version_id');
            }
        })->orWhereHas('ratings', function ($q) use ($versionId) {
            $q->where('dataset_version_id', $versionId);
            if ($versionId === 1) {
                $q->orWhereNull('dataset_version_id');
            }
        })->count();

        $provincesCount = Province::whereHas('destinations', function ($q) use ($versionId) {
            $q->where('dataset_version_id', $versionId);
            if ($versionId === 1) {
                $q->orWhereNull('dataset_version_id');
            }
        })->count();

        // Check missing required fields
        $missingValues = (clone $ratingQuery)
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhereNull('destination_id')
                    ->orWhereNull('rating');
            })->count();

        // Check invalid rating ranges (outside 1..5)
        $invalidRatings = (clone $ratingQuery)
            ->where(function ($q) {
                $q->where('rating', '<', 1)
                    ->orWhere('rating', '>', 5);
            })->count();

        // Check duplicates: multiple ratings from same user to same destination in this dataset
        $duplicateRows = DB::table('ratings')
            ->select('user_id', 'destination_id', DB::raw('COUNT(*) as count'))
            ->where(function ($q) use ($versionId) {
                $q->where('dataset_version_id', $versionId);
                if ($versionId === 1) {
                    $q->orWhereNull('dataset_version_id');
                }
            })
            ->groupBy('user_id', 'destination_id')
            ->having('count', '>', 1)
            ->get()
            ->count();

        $reasons = [];

        if ($usersCount < 3) {
            $reasons[] = "Jumlah pengguna berating terlalu sedikit ({$usersCount} pengguna, minimal 3).";
        }

        if ($destinationsCount < 3) {
            $reasons[] = "Jumlah destinasi wisata terlalu sedikit ({$destinationsCount} destinasi, minimal 3).";
        }

        if ($ratingsCount < 3) {
            $reasons[] = "Jumlah interaksi ulasan/rating terlalu sedikit ({$ratingsCount} rating, minimal 3).";
        }

        if ($invalidRatings > 0) {
            $reasons[] = "Ditemukan {$invalidRatings} data rating dengan nilai di luar rentang valid (1 - 5).";
        }

        if ($missingValues > 0) {
            $reasons[] = "Ditemukan {$missingValues} data baris dengan kolom wajib kosong (missing values).";
        }

        $isEligible = empty($reasons);

        return [
            'total_rows' => $ratingsCount,
            'users_count' => $usersCount,
            'destinations_count' => $destinationsCount,
            'ratings_count' => $ratingsCount,
            'provinces_count' => $provincesCount,
            'missing_values' => $missingValues,
            'duplicate_rows' => $duplicateRows,
            'invalid_ratings' => $invalidRatings,
            'is_eligible' => $isEligible,
            'reasons' => $reasons,
        ];
    }

    /**
     * Execute the full end-to-end Automated ML Pipeline.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws Exception
     */
    public function execute(DatasetVersion $datasetVersion, array $options = []): MlRun
    {
        $minK = max(2, (int) ($options['min_k'] ?? 2));
        $maxK = max($minK, min(8, (int) ($options['max_k'] ?? 5)));
        $seed = (int) ($options['seed'] ?? 42);
        $maxIterations = (int) ($options['max_iterations'] ?? 50);
        $neighborsCount = (int) ($options['neighbors'] ?? 10);
        $testRatio = (float) ($options['test_ratio'] ?? 0.20);
        $topN = (int) ($options['top_n'] ?? 6);

        // Step 0: Create new ML Run record
        $mlRun = MlRun::create([
            'dataset_version_id' => $datasetVersion->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'queued',
            'progress' => 5,
            'current_stage' => 'queued',
            'is_active' => false,
            'seed' => $seed,
            'started_at' => now(),
            'parameters' => [
                'min_k' => $minK,
                'max_k' => $maxK,
                'seed' => $seed,
                'max_iterations' => $maxIterations,
                'neighbors_count' => $neighborsCount,
                'similarity_method' => 'cosine',
                'test_ratio' => $testRatio,
                'top_n' => $topN,
            ],
        ]);

        try {
            // STAGE 1: Dataset Validation (10%)
            $mlRun->update([
                'status' => 'validating',
                'current_stage' => 'Dataset validation',
                'progress' => 10,
            ]);

            $inspection = $this->inspectDataset($datasetVersion);

            if (! $inspection['is_eligible']) {
                $errorMsg = 'Dataset belum memenuhi syarat untuk diproses: '.implode('; ', $inspection['reasons']);
                $mlRun->update([
                    'status' => 'failed',
                    'current_stage' => 'Validation failed',
                    'progress' => 10,
                    'error_message' => $errorMsg,
                    'finished_at' => now(),
                    'completed_at' => now(),
                ]);

                throw new Exception($errorMsg);
            }

            // STAGE 2: Preprocessing (25%)
            $mlRun->update([
                'status' => 'preprocessing',
                'current_stage' => 'Preprocessing & matrix analysis',
                'progress' => 25,
            ]);

            $matrixStats = $this->collaborativeFiltering->getMatrixStatistics($datasetVersion);

            // STAGE 3: Feature Engineering & Standardization (40%)
            $mlRun->update([
                'status' => 'feature_engineering',
                'current_stage' => 'Feature engineering & standardization',
                'progress' => 40,
            ]);

            $userFeatures = $this->kMeans->buildUserFeatures($datasetVersion);
            if (count($userFeatures) < $minK) {
                throw new Exception('Jumlah pengguna berfitur interactions tidak mencukupi untuk klasterisasi.');
            }

            // STAGE 4: K-Means Candidate Evaluation (55%)
            $mlRun->update([
                'status' => 'clustering',
                'current_stage' => 'K-Means candidate evaluation',
                'progress' => 55,
            ]);

            $kCandidates = [];
            $bestK = $minK;
            $bestScore = -999.0;

            for ($k = $minK; $k <= $maxK; $k++) {
                $runResult = $this->kMeans->run($k, $maxIterations, $seed, false, $datasetVersion);
                $metrics = $runResult['metrics'];

                $kCandidates[$k] = [
                    'k' => $k,
                    'inertia' => $metrics['inertia'],
                    'silhouette' => $metrics['silhouette'],
                    'davies_bouldin' => $metrics['davies_bouldin'],
                    'calinski_harabasz' => $metrics['calinski_harabasz'],
                    'iterations' => $runResult['iterations'],
                ];

                // Heuristic for optimal K: Balance high Silhouette and low Davies-Bouldin
                // Score = Silhouette - (0.1 * DaviesBouldin)
                $score = $metrics['silhouette'] - (0.05 * $metrics['davies_bouldin']);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestK = $k;
                }
            }

            // STAGE 5: Cluster Assignment & Final Evaluation (70%)
            $mlRun->update([
                'status' => 'evaluating_cluster',
                'current_stage' => "Final K-Means clustering (K={$bestK}) & evaluation",
                'progress' => 70,
            ]);

            $finalKMeans = $this->kMeans->run($bestK, $maxIterations, $seed, false, $datasetVersion);

            // Execute Destination Clustering (K=4, StandardScaler, price & destination_rating)
            $destClustering = $this->kMeans->clusterDestinations(4, $maxIterations, $seed, true, $datasetVersion, false, 10, false);

            // STAGE 6: Building Collaborative Filtering (80%)
            $mlRun->update([
                'status' => 'building_cf',
                'current_stage' => 'Building User-Based Collaborative Filtering',
                'progress' => 80,
            ]);

            // STAGE 7: Recommendation Evaluation (80:20 Train/Test Split) (90%)
            $mlRun->update([
                'status' => 'evaluating_recommendation',
                'current_stage' => 'Train/test recommendation evaluation (80:20 split)',
                'progress' => 90,
            ]);

            $cfEvaluation = $this->collaborativeFiltering->evaluateModel(
                $testRatio,
                $topN,
                4.0,
                $datasetVersion
            );

            $strictTrainEvaluation = $this->collaborativeFiltering->evaluateModelStrictTrain(
                $testRatio,
                $topN,
                4.0,
                $datasetVersion,
                $bestK
            );

            $ablationStudy = $this->collaborativeFiltering->runAblationStudy(
                $testRatio,
                $topN,
                4.0,
                $datasetVersion
            );

            // STAGE 8: Saving Results & Finalizing (100%)
            $mlRun->update([
                'status' => 'completed',
                'current_stage' => 'completed',
                'progress' => 100,
                'k' => $bestK,
                'iterations' => $finalKMeans['iterations'],
                'inertia' => $finalKMeans['metrics']['inertia'],
                'silhouette_score' => $finalKMeans['metrics']['silhouette'],
                'davies_bouldin_score' => $finalKMeans['metrics']['davies_bouldin'],
                'calinski_harabasz_score' => $finalKMeans['metrics']['calinski_harabasz'],
                'mae' => $cfEvaluation['mae'],
                'rmse' => $cfEvaluation['rmse'],
                'precision_at_k' => $cfEvaluation['precision_at_k'],
                'recall_at_k' => $cfEvaluation['recall_at_k'],
                'features' => $finalKMeans['features'],
                'metrics' => [
                    'inertia' => $finalKMeans['metrics']['inertia'],
                    'silhouette' => $finalKMeans['metrics']['silhouette'],
                    'davies_bouldin' => $finalKMeans['metrics']['davies_bouldin'],
                    'calinski_harabasz' => $finalKMeans['metrics']['calinski_harabasz'],
                    'mae' => $cfEvaluation['mae'],
                    'rmse' => $cfEvaluation['rmse'],
                    'precision_at_k' => $cfEvaluation['precision_at_k'],
                    'recall_at_k' => $cfEvaluation['recall_at_k'],
                    'test_ratings_count' => $cfEvaluation['test_ratings_count'],
                    'evaluated_users_count' => $cfEvaluation['evaluated_users_count'],
                    'destination_clustering' => [
                        'is_primary_research_method' => false,
                        'module_type' => 'exploratory_destination_analysis',
                        'k' => $destClustering['k'],
                        'silhouette' => $destClustering['metrics']['silhouette'],
                        'davies_bouldin' => $destClustering['metrics']['davies_bouldin'],
                        'calinski_harabasz' => $destClustering['metrics']['calinski_harabasz'],
                        'inertia' => $destClustering['metrics']['inertia'],
                        'has_degenerate_cluster' => $destClustering['has_degenerate_cluster'] ?? true,
                        'degenerate_warning' => $destClustering['degenerate_warning'] ?? null,
                    ],
                ],
                'summary' => [
                    'k_candidates' => $kCandidates,
                    'optimal_k' => $bestK,
                    'cluster_distribution' => $finalKMeans['clusters'],
                    'centroids' => $finalKMeans['centroids'],
                    'pca_data' => $finalKMeans['pca_data'],
                    'user_assignments' => $finalKMeans['user_assignments'],
                    'destination_clustering' => [
                        'is_primary_research_method' => false,
                        'module_type' => 'exploratory_destination_analysis',
                        'research_context' => 'Analisis klaster destinasi merupakan eksperimen eksploratori tambahan dan BUKAN pengganti metodologi utama (User K-Means + User-Based Collaborative Filtering).',
                        'has_degenerate_cluster' => $destClustering['has_degenerate_cluster'] ?? true,
                        'degenerate_warning' => $destClustering['degenerate_warning'] ?? null,
                        'k' => $destClustering['k'],
                        'features' => $destClustering['features'],
                        'excluded_metadata' => $destClustering['excluded_metadata'],
                        'scaler' => $destClustering['scaler'],
                        'metrics' => $destClustering['metrics'],
                        'distribution' => $destClustering['clusters'],
                        'centroids' => $destClustering['centroids'],
                        'scatter_data' => $destClustering['scatter_data'],
                    ],
                    'cf_stats' => $matrixStats,
                    'cf_evaluation' => $cfEvaluation,
                    'strict_train_evaluation' => $strictTrainEvaluation,
                    'ablation_study' => $ablationStudy,
                    'dataset_summary' => $inspection,
                    'execution_duration_sec' => round(now()->diffInRealSeconds($mlRun->started_at), 2),
                ],
                'finished_at' => now(),
                'completed_at' => now(),
            ]);

            // If no ML run is currently active in the entire system, automatically activate this first completed run
            if (! MlRun::where('is_active', true)->exists()) {
                $mlRun->activate();
            }

            return $mlRun->fresh();
        } catch (Exception $e) {
            $mlRun->update([
                'status' => 'failed',
                'current_stage' => 'Execution failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
