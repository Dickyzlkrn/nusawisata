<?php

namespace Tests\Feature;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomatedMlRunTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected DatasetVersion $datasetV1;

    protected Province $province;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@nusawisata.test',
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Wisatawan Test',
            'email' => 'user@nusawisata.test',
            'role' => 'user',
        ]);

        $this->province = Province::create([
            'name' => 'Bali',
            'slug' => 'bali',
            'description' => 'Destinasi wisata unggulan',
        ]);

        $this->datasetV1 = DatasetVersion::create([
            'name' => 'Dataset v1 Riset',
            'original_filename' => 'dataset_v1.xlsx',
            'file_path' => 'datasets/dataset_v1.xlsx',
            'version' => 'v1',
            'status' => 'active',
            'users_count' => 10,
            'destinations_count' => 5,
            'ratings_count' => 25,
            'provinces_count' => 1,
            'uploaded_at' => now(),
            'activated_at' => now(),
        ]);

        // Seed 5 destinations
        $destinations = [];
        for ($i = 1; $i <= 5; $i++) {
            $destinations[$i] = Destination::create([
                'dataset_version_id' => $this->datasetV1->id,
                'name' => "Destinasi Bali #{$i}",
                'slug' => "destinasi-bali-{$i}",
                'province_id' => $this->province->id,
                'category' => 'Wisata Bahari',
                'description' => 'Pemandangan alam indah.',
                'price' => 20000 * $i,
                'latitude' => -8.4095 + ($i * 0.05),
                'longitude' => 115.1889 + ($i * 0.05),
                'google_rating' => 4.5,
                'review_count' => 100,
            ]);
        }

        // Seed 6 rated users
        $users = User::factory()->count(6)->create([
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        // Seed interactions with varied ratings
        foreach ($users as $idx => $u) {
            for ($d = 1; $d <= 4; $d++) {
                Rating::create([
                    'dataset_version_id' => $this->datasetV1->id,
                    'user_id' => $u->id,
                    'destination_id' => $destinations[$d]->id,
                    'rating' => (($idx + $d) % 4) + 2, // ratings between 2 and 5
                    'comment' => 'Pengalaman liburan yang memuaskan.',
                ]);
            }
        }
    }

    /**
     * 1. Test admin-only authorization for ML run routes.
     */
    public function test_admin_only_access(): void
    {
        // Guest is redirected to login
        $this->get(route('admin.ml_runs.index'))->assertRedirect(route('login'));

        // Regular user receives 403 forbidden
        $this->actingAs($this->regularUser)->get(route('admin.ml_runs.index'))->assertForbidden();

        // Admin can access successfully
        $this->actingAs($this->admin)->get(route('admin.ml_runs.index'))->assertOk();
    }

    /**
     * 2. Test dataset selection and summary inspection endpoint.
     */
    public function test_dataset_selection_and_summary_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.ml_runs.dataset_summary', $this->datasetV1));

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'version',
                'status',
                'inspection' => [
                    'total_rows',
                    'users_count',
                    'destinations_count',
                    'ratings_count',
                    'missing_values',
                    'duplicate_rows',
                    'invalid_ratings',
                    'is_eligible',
                    'reasons',
                ],
            ]);

        $this->assertTrue($response->json('inspection.is_eligible'));
    }

    /**
     * 3. Test invalid/empty dataset is rejected from automated ML run.
     */
    public function test_invalid_dataset_is_rejected_from_ml_run(): void
    {
        $emptyDataset = DatasetVersion::create([
            'name' => 'Empty Dataset Corrupt',
            'original_filename' => 'empty.xlsx',
            'version' => 'v99',
            'status' => 'uploaded',
            'users_count' => 0,
            'destinations_count' => 0,
            'ratings_count' => 0,
            'provinces_count' => 0,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.ml_runs.run'), [
            'dataset_version_id' => $emptyDataset->id,
        ]);

        $response->assertRedirect(route('admin.ml_runs.index', ['dataset_id' => $emptyDataset->id]));
        $response->assertSessionHas('error');

        $latestFailedRun = MlRun::where('dataset_version_id', $emptyDataset->id)->latest()->first();
        $this->assertNotNull($latestFailedRun);
        $this->assertEquals('failed', $latestFailedRun->status);
        $this->assertStringContainsString('Dataset belum memenuhi syarat', $latestFailedRun->error_message);
    }

    /**
     * 4. Test automated ML pipeline execution creates real run and saves metrics.
     */
    public function test_automated_ml_run_creates_new_run_and_executes_pipeline(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ml_runs.run'), [
            'dataset_version_id' => $this->datasetV1->id,
            'min_k' => 2,
            'max_k' => 3,
            'seed' => 42,
            'neighbors' => 5,
            'train_test_ratio' => 0.20,
        ]);

        $latestRun = MlRun::where('dataset_version_id', $this->datasetV1->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $this->assertNotNull($latestRun);
        $response->assertRedirect(route('admin.ml_runs.show', $latestRun));

        // Check metrics persisted without mock/hardcoding
        $this->assertNotNull($latestRun->k);
        $this->assertGreaterThanOrEqual(2, $latestRun->k);
        $this->assertNotNull($latestRun->inertia);
        $this->assertNotNull($latestRun->silhouette_score);
        $this->assertNotNull($latestRun->davies_bouldin_score);
        $this->assertNotNull($latestRun->mae);
        $this->assertNotNull($latestRun->rmse);
        $this->assertNotNull($latestRun->completed_at);
        $this->assertEquals(100, $latestRun->progress);
        $this->assertEquals('completed', $latestRun->status);
    }

    /**
     * 5. Test dataset isolation: ML runs are bound to specific dataset versions.
     */
    public function test_ml_run_isolation_by_dataset_version(): void
    {
        $datasetV2 = DatasetVersion::create([
            'name' => 'Dataset v2 Isolasi',
            'original_filename' => 'dataset_v2.xlsx',
            'version' => 'v2',
            'status' => 'uploaded',
            'users_count' => 10,
            'destinations_count' => 5,
            'ratings_count' => 25,
            'provinces_count' => 1,
        ]);

        $run1 = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'silhouette_score' => 0.45,
            'is_active' => true,
        ]);

        $run2 = MlRun::create([
            'dataset_version_id' => $datasetV2->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 4,
            'silhouette_score' => 0.55,
            'is_active' => false,
        ]);

        $this->assertEquals($this->datasetV1->id, $run1->dataset_version_id);
        $this->assertEquals($datasetV2->id, $run2->dataset_version_id);
        $this->assertNotEquals($run1->dataset_version_id, $run2->dataset_version_id);
    }

    /**
     * 6. Test activating a run deactivates the previous run (Single Active Run rule).
     */
    public function test_activate_run_deactivates_previous_run(): void
    {
        $run1 = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => true,
        ]);

        $run2 = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 4,
            'is_active' => false,
        ]);

        $this->assertTrue($run1->fresh()->isActive());
        $this->assertFalse($run2->fresh()->isActive());

        // Activate run2
        $response = $this->actingAs($this->admin)->post(route('admin.ml_runs.activate', $run2));
        $response->assertSessionHas('success');

        $this->assertFalse($run1->fresh()->isActive());
        $this->assertTrue($run2->fresh()->isActive());
        $this->assertEquals(1, MlRun::where('is_active', true)->count());
    }

    /**
     * 7. Test rollback: admin can reactivate an older ML run.
     */
    public function test_admin_can_rollback_to_previous_ml_run(): void
    {
        $run1 = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => false,
        ]);

        $run2 = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 4,
            'is_active' => true,
        ]);

        // Roll back to run1
        $this->actingAs($this->admin)->post(route('admin.ml_runs.activate', $run1));

        $this->assertTrue($run1->fresh()->isActive());
        $this->assertFalse($run2->fresh()->isActive());
    }

    /**
     * 8. Test failed run does not replace active run.
     */
    public function test_failed_run_does_not_replace_active_run(): void
    {
        $activeRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => true,
        ]);

        // Simulate failed run
        $failedRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'failed',
            'error_message' => 'Simulated error during clustering',
            'is_active' => false,
        ]);

        // Active run remains untouched
        $this->assertTrue($activeRun->fresh()->isActive());
        $this->assertFalse($failedRun->fresh()->isActive());
        $this->assertEquals($activeRun->id, MlRun::getActive()->id);
    }

    /**
     * 9. Test delete protection: cannot delete active ML run.
     */
    public function test_cannot_delete_active_ml_run(): void
    {
        $activeRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ml_runs.destroy', $activeRun));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('ml_runs', ['id' => $activeRun->id]);
    }

    /**
     * 10. Test delete protection: cannot delete dataset referenced by active ML run.
     */
    public function test_cannot_delete_dataset_referenced_by_active_ml_run(): void
    {
        $activeRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.dataset.destroy', $this->datasetV1));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('dataset_versions', ['id' => $this->datasetV1->id]);
    }

    /**
     * 11. Test recommendation engine automatically uses active ML run.
     */
    public function test_recommendation_service_uses_active_ml_run(): void
    {
        $activeRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 4,
            'is_active' => true,
            'summary' => [
                'user_assignments' => [
                    $this->regularUser->id => ['user_id' => $this->regularUser->id, 'cluster_id' => 2, 'distance' => 0.5],
                ],
            ],
        ]);

        /** @var RecommendationService $service */
        $service = app(RecommendationService::class);
        $run = $service->generateInteractiveRun($this->regularUser, 6);

        $this->assertEquals($activeRun->id, $run->ml_run_id);
        $this->assertEquals(2, $run->cluster_id);
        $this->assertEquals('completed', $run->status);
    }

    /**
     * 12. Test recommendation traceability end-to-end.
     */
    public function test_recommendation_traceability(): void
    {
        $activeRun = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => true,
        ]);

        /** @var RecommendationService $service */
        $service = app(RecommendationService::class);
        $recRun = $service->generateInteractiveRun($this->regularUser, 6);

        $this->assertNotNull($recRun->user_id);
        $this->assertEquals($activeRun->id, $recRun->ml_run_id);
        $this->assertEquals($this->datasetV1->id, $recRun->dataset_version_id);
        $this->assertNotNull($recRun->cluster_id);
        $this->assertIsArray($recRun->result_payload);
        $this->assertArrayHasKey('algorithm', $recRun->result_payload);
        $this->assertArrayHasKey('recommendations', $recRun->result_payload);
    }

    /**
     * 13. Test ML run show view renders visual elements.
     */
    public function test_admin_can_view_ml_run_detail_page(): void
    {
        $run = MlRun::create([
            'dataset_version_id' => $this->datasetV1->id,
            'algorithm' => 'K-Means + User-Based CF',
            'status' => 'completed',
            'k' => 3,
            'is_active' => false,
            'silhouette_score' => 0.5234,
            'davies_bouldin_score' => 1.2345,
            'mae' => 0.85,
            'rmse' => 1.12,
            'summary' => [
                'cluster_distribution' => [1 => 3, 2 => 2, 3 => 1],
                'pca_data' => [
                    ['user_id' => 1, 'name' => 'User 1', 'cluster_id' => 1, 'x' => 0.5, 'y' => -0.2],
                ],
                'k_candidates' => [
                    2 => ['k' => 2, 'silhouette' => 0.45, 'davies_bouldin' => 1.4],
                    3 => ['k' => 3, 'silhouette' => 0.52, 'davies_bouldin' => 1.2],
                ],
                'cf_stats' => [
                    'matrix_size' => 30,
                    'total_users' => 6,
                    'total_destinations' => 5,
                    'sparsity_percent' => 20.0,
                    'density_percent' => 80.0,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ml_runs.show', $run));

        $response->assertOk();
        $response->assertSee("ML Run #{$run->id}");
        $response->assertSee('0.5234');
        $response->assertSee('clusterDistChart');
        $response->assertSee('pcaScatterChart');
    }
}
