<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_documentation_page_is_accessible(): void
    {
        $response = $this->get('/documentation');

        $response->assertStatus(200);
        $response->assertSee('Sistem Rekomendasi Wisata Indonesia Cerdas');
        $response->assertSee('Diagram Alur Kerja Rekomendasi End-to-End');
        $response->assertSee('K-Means Clustering');
        $response->assertSee('Collaborative Filtering');
        $response->assertSee('Diagram Alur Keputusan');
        $response->assertSee('Cold-Start Fallback Engine');
        $response->assertSee('Davies-Bouldin Index');
    }

    public function test_guest_is_redirected_from_admin_documentation(): void
    {
        $response = $this->get('/admin/documentation');

        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_documentation(): void
    {
        $regularUser = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regularUser)->get('/admin/documentation');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_documentation(): void
    {
        $adminUser = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($adminUser)->get('/admin/documentation');

        $response->assertStatus(200);
        $response->assertSee('Buku Panduan Teknis & Dokumentasi Riset NusaWisata', false);
        $response->assertSee('Diagram Arsitektur Relasi Basis Data (ERD)', false);
        $response->assertSee('Siklus Pelatihan Algoritma');
        $response->assertSee('Panduan Metrik Evaluasi K-Means');
    }
}
