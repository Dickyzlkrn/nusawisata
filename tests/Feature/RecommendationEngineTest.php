<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\CollaborativeFilteringService;
use App\Services\KMeansService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic users and destinations
        $province = Province::create(['name' => 'Bali', 'slug' => 'bali']);

        $dests = [];
        for ($i = 1; $i <= 8; $i++) {
            $dests[$i] = Destination::create([
                'province_id' => $province->id,
                'name' => "Destinasi {$i}",
                'slug' => "destinasi-{$i}",
                'category' => 'Alam',
                'price' => 25000,
                'google_rating' => 4.5,
                'review_count' => 100,
            ]);
        }

        // Create 6 test users with ratings
        for ($u = 1; $u <= 6; $u++) {
            $user = User::create([
                'name' => "User {$u}",
                'email' => "user{$u}@test.com",
                'password' => bcrypt('password'),
                'role' => 'user',
            ]);

            // Rate some destinations
            Rating::create(['user_id' => $user->id, 'destination_id' => $dests[1]->id, 'rating' => 5]);
            Rating::create(['user_id' => $user->id, 'destination_id' => $dests[2]->id, 'rating' => ($u % 2 === 0 ? 5 : 2)]);
            Rating::create(['user_id' => $user->id, 'destination_id' => $dests[3]->id, 'rating' => 4]);
            Rating::create(['user_id' => $user->id, 'destination_id' => $dests[4]->id, 'rating' => ($u % 2 === 0 ? 4 : 5)]);
        }
    }

    public function test_kmeans_service_clusters_users_and_persists_cluster_id(): void
    {
        $kMeansService = app(KMeansService::class);
        $result = $kMeansService->run(2, 30, 42, true);

        $this->assertEquals(2, $result['k']);
        $this->assertGreaterThan(0, $result['iterations']);
        $this->assertArrayHasKey('silhouette', $result['metrics']);
        $this->assertArrayHasKey('davies_bouldin', $result['metrics']);
        $this->assertArrayHasKey('calinski_harabasz', $result['metrics']);
        $this->assertArrayHasKey('inertia', $result['metrics']);

        // Verify users have cluster_id in database
        $clusteredCount = User::whereNotNull('cluster_id')->count();
        $this->assertEquals(6, $clusteredCount);
    }

    public function test_kmeans_evaluates_candidate_k_values(): void
    {
        $kMeansService = app(KMeansService::class);
        $candidates = $kMeansService->evaluateKCandidates(2, 3);

        $this->assertCount(2, $candidates);
        $this->assertArrayHasKey(2, $candidates);
        $this->assertArrayHasKey(3, $candidates);
        $this->assertArrayHasKey('inertia', $candidates[2]);
        $this->assertArrayHasKey('silhouette', $candidates[2]);
    }

    public function test_collaborative_filtering_generates_recommendations_excluding_rated_items(): void
    {
        $cfService = app(CollaborativeFilteringService::class);
        $targetUser = User::first();

        // Have other peers rate destinations 5 and 6 (which target user has NOT rated)
        $dest5 = Destination::where('slug', 'destinasi-5')->first();
        $dest6 = Destination::where('slug', 'destinasi-6')->first();
        $peer2 = User::where('id', '!=', $targetUser->id)->first();
        $peer3 = User::where('id', '!=', $targetUser->id)->skip(1)->first();

        Rating::create(['user_id' => $peer2->id, 'destination_id' => $dest5->id, 'rating' => 5]);
        Rating::create(['user_id' => $peer3->id, 'destination_id' => $dest6->id, 'rating' => 5]);

        $result = $cfService->recommendForUser($targetUser, 3, 3, false);

        $this->assertFalse($result['is_fallback']);
        $this->assertEquals('K-Means + User-Based Collaborative Filtering', $result['algorithm']);

        $recommended = $result['recommendations'];
        $this->assertNotEmpty($recommended);

        // Destination 1, 2, 3, 4 were rated by User 1, so they must NOT appear in recommendations
        $ratedIds = Rating::where('user_id', $targetUser->id)->pluck('destination_id')->all();
        foreach ($recommended as $rec) {
            $this->assertNotContains($rec->id, $ratedIds);
            $this->assertNotNull($rec->predicted_rating);
            $this->assertGreaterThanOrEqual(1.0, $rec->predicted_rating);
            $this->assertLessThanOrEqual(5.0, $rec->predicted_rating);
        }
    }

    public function test_cold_start_user_receives_fallback_with_honest_label(): void
    {
        $cfService = app(CollaborativeFilteringService::class);

        // Brand new user without ratings
        $newUser = User::create([
            'name' => 'Cold Start User',
            'email' => 'coldstart@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $result = $cfService->recommendForUser($newUser, 3);

        $this->assertTrue($result['is_fallback']);
        $this->assertEquals('Cold-Start Popularity Fallback', $result['algorithm']);
        $this->assertCount(3, $result['recommendations']);
        $this->assertTrue($result['recommendations']->first()->is_fallback);
    }

    public function test_recommendation_service_orchestrator(): void
    {
        $recService = app(RecommendationService::class);
        $user = User::first();

        $result = $recService->getRecommendationResult($user, 4);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('algorithm', $result);
        $this->assertArrayHasKey('is_fallback', $result);
        $this->assertArrayHasKey('explanation', $result);

        // Guest user test
        $guestResult = $recService->getRecommendationResult(null, 4);
        $this->assertTrue($guestResult['is_fallback']);
        $this->assertNull($guestResult['user']);
    }

    public function test_admin_can_access_collaborative_filtering_panel(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/collaborative-filtering');
        $response->assertStatus(200);
        $response->assertSee('Collaborative Filtering');
        $response->assertSee('Tingkat Kerapatan');
    }

    public function test_user_cannot_access_collaborative_filtering_panel(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/admin/collaborative-filtering');
        $response->assertStatus(403);
    }

    public function test_rating_creation_and_update(): void
    {
        $user = User::first();
        $dest = Destination::whereNotIn('id', Rating::where('user_id', $user->id)->pluck('destination_id'))->first();

        // Create rating
        $response = $this->actingAs($user)->post('/ratings', [
            'destination_id' => $dest->id,
            'rating' => 5,
            'comment' => 'Keren sekali!',
        ]);
        $response->assertSessionHas('success');

        $rating = Rating::where('user_id', $user->id)->where('destination_id', $dest->id)->first();
        $this->assertNotNull($rating);
        $this->assertEquals(5, $rating->rating);

        // Update existing rating
        $updateResponse = $this->actingAs($user)->post('/ratings', [
            'destination_id' => $dest->id,
            'rating' => 3,
            'comment' => 'Biasa saja setelah kunjungan kedua.',
        ]);
        $updateResponse->assertSessionHas('success');

        $rating->refresh();
        $this->assertEquals(3, $rating->rating);
        $this->assertEquals('Biasa saja setelah kunjungan kedua.', $rating->comment);
    }
}
