<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $province = Province::create([
            'name' => 'Bali',
            'slug' => 'bali',
            'description' => 'Pulau Dewata',
        ]);

        $destination = Destination::create([
            'name' => 'Pantai Kuta',
            'slug' => 'pantai-kuta',
            'province_id' => $province->id,
            'category' => 'Wisata Bahari',
            'description' => 'Pantai pasir putih terkenal.',
            'price' => 15000,
            'latitude' => -8.7185,
            'longitude' => 115.1686,
            'google_rating' => 4.6,
            'review_count' => 120,
        ]);

        $users = User::factory()->count(5)->create([
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        foreach ($users as $u) {
            Rating::create([
                'user_id' => $u->id,
                'destination_id' => $destination->id,
                'rating' => 5,
                'comment' => 'Sangat indah pemandangannya!',
            ]);
        }
    }

    public function test_admin_dashboard_loads_analytics_and_map_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalDestinations');
        $response->assertViewHas('mapDestinations');
        $response->assertViewHas('clusterCounts');
        $response->assertViewHas('ratingDistribution');
        $response->assertViewHas('matrixStats');
        $response->assertSee('Peta Sebaran Destinasi Wisata Indonesia');
        $response->assertSee('Analisis K-Means Clustering');
        $response->assertSee('Distribusi Skor Rating Wisatawan');
    }

    public function test_admin_clustering_page_loads_pca_and_evaluation_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.clustering.index'));

        $response->assertStatus(200);
        $response->assertViewHas('lastResult');
        $response->assertViewHas('candidates');
        $response->assertViewHas('clusterDistribution');
        $response->assertSee('Proyeksi 2D PCA');
        $response->assertSee('Kurva Elbow');
    }

    public function test_admin_collaborative_filtering_page_loads_rating_distribution_and_simulator(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.collaborative_filtering.index'));

        $response->assertStatus(200);
        $response->assertViewHas('ratingDistribution');
        $response->assertViewHas('matrixStats');
        $response->assertSee('Distribusi Penilaian Wisatawan');
        $response->assertSee('Sparsity Matriks');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_all_admin_management_pages_load_successfully(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $routes = [
            'admin.destinations.index',
            'admin.provinces.index',
            'admin.ratings.index',
            'admin.users.index',
            'admin.dataset.index',
            'admin.documentation.index',
        ];

        foreach ($routes as $r) {
            $response = $this->actingAs($admin)->get(route($r));
            $response->assertStatus(200);
        }
    }
}
