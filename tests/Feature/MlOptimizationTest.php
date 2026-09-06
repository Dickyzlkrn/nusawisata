<?php

namespace Tests\Feature;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\CollaborativeFilteringService;
use App\Services\KMeansService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected DatasetVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@nusawisata.com',
        ]);

        $this->version = DatasetVersion::create([
            'name' => 'Dataset Uji ML',
            'version' => 'v1_test',
            'original_filename' => 'test.csv',
            'status' => 'active',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $province = Province::create([
            'name' => 'Bali',
            'slug' => 'bali',
            'island' => 'Kepulauan Nusa Tenggara',
        ]);

        // Seed 10 destinations with various prices and ratings
        $prices = [10000, 15000, 20000, 25000, 50000, 75000, 100000, 150000, 250000, 500000];
        $ratings = [4.5, 4.7, 4.2, 3.8, 4.6, 4.3, 4.8, 4.9, 4.4, 4.9];

        for ($i = 0; $i < 10; $i++) {
            Destination::create([
                'dataset_version_id' => $this->version->id,
                'province_id' => $province->id,
                'name' => "Destinasi Uji {$i}",
                'slug' => "destinasi-uji-{$i}",
                'category' => 'Alam',
                'description' => 'Deskripsi destinasi uji',
                'price' => $prices[$i],
                'latitude' => -8.4 + ($i * 0.05),
                'longitude' => 115.1 + ($i * 0.05),
                'google_rating' => $ratings[$i],
                'review_count' => 100 * ($i + 1),
            ]);
        }

        // Seed 8 users with ratings
        $users = User::factory()->count(8)->create(['role' => 'user']);
        $destIds = Destination::pluck('id')->all();

        foreach ($users as $uIdx => $u) {
            foreach ($destIds as $dIdx => $dId) {
                if (($uIdx + $dIdx) % 2 === 0) {
                    Rating::create([
                        'user_id' => $u->id,
                        'destination_id' => $dId,
                        'dataset_version_id' => $this->version->id,
                        'rating' => (($uIdx + $dIdx) % 5) + 1,
                    ]);
                }
            }
        }
    }

    public function test_destination_clustering_uses_only_price_and_rating_with_standard_scaler(): void
    {
        $kMeans = app(KMeansService::class);
        $result = $kMeans->clusterDestinations(4, 50, 42, false, $this->version);

        $this->assertEquals(4, $result['k']);
        $this->assertEquals(['price', 'destination_rating'], $result['features']);
        $this->assertEquals('StandardScaler', $result['scaler']);

        // Verify non-features are strictly listed as excluded
        $this->assertContains('user_id', $result['excluded_metadata']);
        $this->assertContains('place_id', $result['excluded_metadata']);
        $this->assertContains('place_name', $result['excluded_metadata']);
        $this->assertContains('province', $result['excluded_metadata']);
        $this->assertContains('category', $result['excluded_metadata']);
        $this->assertContains('visitor_count', $result['excluded_metadata']);

        // Verify metrics
        $this->assertArrayHasKey('inertia', $result['metrics']);
        $this->assertArrayHasKey('silhouette', $result['metrics']);
        $this->assertArrayHasKey('davies_bouldin', $result['metrics']);
        $this->assertArrayHasKey('calinski_harabasz', $result['metrics']);
        $this->assertGreaterThan(0.25, $result['metrics']['silhouette']);
    }

    public function test_destination_clustering_persists_to_database_and_records_ml_run(): void
    {
        $kMeans = app(KMeansService::class);
        $result = $kMeans->clusterDestinations(4, 50, 42, true, $this->version);

        $this->assertTrue(Destination::whereNotNull('cluster_id')->count() > 0);

        $mlRun = MlRun::where('algorithm', 'kmeans_destinations')->latest()->first();
        $this->assertNotNull($mlRun);
        $this->assertEquals(4, $mlRun->parameters['k']);
        $this->assertEquals(['price', 'destination_rating'], $mlRun->parameters['features']);
    }

    public function test_user_clustering_supports_kmeans_plus_plus_and_feature_sets(): void
    {
        $kMeans = app(KMeansService::class);
        $result = $kMeans->run(3, 40, 42, false, $this->version, 'strict_behavior_3', 5);

        $this->assertEquals(3, $result['k']);
        $this->assertEquals(['average_rating', 'rating_std', 'log_total_ratings'], $result['features']);
        $this->assertArrayHasKey('silhouette', $result['metrics']);
        $this->assertArrayHasKey('davies_bouldin', $result['metrics']);
    }

    public function test_strict_train_cf_evaluation_eliminates_potential_data_leakage(): void
    {
        $cf = app(CollaborativeFilteringService::class);
        $result = $cf->evaluateModelStrictTrain(0.20, 5, 4.0, $this->version, 2);

        $this->assertEquals('strict_train_only', $result['protocol']);
        $this->assertArrayHasKey('mae', $result);
        $this->assertArrayHasKey('rmse', $result);
        $this->assertArrayHasKey('precision_at_k', $result);
        $this->assertArrayHasKey('recall_at_k', $result);
    }

    public function test_ablation_study_returns_four_distinct_configurations(): void
    {
        $cf = app(CollaborativeFilteringService::class);
        $ablation = $cf->runAblationStudy(0.20, 5, 4.0, $this->version);

        $this->assertArrayHasKey('cf_only', $ablation);
        $this->assertArrayHasKey('kmeans_user_cf', $ablation);
        $this->assertArrayHasKey('kmeans_dest_cf', $ablation);
        $this->assertArrayHasKey('kmeans_cf_filtered', $ablation);

        foreach ($ablation as $config) {
            $this->assertArrayHasKey('mae', $config);
            $this->assertArrayHasKey('rmse', $config);
            $this->assertArrayHasKey('precision_at_k', $config);
            $this->assertArrayHasKey('recall_at_k', $config);
        }
    }

    public function test_admin_can_view_both_destination_and_user_clustering_modes(): void
    {
        $responseDest = $this->actingAs($this->admin)->get(route('admin.clustering.index', ['mode' => 'destinations']));
        $responseDest->assertStatus(200);
        $responseDest->assertSee('Klasterisasi Destinasi');

        $responseUser = $this->actingAs($this->admin)->get(route('admin.clustering.index', ['mode' => 'users']));
        $responseUser->assertStatus(200);
        $responseUser->assertSee('Klasterisasi Pengguna');
    }

    public function test_admin_can_run_destination_clustering_via_post(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.clustering.run'), [
            'mode' => 'destinations',
            'k' => 4,
            'iterations' => 40,
            'seed' => 42,
        ]);

        $response->assertRedirect(route('admin.clustering.index', ['mode' => 'destinations']));
        $response->assertSessionHas('success');
    }

    public function test_destination_clustering_identifies_degenerate_clusters_and_exploratory_status(): void
    {
        $kMeans = app(KMeansService::class);
        $result = $kMeans->clusterDestinations(4, 50, 42, false, $this->version);

        $this->assertFalse($result['is_primary_research_method']);
        $this->assertEquals('exploratory_destination_analysis', $result['module_type']);
        $this->assertArrayHasKey('has_degenerate_cluster', $result);
        $this->assertArrayHasKey('degenerate_warning', $result);
    }

    public function test_user_clustering_evaluates_multi_seed_stability(): void
    {
        $kMeans = app(KMeansService::class);
        $stability = $kMeans->evaluateClusterStability(2, [42, 43, 44], 'strict_behavior_3', $this->version);

        $this->assertArrayHasKey('mean_silhouette', $stability);
        $this->assertArrayHasKey('std_silhouette', $stability);
        $this->assertArrayHasKey('is_stable', $stability);
        $this->assertCount(3, $stability['metrics_per_seed']);
    }
}
