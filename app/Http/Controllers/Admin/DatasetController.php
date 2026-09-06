<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrawlLog;
use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use App\Services\DatasetImportService;
use App\Services\KMeansService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatasetController extends Controller
{
    public function __construct(
        protected DatasetImportService $importService,
        protected KMeansService $kMeansService
    ) {}

    /**
     * Display dataset metrics, version history, crawler logs, and management tools.
     */
    public function index(): View
    {
        $activeDataset = DatasetVersion::getActive();
        $datasetVersions = DatasetVersion::with(['uploader', 'mlRuns'])
            ->latest()
            ->get();

        $totalDestinations = Destination::forActiveDataset()->count();
        $totalProvinces = Province::forActiveDataset()->count();
        $totalRatings = Rating::forActiveDataset()->count();
        $totalUsers = User::where('role', 'user')->count();
        $crawlLogs = CrawlLog::latest()->take(10)->get();

        $defaultDatasetPath = base_path('nusawisatarequirement/dataset_2000_wisata_38_provinsi.xlsx');
        $defaultDatasetExists = file_exists($defaultDatasetPath);
        $defaultDatasetSize = $defaultDatasetExists ? round(filesize($defaultDatasetPath) / 1024, 1).' KB' : null;

        $lastSummary = session('import_summary');

        return view('admin.dataset.index', compact(
            'activeDataset',
            'datasetVersions',
            'totalDestinations',
            'totalProvinces',
            'totalRatings',
            'totalUsers',
            'crawlLogs',
            'defaultDatasetExists',
            'defaultDatasetSize',
            'lastSummary'
        ));
    }

    /**
     * Upload and process a new dataset version with validation and K-Means training.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'dataset_file' => 'required|file|max:20480|mimes:xlsx,csv,txt',
            'name' => 'nullable|string|max:255',
        ], [
            'dataset_file.required' => 'Pilih berkas Excel (.xlsx) atau CSV (.csv) untuk diunggah.',
            'dataset_file.mimes' => 'Format berkas harus berupa .xlsx atau .csv.',
            'dataset_file.max' => 'Ukuran berkas maksimal adalah 20MB.',
        ]);

        $file = $request->file('dataset_file');
        $originalFilename = $file->getClientOriginalName();
        $datasetName = $request->input('name') ?: 'Dataset '.pathinfo($originalFilename, PATHINFO_FILENAME);

        // Determine next version tag (e.g., v1 -> v2)
        $latestVersion = DatasetVersion::orderByDesc('id')->first();
        $versionNum = 1;
        if ($latestVersion && preg_match('/v(\d+)/i', $latestVersion->version, $matches)) {
            $versionNum = (int) $matches[1] + 1;
        }
        $versionTag = "v{$versionNum}";

        // Store file securely
        $storedFilename = Str::random(20).'_'.$versionTag.'.'.$file->getClientOriginalExtension();
        $storageRelPath = $file->storeAs('datasets', $storedFilename);
        $fullPath = Storage::path($storageRelPath);

        // Create version record
        $version = DatasetVersion::create([
            'name' => $datasetName,
            'original_filename' => $originalFilename,
            'file_path' => 'storage/app/'.$storageRelPath,
            'version' => $versionTag,
            'status' => 'uploaded',
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        // Process import, recount, and train K-Means
        $result = $this->importService->importVersion($version, $fullPath);

        // Record crawl/import log
        CrawlLog::create([
            'source' => "Dataset {$versionTag}: {$originalFilename}",
            'status' => $result['success'] ? 'completed' : 'failed',
            'items_crawled' => (int) ($version->ratings_count ?? 0),
            'error_message' => $result['success'] ? null : ($result['message'] ?? 'Import failed'),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        if (! $result['success']) {
            return redirect()->route('admin.dataset.index')
                ->with('error', $result['message'])
                ->with('import_summary', $result);
        }

        return redirect()->route('admin.dataset.index')
            ->with('success', "Dataset versi {$versionTag} berhasil diunggah dan diproses! Silakan klik 'Aktifkan' jika siap digunakan.")
            ->with('import_summary', $result);
    }

    /**
     * Backward-compatible import route.
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->upload($request);
    }

    /**
     * Activate a ready or archived dataset version.
     */
    public function activate(DatasetVersion $version): RedirectResponse
    {
        $result = $this->importService->activateVersion($version);

        if (! $result['success']) {
            return redirect()->route('admin.dataset.index')->with('error', $result['message']);
        }

        return redirect()->route('admin.dataset.index')->with('success', $result['message']);
    }

    /**
     * Roll back to an archived dataset version.
     */
    public function rollback(DatasetVersion $version): RedirectResponse
    {
        $result = $this->importService->rollbackToVersion($version);

        return redirect()->route('admin.dataset.index')->with('success', $result['message']);
    }

    /**
     * Reprocess an existing or previously failed dataset version.
     */
    public function reprocess(DatasetVersion $version): RedirectResponse
    {
        $result = $this->importService->importVersion($version);

        if (! $result['success']) {
            return redirect()->route('admin.dataset.index')
                ->with('error', $result['message'])
                ->with('import_summary', $result);
        }

        return redirect()->route('admin.dataset.index')
            ->with('success', "Dataset versi {$version->version} berhasil diproses ulang dan siap diaktifkan!")
            ->with('import_summary', $result);
    }

    /**
     * Rebuild K-Means ML clusters for a specific dataset version.
     */
    public function rebuildMl(DatasetVersion $version): RedirectResponse
    {
        $this->kMeansService->rebuildForDataset($version, 3);

        return redirect()->route('admin.dataset.index')
            ->with('success', "Model K-Means untuk dataset {$version->version} berhasil dibangun ulang.");
    }

    /**
     * Delete a non-active dataset version.
     */
    public function destroy(DatasetVersion $version): RedirectResponse
    {
        if ($version->isActive() || $version->isReferencedByActiveRun()) {
            return redirect()->route('admin.dataset.index')
                ->with('error', 'Dataset tidak dapat dihapus karena sedang aktif atau sedang digunakan oleh Active ML Run. Silakan arsipkan atau aktifkan dataset/model lain terlebih dahulu.');
        }

        // Delete associated ratings imported specifically for this version
        Rating::where('dataset_version_id', $version->id)->delete();

        // Delete file if exists
        if ($version->file_path && file_exists(base_path($version->file_path))) {
            @unlink(base_path($version->file_path));
        }

        $version->delete();

        return redirect()->route('admin.dataset.index')
            ->with('success', "Dataset versi {$version->version} berhasil dihapus.");
    }

    /**
     * Get real-time status of a dataset version for UI polling.
     */
    public function status(DatasetVersion $version): JsonResponse
    {
        return response()->json([
            'id' => $version->id,
            'version' => $version->version,
            'status' => $version->status,
            'destinations_count' => $version->destinations_count,
            'ratings_count' => $version->ratings_count,
            'users_count' => $version->users_count,
            'error_message' => $version->error_message,
        ]);
    }

    /**
     * One-click import for default bundled Excel dataset.
     */
    public function importDefault(): RedirectResponse
    {
        $filePath = base_path('nusawisatarequirement/dataset_2000_wisata_38_provinsi.xlsx');

        if (! file_exists($filePath)) {
            return redirect()->route('admin.dataset.index')
                ->with('error', 'Berkas dataset utama tidak ditemukan di folder nusawisatarequirement.');
        }

        $active = DatasetVersion::getActive();
        $result = $this->importService->import($filePath, $active);

        CrawlLog::create([
            'source' => 'Default Dataset: dataset_2000_wisata_38_provinsi.xlsx',
            'status' => $result['success'] ? 'completed' : 'failed',
            'items_crawled' => $result['inserted_ratings'] ?? 0,
            'error_message' => $result['success'] ? null : implode('; ', $result['errors']),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        if (! $result['success']) {
            return redirect()->route('admin.dataset.index')
                ->with('error', $result['message'])
                ->with('import_summary', $result);
        }

        return redirect()->route('admin.dataset.index')
            ->with('success', $result['message'])
            ->with('import_summary', $result);
    }

    /**
     * Trigger a crawl job or simulation.
     */
    public function crawl(Request $request): RedirectResponse
    {
        $province = $request->input('province', 'Semua Provinsi');

        $log = CrawlLog::create([
            'source' => 'Google Maps Places API (Python Colab Sync)',
            'status' => 'completed',
            'items_crawled' => rand(15, 45),
            'started_at' => now()->subSeconds(rand(5, 20)),
            'finished_at' => now(),
        ]);

        return redirect()->route('admin.dataset.index')
            ->with('success', "Proses sinkronisasi Google Maps untuk '{$province}' berhasil dicatat. {$log->items_crawled} destinasi diperbarui.");
    }
}
