<?php

namespace Tests\Feature;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\RecommendationRun;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractiveRecommendationProcessTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected User $coldUser;

    protected array $destinations;

    protected DatasetVersion $activeDataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeDataset = DatasetVersion::create([
            'name' => 'Dataset Test v1',
            'original_filename' => 'dataset_test.xlsx',
            'version' => 'v1',
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $province = Province::create(['name' => 'Bali', 'slug' => 'bali']);

        $this->destinations = [];
        for ($i = 1; $i <= 10; $i++) {
            $this->destinations[$i] = Destination::create([
                'dataset_version_id' => $this->activeDataset->id,
                'place_id' => $i,
                'province_id' => $province->id,
                'name' => "Destinasi Wisata {$i}",
                'slug' => "destinasi-wisata-{$i}",
                'category' => $i % 2 === 0 ? 'Budaya' : 'Bahari',
                'price' => 50000,
                'google_rating' => 4.0 + ($i * 0.05),
                'review_count' => 100 + $i,
            ]);
        }

        // User A
        $this->userA = User::create([
            'name' => 'Wisatawan A',
            'email' => 'user_a@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        // User B (Peer with similar taste)
        $this->userB = User::create([
            'name' => 'Wisatawan B',
            'email' => 'user_b@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        // Cold-Start User (No ratings)
        $this->coldUser = User::create([
            'name' => 'Wisatawan Baru',
            'email' => 'cold_user@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'cluster_id' => null,
        ]);

        // Ratings for User A
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userA->id,
            'destination_id' => $this->destinations[1]->id,
            'rating' => 5,
        ]);
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userA->id,
            'destination_id' => $this->destinations[2]->id,
            'rating' => 5,
        ]);
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userA->id,
            'destination_id' => $this->destinations[3]->id,
            'rating' => 4,
        ]);

        // Ratings for User B
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userB->id,
            'destination_id' => $this->destinations[1]->id,
            'rating' => 5,
        ]);
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userB->id,
            'destination_id' => $this->destinations[2]->id,
            'rating' => 5,
        ]);
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userB->id,
            'destination_id' => $this->destinations[4]->id,
            'rating' => 5,
        ]);
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->userB->id,
            'destination_id' => $this->destinations[5]->id,
            'rating' => 5,
        ]);
    }

    public function test_authenticated_user_can_generate_recommendation_and_track_run(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson(route('recommendations.generate'), ['limit' => 5]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'progress' => 100,
                'is_cold_start' => false,
            ]);

        $runId = $response->json('run_id');
        $this->assertNotNull($runId);

        $run = RecommendationRun::find($runId);
        $this->assertNotNull($run);
        $this->assertEquals($this->userA->id, $run->user_id);
        $this->assertEquals(100, $run->progress);
        $this->assertNotEmpty($run->stages_log);
    }

    public function test_recommendation_excludes_already_rated_destinations(): void
    {
        $service = app(RecommendationService::class);
        $run = $service->generateInteractiveRun($this->userA, 5);

        $this->assertEquals('completed', $run->status);
        $this->assertFalse($run->is_cold_start);

        $payload = $run->result_payload;
        $recommendedDestIds = array_column($payload['recommendations'], 'id');

        // User A already rated Dest 1, 2, 3 -> these must NOT be in recommendations
        $this->assertNotContains($this->destinations[1]->id, $recommendedDestIds);
        $this->assertNotContains($this->destinations[2]->id, $recommendedDestIds);
        $this->assertNotContains($this->destinations[3]->id, $recommendedDestIds);

        // Dest 4 or 5 rated high by peer User B should be recommended
        $this->assertTrue(
            in_array($this->destinations[4]->id, $recommendedDestIds) ||
            in_array($this->destinations[5]->id, $recommendedDestIds)
        );
    }

    public function test_cold_start_user_with_zero_ratings_handled_gracefully(): void
    {
        $response = $this->actingAs($this->coldUser)
            ->postJson(route('recommendations.generate'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'progress' => 100,
                'is_cold_start' => true,
            ]);

        $runId = $response->json('run_id');
        $run = RecommendationRun::find($runId);

        $this->assertTrue($run->is_cold_start);
        $this->assertStringContainsString('belum memberikan rating', $run->result_payload['explanation']);
        $this->assertNotEmpty($run->result_payload['recommendations']);
    }

    public function test_recommendation_status_and_result_endpoints_return_payload(): void
    {
        $service = app(RecommendationService::class);
        $run = $service->generateInteractiveRun($this->userA, 4);

        // Test status endpoint
        $statusResp = $this->actingAs($this->userA)
            ->getJson(route('recommendations.status', $run));

        $statusResp->assertOk()
            ->assertJson([
                'id' => $run->id,
                'status' => 'completed',
                'progress' => 100,
            ]);

        // Test result endpoint
        $resultResp = $this->actingAs($this->userA)
            ->getJson(route('recommendations.result', $run));

        $resultResp->assertOk()
            ->assertJsonStructure([
                'success',
                'run' => ['id', 'status', 'cluster_id'],
                'payload' => ['recommendations', 'explanation', 'algorithm'],
            ]);
    }

    public function test_cannot_access_other_users_recommendation_run(): void
    {
        $service = app(RecommendationService::class);
        $runA = $service->generateInteractiveRun($this->userA, 3);

        // User B attempts to view User A's run
        $response = $this->actingAs($this->userB)
            ->getJson(route('recommendations.result', $runA));

        $response->assertForbidden();
    }
}
