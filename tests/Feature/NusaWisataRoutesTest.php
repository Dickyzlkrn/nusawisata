<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NusaWisataRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homepage_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('NusaWisata');
        $response->assertSee('Collaborative Filtering');
    }

    public function test_homepage_renders_active_dataset_map(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Peta Sebaran Destinasi Wisata Indonesia');
        $response->assertSee('id="publicHomeMap"', false);
        $response->assertSee('Fokus Wilayah:');
        $response->assertViewHas('mapDestinations');
        $response->assertViewHas('mapProvinces');
        $response->assertViewHas('mapCategories');
    }

    public function test_destinations_catalog_is_accessible(): void
    {
        $response = $this->get('/destinations');
        $response->assertStatus(200);
        $response->assertSee('Katalog Destinasi Wisata');
    }

    public function test_destination_detail_is_accessible(): void
    {
        $destination = Destination::first();
        $this->assertNotNull($destination);

        $response = $this->get('/destinations/'.$destination->slug);
        $response->assertStatus(200);
        $response->assertSee($destination->name);
    }

    public function test_provinces_catalog_is_accessible(): void
    {
        $response = $this->get('/provinces');
        $response->assertStatus(200);
        $response->assertSee('38 Provinsi');
    }

    public function test_province_detail_is_accessible(): void
    {
        $province = Province::where('slug', 'bali')->first() ?? Province::first();
        $this->assertNotNull($province);

        $response = $this->get('/provinces/'.$province->slug);
        $response->assertStatus(200);
        $response->assertSee($province->name);
    }

    public function test_recommendations_page_is_accessible(): void
    {
        $response = $this->get('/recommendations');
        $response->assertStatus(200);
        $response->assertSee('K-Means Clustering');
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk');
    }

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Halo, '.$user->name);
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Akun');
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Admin Panel');

        $responseDest = $this->actingAs($admin)->get('/admin/destinations');
        $responseDest->assertStatus(200);

        $responseProv = $this->actingAs($admin)->get('/admin/provinces');
        $responseProv->assertStatus(200);

        $responseUsers = $this->actingAs($admin)->get('/admin/users');
        $responseUsers->assertStatus(200);

        $responseRatings = $this->actingAs($admin)->get('/admin/ratings');
        $responseRatings->assertStatus(200);

        $responseDataset = $this->actingAs($admin)->get('/admin/dataset');
        $responseDataset->assertStatus(200);

        $responseClustering = $this->actingAs($admin)->get('/admin/clustering');
        $responseClustering->assertStatus(200);
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_user_can_register_and_is_redirected_to_login_with_flash_message(): void
    {
        $uniqueEmail = 'test_reg_'.time().'@example.com';
        $response = $this->post('/register', [
            'name' => 'Peserta Uji',
            'email' => $uniqueEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => $uniqueEmail]);
    }

    public function test_authenticated_user_can_logout_and_is_redirected_to_home(): void
    {
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/');
        $response->assertSessionHas('success');
        $this->assertGuest();
    }
}
