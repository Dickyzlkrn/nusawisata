<?php

namespace Tests\Feature;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\MlRun;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\DatasetImportService;
use App\Services\KMeansService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected DatasetVersion $v1;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::create([
            'name' => 'Admin NusaWisata',
            'email' => 'admin@nusawisata.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->regularUser = User::create([
            'name' => 'Wisatawan Biasa',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $province = Province::create(['name' => 'Bali', 'slug' => 'bali']);

        // Create baseline v1 dataset
        $this->v1 = DatasetVersion::create([
            'name' => 'Dataset Riset Baseline v1',
            'original_filename' => 'dataset_v1.xlsx',
            'version' => 'v1',
            'status' => 'active',
            'users_count' => 10,
            'destinations_count' => 5,
            'ratings_count' => 20,
            'provinces_count' => 1,
            'activated_at' => now(),
        ]);

        // Destinations for v1
        for ($i = 1; $i <= 5; $i++) {
            Destination::create([
                'dataset_version_id' => $this->v1->id,
                'place_id' => $i,
                'province_id' => $province->id,
                'name' => "Destinasi v1 - {$i}",
                'slug' => "destinasi-v1-{$i}",
                'category' => 'Alam',
                'price' => 15000,
                'google_rating' => 4.5,
                'review_count' => 50,
            ]);
        }
    }

    public function test_non_admin_cannot_access_dataset_management(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.dataset.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_dataset_page_with_active_version(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dataset.index'));

        $response->assertOk()
            ->assertSee('Dataset Riset Baseline v1')
            ->assertSee('DATASET AKTIF (v1)');
    }

    public function test_validation_rejects_missing_columns(): void
    {
        $service = app(DatasetImportService::class);

        // CSV without required column 'price'
        $invalidCsv = "user_id,place_id,place_name,category,province,destination_rating,place_ratings\n1,1,Pantai Kuta,Bahari,Bali,4.5,5\n";
        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_missing_'.uniqid().'.csv';
        file_put_contents($tempFile, $invalidCsv);

        $result = $service->validateFile($tempFile);
        @unlink($tempFile);

        $this->assertFalse($result['valid']);
        $this->assertContains('price', $result['missing_columns']);
    }

    public function test_validation_rejects_invalid_ratings(): void
    {
        $service = app(DatasetImportService::class);

        // Rating value is 9 (exceeds max 5)
        $invalidRatingCsv = "user_id,place_id,place_name,category,province,price,destination_rating,place_ratings\n1,1,Pantai Kuta,Bahari,Bali,10000,4.5,9\n";
        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_invalid_rating_'.uniqid().'.csv';
        file_put_contents($tempFile, $invalidRatingCsv);

        $result = $service->validateFile($tempFile);
        @unlink($tempFile);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('tidak valid', $result['message']);
    }

    public function test_failed_dataset_upload_keeps_active_dataset_safe_and_intact(): void
    {
        $invalidContent = "invalid,header,columns\n1,2,3\n";
        $file = UploadedFile::fake()->createWithContent('invalid_dataset.csv', $invalidContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.dataset.upload'), [
                'name' => 'Dataset Rusak',
                'dataset_file' => $file,
            ]);

        $response->assertRedirect(route('admin.dataset.index'));

        // Verify active dataset is still v1 and status is active
        $active = DatasetVersion::getActive();
        $this->assertNotNull($active);
        $this->assertEquals('v1', $active->version);
        $this->assertEquals('active', $active->status);

        // Verify the failed version was recorded with failed status
        $failed = DatasetVersion::where('version', 'v2')->first();
        $this->assertNotNull($failed);
        $this->assertEquals('failed', $failed->status);
    }

    public function test_admin_can_upload_and_process_valid_csv_dataset(): void
    {
        $csvContent = "User_Id,Place_Id,Place_Name,Category,Province,Price,Destination_Rating,Place_Ratings\n"
            ."101,11,Danau Beratan,Alam,Bali,20000,4.8,5\n"
            ."101,12,Tirta Empul,Budaya,Bali,15000,4.6,4\n"
            ."102,11,Danau Beratan,Alam,Bali,20000,4.8,4\n"
            ."102,12,Tirta Empul,Budaya,Bali,15000,4.6,5\n"
            ."103,11,Danau Beratan,Alam,Bali,20000,4.8,5\n"
            ."103,12,Tirta Empul,Budaya,Bali,15000,4.6,4\n";

        $file = UploadedFile::fake()->createWithContent('dataset_batch2.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.dataset.upload'), [
                'name' => 'Dataset Wisata Batch 2',
                'dataset_file' => $file,
            ]);

        $response->assertRedirect(route('admin.dataset.index'));

        $v2 = DatasetVersion::where('version', 'v2')->first();
        $this->assertNotNull($v2);
        $this->assertEquals('ready', $v2->status);
        $this->assertGreaterThan(0, $v2->ratings_count);

        // Baseline v1 is still active until explicitly activated
        $this->assertEquals('v1', DatasetVersion::getActive()->version);
    }

    public function test_admin_can_activate_ready_dataset_and_archives_previous(): void
    {
        $v2 = DatasetVersion::create([
            'name' => 'Dataset Wisata v2',
            'original_filename' => 'v2.csv',
            'version' => 'v2',
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.dataset.activate', $v2));

        $response->assertRedirect(route('admin.dataset.index'));

        $this->v1->refresh();
        $v2->refresh();

        $this->assertEquals('archived', $this->v1->status);
        $this->assertEquals('active', $v2->status);
        $this->assertEquals($v2->id, DatasetVersion::getActive()->id);
    }

    public function test_admin_can_rollback_to_archived_dataset(): void
    {
        // Set v2 as active, v1 as archived
        $this->v1->update(['status' => 'archived']);
        $v2 = DatasetVersion::create([
            'name' => 'Dataset Wisata v2',
            'original_filename' => 'v2.csv',
            'version' => 'v2',
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Rollback to v1
        $response = $this->actingAs($this->admin)
            ->post(route('admin.dataset.rollback', $this->v1));

        $response->assertRedirect(route('admin.dataset.index'));

        $this->v1->refresh();
        $v2->refresh();

        $this->assertEquals('active', $this->v1->status);
        $this->assertEquals('archived', $v2->status);
        $this->assertEquals($this->v1->id, DatasetVersion::getActive()->id);
    }

    public function test_rebuilding_kmeans_links_ml_run_to_dataset_version(): void
    {
        $dest = Destination::where('dataset_version_id', $this->v1->id)->first();

        // Create 2 users with ratings
        for ($u = 1; $u <= 2; $u++) {
            $user = User::create([
                'name' => "Tester {$u}",
                'email' => "tester{$u}@test.com",
                'password' => bcrypt('password'),
                'role' => 'user',
            ]);
            Rating::create([
                'dataset_version_id' => $this->v1->id,
                'user_id' => $user->id,
                'destination_id' => $dest->id,
                'rating' => 4 + ($u % 2),
            ]);
        }

        $kMeansService = app(KMeansService::class);
        $kMeansService->rebuildForDataset($this->v1, 2);

        $mlRun = MlRun::where('algorithm', 'kmeans')
            ->where('dataset_version_id', $this->v1->id)
            ->latest()
            ->first();

        $this->assertNotNull($mlRun);
        $this->assertEquals($this->v1->id, $mlRun->dataset_version_id);
    }

    public function test_can_validate_and_import_destination_catalog_dataset_without_user_ratings(): void
    {
        $service = app(DatasetImportService::class);

        $destCatalogCsv = "Place_Id,Place_Name,Province,Category,Price,Destination_Rating,Latitude,Longitude\n".
            "101,Pantai Kelingking,Bali,Bahari,25000,4.8,-8.750849,115.474621\n".
            "102,Tirta Empul,Bali,Budaya,50000,4.6,-8.414983,115.315053\n";

        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_catalog_'.uniqid().'.csv';
        file_put_contents($tempFile, $destCatalogCsv);

        $validation = $service->validateFile($tempFile);
        $this->assertTrue($validation['valid']);
        $this->assertEquals('destination', $validation['type']);

        $version = DatasetVersion::create([
            'name' => 'Katalog Destinasi Baru',
            'original_filename' => 'katalog.csv',
            'file_path' => $tempFile,
            'version' => 'v5',
            'status' => 'uploaded',
        ]);

        $importResult = $service->importVersion($version, $tempFile);
        @unlink($tempFile);

        $this->assertTrue($importResult['success']);
        $version->refresh();
        $this->assertEquals('ready', $version->status);
        $this->assertGreaterThanOrEqual(2, $version->destinations_count);

        $dest = Destination::where('name', 'Pantai Kelingking')->first();
        $this->assertNotNull($dest);
        $this->assertEquals(-8.750849, $dest->latitude);
        $this->assertEquals(115.474621, $dest->longitude);
    }

    public function test_can_handle_and_strip_utf8_bom_in_csv_dataset(): void
    {
        $service = app(DatasetImportService::class);

        // CSV with UTF-8 BOM at start
        $bomCsv = chr(239).chr(187).chr(191)."Place_Id,Place_Name,Province,Category,Price,Destination_Rating\n".
            "201,Danau Beratan,Bali,Alam,30000,4.7\n";

        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_bom_cat_'.uniqid().'.csv';
        file_put_contents($tempFile, $bomCsv);

        $validation = $service->validateFile($tempFile);
        @unlink($tempFile);

        $this->assertTrue($validation['valid']);
        $this->assertEquals('destination', $validation['type']);
    }

    public function test_admin_can_reprocess_failed_dataset_version(): void
    {
        $destCsv = "Place_Id,Place_Name,Province,Category,Price,Destination_Rating\n".
            "301,Pantai Sanur,Bali,Bahari,10000,4.6\n";

        $tempFile = storage_path('app/private/test_reprocess_'.uniqid().'.csv');
        file_put_contents($tempFile, $destCsv);

        $version = DatasetVersion::create([
            'name' => 'Dataset Gagal Awal',
            'original_filename' => 'reprocess_test.csv',
            'file_path' => $tempFile,
            'version' => 'v6',
            'status' => 'failed',
            'error_message' => 'Dummy error',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.dataset.reprocess', $version));

        @unlink($tempFile);

        $response->assertRedirect(route('admin.dataset.index'));
        $version->refresh();
        $this->assertEquals('ready', $version->status);
        $this->assertNull($version->error_message);
    }
}
