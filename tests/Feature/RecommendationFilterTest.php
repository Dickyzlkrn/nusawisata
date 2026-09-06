<?php

namespace Tests\Feature;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\DestinationFilterService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected DatasetVersion $activeDataset;

    protected Province $provinceLampung;

    protected Province $provinceBali;

    protected User $user;

    protected User $peer;

    protected array $destinations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeDataset = DatasetVersion::create([
            'name' => 'Dataset Filter Test',
            'original_filename' => 'dataset.xlsx',
            'version' => 'v1',
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $this->provinceLampung = Province::create(['name' => 'Lampung', 'slug' => 'lampung']);
        $this->provinceBali = Province::create(['name' => 'Bali', 'slug' => 'bali']);

        // 1. Lampung - Alam - Rp 20.000 - Rating 4.6
        $this->destinations['lampung_alam'] = Destination::create([
            'dataset_version_id' => $this->activeDataset->id,
            'place_id' => 101,
            'province_id' => $this->provinceLampung->id,
            'name' => 'Taman Nasional Way Kambas',
            'slug' => 'taman-nasional-way-kambas',
            'category' => 'Alam',
            'price' => 20000,
            'google_rating' => 4.6,
            'review_count' => 1200,
        ]);

        // 2. Lampung - Bahari - Rp 35.000 - Rating 4.4
        $this->destinations['lampung_bahari'] = Destination::create([
            'dataset_version_id' => $this->activeDataset->id,
            'place_id' => 102,
            'province_id' => $this->provinceLampung->id,
            'name' => 'Pantai Gigi Hiu',
            'slug' => 'pantai-gigi-hiu',
            'category' => 'Bahari',
            'price' => 35000,
            'google_rating' => 4.4,
            'review_count' => 850,
        ]);

        // 3. Lampung - Budaya - Rp 0 (Gratis) - Rating 4.2
        $this->destinations['lampung_budaya'] = Destination::create([
            'dataset_version_id' => $this->activeDataset->id,
            'place_id' => 103,
            'province_id' => $this->provinceLampung->id,
            'name' => 'Menara Siger',
            'slug' => 'menara-siger',
            'category' => 'Budaya',
            'price' => 0,
            'google_rating' => 4.2,
            'review_count' => 500,
        ]);

        // 4. Bali - Alam / Wisata Alam - Rp 75.000 - Rating 4.8
        $this->destinations['bali_alam'] = Destination::create([
            'dataset_version_id' => $this->activeDataset->id,
            'place_id' => 104,
            'province_id' => $this->provinceBali->id,
            'name' => 'Gunung Batur Kintamani',
            'slug' => 'gunung-batur-kintamani',
            'category' => 'Wisata Alam',
            'price' => 75000,
            'google_rating' => 4.8,
            'review_count' => 3000,
        ]);

        // 5. Bali - Budaya - Rp 150.000 - Rating 4.7
        $this->destinations['bali_budaya'] = Destination::create([
            'dataset_version_id' => $this->activeDataset->id,
            'place_id' => 105,
            'province_id' => $this->provinceBali->id,
            'name' => 'Pura Besakih',
            'slug' => 'pura-besakih',
            'category' => 'Budaya',
            'price' => 150000,
            'google_rating' => 4.7,
            'review_count' => 2500,
        ]);

        // User setup
        $this->user = User::create([
            'name' => 'User Traveler',
            'email' => 'traveler@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        $this->peer = User::create([
            'name' => 'Peer Traveler',
            'email' => 'peer@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'cluster_id' => 1,
        ]);

        // User and Peer ratings
        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->user->id,
            'destination_id' => $this->destinations['lampung_budaya']->id,
            'rating' => 5.0,
        ]);

        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->peer->id,
            'destination_id' => $this->destinations['lampung_budaya']->id,
            'rating' => 5.0,
        ]);

        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->peer->id,
            'destination_id' => $this->destinations['lampung_alam']->id,
            'rating' => 5.0,
        ]);

        Rating::create([
            'dataset_version_id' => $this->activeDataset->id,
            'user_id' => $this->peer->id,
            'destination_id' => $this->destinations['bali_alam']->id,
            'rating' => 4.5,
        ]);
    }

    public function test_filter_options_service_returns_dynamic_dataset_options(): void
    {
        $filterService = app(DestinationFilterService::class);
        $options = $filterService->getFilterOptions();

        $this->assertArrayHasKey('provinces', $options);
        $this->assertArrayHasKey('categories', $options);
        $this->assertArrayHasKey('budgets', $options);
        $this->assertArrayHasKey('ratings', $options);

        $provinceNames = array_column($options['provinces'], 'name');
        $this->assertContains('Lampung', $provinceNames);
        $this->assertContains('Bali', $provinceNames);
    }

    public function test_recommendations_index_page_displays_filter_card_and_options(): void
    {
        $response = $this->actingAs($this->user)->get(route('recommendations.index'));

        $response->assertStatus(200);
        $response->assertSee('Preferensi Pencarian Wisata');
        $response->assertSee('Daerah / Provinsi');
        $response->assertSee('Jenis Wisata');
        $response->assertSee('Rentang Budget');
        $response->assertSee('Minimal Rating');
        $response->assertSee('Lampung');
        $response->assertSee('Bali');
    }

    public function test_filter_by_province_constrains_recommendations_to_selected_province(): void
    {
        $response = $this->actingAs($this->user)->get(route('recommendations.index', [
            'province' => $this->provinceLampung->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Taman Nasional Way Kambas');
        $response->assertSee('Lampung');

        $recs = $response->viewData('recommendations');
        $this->assertNotEmpty($recs);
        foreach ($recs as $r) {
            $this->assertEquals($this->provinceLampung->id, $r->province_id);
        }
    }

    public function test_filter_by_category_matches_synonyms(): void
    {
        // Selecting "Alam" should match "Wisata Alam" (Gunung Batur) and "Alam" (Way Kambas)
        $filterService = app(DestinationFilterService::class);
        $candidateIds = $filterService->getCandidateDestinationIds(['category' => 'Alam']);

        $this->assertContains($this->destinations['lampung_alam']->id, $candidateIds);
        $this->assertContains($this->destinations['bali_alam']->id, $candidateIds);
        $this->assertNotContains($this->destinations['lampung_budaya']->id, $candidateIds);
    }

    public function test_filter_by_budget_free(): void
    {
        $filterService = app(DestinationFilterService::class);
        $candidateIds = $filterService->getCandidateDestinationIds(['budget' => 'free']);

        $this->assertContains($this->destinations['lampung_budaya']->id, $candidateIds);
        $this->assertNotContains($this->destinations['lampung_alam']->id, $candidateIds);
        $this->assertNotContains($this->destinations['bali_budaya']->id, $candidateIds);
    }

    public function test_filter_by_minimum_rating(): void
    {
        $filterService = app(DestinationFilterService::class);
        // Min rating 4.5
        $candidateIds = $filterService->getCandidateDestinationIds(['min_rating' => '4.5']);

        $this->assertContains($this->destinations['lampung_alam']->id, $candidateIds);
        $this->assertContains($this->destinations['bali_alam']->id, $candidateIds);
        $this->assertContains($this->destinations['bali_budaya']->id, $candidateIds);
        $this->assertNotContains($this->destinations['lampung_bahari']->id, $candidateIds);
    }

    public function test_filter_by_keyword(): void
    {
        $response = $this->actingAs($this->user)->get(route('recommendations.index', [
            'keyword' => 'Way Kambas',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Taman Nasional Way Kambas');

        $recs = $response->viewData('recommendations');
        $this->assertTrue($recs->contains('id', $this->destinations['lampung_alam']->id));
        $this->assertFalse($recs->contains('id', $this->destinations['lampung_bahari']->id));
    }

    public function test_multi_criteria_filtering(): void
    {
        $recService = app(RecommendationService::class);
        $result = $recService->getRecommendationResult($this->user, 9, [
            'province' => $this->provinceLampung->id,
            'category' => 'Alam',
            'budget' => 'under_25k',
        ]);

        $recommendations = $result['recommendations'];
        $this->assertCount(1, $recommendations);
        $this->assertEquals('Taman Nasional Way Kambas', $recommendations->first()->name);
    }

    public function test_empty_candidate_filter_shows_friendly_empty_state(): void
    {
        // No destination with category 'Budaya' in Lampung has price > 100k
        $response = $this->actingAs($this->user)->get(route('recommendations.index', [
            'province' => $this->provinceLampung->id,
            'category' => 'Budaya',
            'budget' => 'above_100k',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Belum Ditemukan Destinasi yang Sesuai');
        $response->assertSee('Reset Semua Filter');
    }

    public function test_cold_start_fallback_is_constrained_to_filter_candidates(): void
    {
        $coldUser = User::create([
            'name' => 'Cold User',
            'email' => 'cold@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $recService = app(RecommendationService::class);
        $result = $recService->getRecommendationResult($coldUser, 9, [
            'province' => $this->provinceBali->id,
        ]);

        $recommendations = $result['recommendations'];
        $this->assertTrue($result['is_fallback']);
        $this->assertNotEmpty($recommendations);

        // All returned destinations MUST be from Bali, NOT Lampung!
        foreach ($recommendations as $dest) {
            $this->assertEquals($this->provinceBali->id, $dest->province_id);
        }
    }

    public function test_generate_endpoint_accepts_filters_and_returns_filtered_run(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('recommendations.generate'), [
            'limit' => 6,
            'province' => $this->provinceLampung->id,
            'category' => 'Alam',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'run_id',
            'status',
            'progress',
        ]);

        $runId = $response->json('run_id');

        $resultResponse = $this->actingAs($this->user)->getJson("/recommendations/{$runId}/result");
        $resultResponse->assertStatus(200);

        $payload = $resultResponse->json('payload');
        $this->assertNotEmpty($payload['recommendations']);
        $this->assertEquals('Taman Nasional Way Kambas', $payload['recommendations'][0]['name']);
    }
}
