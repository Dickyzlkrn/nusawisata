<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetVersion;
use App\Models\MlRun;
use App\Services\ActiveMlRunResolver;
use App\Services\AutomatedMlRunService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MlRunController extends Controller
{
    public function __construct(
        protected AutomatedMlRunService $automatedMlRunService,
        protected ActiveMlRunResolver $activeMlRunResolver
    ) {}

    /**
     * Display the Automated ML Run central hub, dataset selector, and run registry.
     */
    public function index(Request $request): View
    {
        $activeRun = $this->activeMlRunResolver->getActiveRun();
        $activeDataset = $this->activeMlRunResolver->getActiveDataset();

        // Datasets eligible for selection
        $datasetVersions = DatasetVersion::orderByDesc('id')->get();

        // Selected dataset for inspection (default to active dataset or first available)
        $selectedDatasetId = $request->query('dataset_id', $activeDataset?->id ?? $datasetVersions->first()?->id);
        $selectedDataset = $datasetVersions->firstWhere('id', (int) $selectedDatasetId) ?? $datasetVersions->first();

        // Pre-run inspection report for the selected dataset
        $inspectionReport = $selectedDataset ? $this->automatedMlRunService->inspectDataset($selectedDataset) : null;

        // Model registry history
        $mlRuns = MlRun::with('datasetVersion')
            ->orderByDesc('id')
            ->paginate(10);

        // Runs available for comparative matrix
        $completedRuns = MlRun::with('datasetVersion')
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('admin.ml_runs.index', compact(
            'activeRun',
            'activeDataset',
            'datasetVersions',
            'selectedDataset',
            'inspectionReport',
            'mlRuns',
            'completedRuns'
        ));
    }

    /**
     * JSON endpoint for dynamic dataset health inspection.
     */
    public function datasetSummary(DatasetVersion $version): JsonResponse
    {
        $inspection = $this->automatedMlRunService->inspectDataset($version);

        return response()->json([
            'id' => $version->id,
            'name' => $version->name,
            'version' => $version->version,
            'status' => $version->status,
            'is_active' => $version->isActive(),
            'uploaded_at' => $version->uploaded_at?->format('d M Y H:i') ?? $version->created_at->format('d M Y H:i'),
            'inspection' => $inspection,
        ]);
    }

    /**
     * Trigger an automated ML run pipeline for the chosen dataset.
     */
    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dataset_version_id' => 'required|exists:dataset_versions,id',
            'min_k' => 'nullable|integer|min:2|max:8',
            'max_k' => 'nullable|integer|min:2|max:8',
            'seed' => 'nullable|integer|min:0',
            'neighbors' => 'nullable|integer|min:1|max:50',
            'train_test_ratio' => 'nullable|numeric|min:0.1|max:0.5',
        ], [
            'dataset_version_id.required' => 'Pilih versi dataset yang akan diproses.',
            'dataset_version_id.exists' => 'Versi dataset yang dipilih tidak valid.',
        ]);

        $dataset = DatasetVersion::findOrFail($validated['dataset_version_id']);

        try {
            $mlRun = $this->automatedMlRunService->execute($dataset, $validated);

            return redirect()->route('admin.ml_runs.show', $mlRun)
                ->with('success', "Automated ML Run #{$mlRun->id} berhasil diselesaikan! Model siap dianalisis dan diaktifkan.");
        } catch (Exception $e) {
            return redirect()->route('admin.ml_runs.index', ['dataset_id' => $dataset->id])
                ->with('error', 'Gagal menjalankan Automated ML: '.$e->getMessage());
        }
    }

    /**
     * Display detailed research results and algorithm visualizations for an ML run.
     */
    public function show(MlRun $run): View
    {
        $run->load('datasetVersion');
        $activeRun = $this->activeMlRunResolver->getActiveRun();

        return view('admin.ml_runs.show', compact('run', 'activeRun'));
    }

    /**
     * Designate an ML run as the system's ACTIVE ML RUN.
     */
    public function activate(MlRun $run): RedirectResponse
    {
        if ($run->status !== 'completed') {
            return redirect()->back()->with('error', 'Hanya ML Run yang berstatus completed yang dapat diaktifkan.');
        }

        $run->activate();
        $this->activeMlRunResolver->clearCache();

        $datasetTag = $run->datasetVersion?->version ?? 'v1';

        return redirect()->back()
            ->with('success', "ML Run #{$run->id} (Dataset {$datasetTag}, K={$run->k}) berhasil ditetapkan sebagai Active ML Run! Seluruh rekomendasi pengguna kini menggunakan model ini.");
    }

    /**
     * Delete an ML run, protected against deleting the active model.
     */
    public function destroy(MlRun $run): RedirectResponse
    {
        if ($run->isActive()) {
            return redirect()->back()
                ->with('error', 'ML Run yang sedang aktif tidak dapat dihapus! Silakan aktifkan model lain terlebih dahulu sebelum menghapus model ini.');
        }

        $runId = $run->id;
        $run->delete();

        return redirect()->route('admin.ml_runs.index')
            ->with('success', "ML Run #{$runId} berhasil dihapus dari model registry.");
    }
}
