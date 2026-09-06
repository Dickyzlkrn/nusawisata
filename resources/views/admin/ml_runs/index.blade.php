@extends('layouts.admin')

@section('title', 'Automated ML Run & Model Registry — NusaWisata')
@section('page-title', 'Automated ML Run')

@section('content')
<div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-robot" style="color: #0284c7;"></i> Automated ML Run & Model Registry
        </h1>
        <p style="color: var(--color-text-muted); font-size: 14px; margin-top: 4px;">
            Orkestrator pipeline machine learning terpusat, evaluasi multi-K, User-Based Collaborative Filtering, dan registry model aktif NusaWisata.
        </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('admin.dataset.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 8px 16px; font-size: 13px;">
            <i class="fa-solid fa-database" style="margin-right: 6px;"></i> Manajemen Dataset
        </a>
    </div>
</div>

<!-- SECTION 1: ACTIVE ML RUN SPOTLIGHT -->
@if($activeRun)
    <div class="detail-card" style="margin-bottom: 32px; background: #ffffff; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06); border-radius: var(--radius-lg); padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 800; padding: 5px 14px; border-radius: var(--radius-pill); font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-circle-check"></i> ACTIVE ML RUN (#{{ $activeRun->id }})
                    </span>
                    <span style="font-size: 13px; color: #64748b;">
                        Dataset: <strong>{{ $activeRun->datasetVersion?->version ?? 'v1' }}</strong> ({{ $activeRun->datasetVersion?->name ?? 'Dataset Utama' }})
                    </span>
                    <span style="font-size: 13px; color: #94a3b8;">•</span>
                    <span style="font-size: 13px; color: #64748b;">
                        Diperbarui: {{ $activeRun->completed_at?->format('d M Y H:i') ?? $activeRun->created_at->format('d M Y H:i') }}
                    </span>
                </div>
                <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">
                    Model Rekomendasi Produksi Aktif: K-Means (K={{ $activeRun->k ?? 3 }}) + User-Based CF
                </h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                    Seluruh sistem rekomendasi pengguna (Home, User Dashboard, & Search) secara otomatis menggunakan model ini sebagai single source of truth.
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="{{ route('admin.ml_runs.show', $activeRun) }}" class="btn-primary" style="font-size: 13px; padding: 9px 18px; border-radius: var(--radius-pill); display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-line"></i> Lihat Visualisasi & Hasil
                </a>
            </div>
        </div>

        <!-- Active Metrics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 14px; background: #f8fafc; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Optimal K</div>
                <div style="font-size: 22px; font-weight: 800; color: #0284c7; margin-top: 2px;">{{ $activeRun->k ?? 3 }}</div>
                <small style="color: #94a3b8; font-size: 11px;">Segmentasi</small>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Silhouette Score</div>
                <div style="font-size: 22px; font-weight: 800; color: #16a34a; margin-top: 2px;">{{ number_format($activeRun->silhouette_score ?? 0, 4) }}</div>
                <small style="color: #94a3b8; font-size: 11px;">Makin tinggi makin baik</small>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Davies-Bouldin (DBI)</div>
                <div style="font-size: 22px; font-weight: 800; color: #d97706; margin-top: 2px;">{{ number_format($activeRun->davies_bouldin_score ?? 0, 4) }}</div>
                <small style="color: #94a3b8; font-size: 11px;">Makin rendah makin baik</small>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">MAE (80:20 Split)</div>
                <div style="font-size: 22px; font-weight: 800; color: #6366f1; margin-top: 2px;">{{ number_format($activeRun->mae ?? 0, 4) }}</div>
                <small style="color: #94a3b8; font-size: 11px;">Mean Absolute Error</small>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">RMSE</div>
                <div style="font-size: 22px; font-weight: 800; color: #ec4899; margin-top: 2px;">{{ number_format($activeRun->rmse ?? 0, 4) }}</div>
                <small style="color: #94a3b8; font-size: 11px;">Root Mean Squared</small>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Precision@K</div>
                <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 2px;">{{ number_format($activeRun->precision_at_k ?? 0, 1) }}%</div>
                <small style="color: #94a3b8; font-size: 11px;">Akurasi Top-N</small>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 16px 20px; border-radius: var(--radius-lg); margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
        <div>
            <strong>Belum ada Model ML yang Ditetapkan Aktif!</strong>
            <p style="margin: 2px 0 0 0; font-size: 13px;">Silakan pilih dataset di bawah ini dan klik <strong>[RUN AUTOMATED ML]</strong> untuk menjalankan pipeline otomatis pertama.</p>
        </div>
    </div>
@endif

<!-- SECTION 2: AUTOMATED ML ORCHESTRATOR PANEL -->
<div class="detail-card" style="margin-bottom: 32px; background: #ffffff; border-radius: var(--radius-lg); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06); padding: 24px;">
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px;">
        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-gears" style="color: #0284c7;"></i> Jalankan Automated ML Pipeline
        </h2>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
            Pilih versi dataset yang tersedia. Sistem akan melakukan validasi kelayakan data pra-run, feature engineering, evaluasi kandidat K, klasterisasi K-Means, User-Based CF, dan evaluasi akurasi secara otomatis.
        </p>
    </div>

    <form action="{{ route('admin.ml_runs.run') }}" method="POST" id="automatedMlRunForm">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 24px;">
            <!-- Dataset Selection -->
            <div>
                <label for="datasetSelector" style="display: block; font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 8px;">
                    1. Pilih Versi Dataset <span style="color: #ef4444;">*</span>
                </label>
                <select name="dataset_version_id" id="datasetSelector" class="form-control" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; background: #ffffff;">
                    @foreach($datasetVersions as $ds)
                        <option value="{{ $ds->id }}" {{ ($selectedDataset && $selectedDataset->id === $ds->id) ? 'selected' : '' }}>
                            {{ $ds->version }} — {{ $ds->name }} ({{ number_format($ds->ratings_count) }} ratings) {{ $ds->isActive() ? '[AKTIF]' : '' }}
                        </option>
                    @endforeach
                </select>
                <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">
                    Hanya dataset yang valid dan memenuhi syarat kelayakan yang dapat diproses.
                </small>

                <!-- Dataset Summary Card -->
                <div id="datasetSummaryBox" style="margin-top: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                    <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Informasi Dataset Terpilih</span>
                        <span id="datasetStatusBadge" class="badge badge-info" style="font-size: 11px;">
                            {{ $selectedDataset?->status ?? 'uploaded' }}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; color: #475569;">
                        <div>Nama: <strong id="sumDatasetName">{{ $selectedDataset?->name ?? '-' }}</strong></div>
                        <div>Versi: <strong id="sumDatasetVersion">{{ $selectedDataset?->version ?? '-' }}</strong></div>
                        <div>Wisatawan: <strong id="sumDatasetUsers">{{ number_format($selectedDataset?->users_count ?? 0) }}</strong></div>
                        <div>Destinasi: <strong id="sumDatasetDestinations">{{ number_format($selectedDataset?->destinations_count ?? 0) }}</strong></div>
                        <div>Total Ratings: <strong id="sumDatasetRatings">{{ number_format($selectedDataset?->ratings_count ?? 0) }}</strong></div>
                        <div>Provinsi: <strong id="sumDatasetProvinces">{{ number_format($selectedDataset?->provinces_count ?? 0) }}</strong></div>
                        <div style="grid-column: 1 / -1;">Diunggah: <strong id="sumDatasetUploadedAt">{{ $selectedDataset?->uploaded_at?->format('d M Y H:i') ?? '-' }}</strong></div>
                    </div>
                </div>
            </div>

            <!-- Pre-Run Quality Report -->
            <div>
                <label style="display: block; font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 8px;">
                    2. Laporan Kualitas Data Pra-Run (Pre-Run Validation)
                </label>
                <div id="qualityReportBox" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 10px 12px; border-radius: 6px;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">Total Baris (Rows)</div>
                            <div id="qrTotalRows" style="font-size: 18px; font-weight: 800; color: #0f172a;">{{ number_format($inspectionReport['total_rows'] ?? 0) }}</div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 10px 12px; border-radius: 6px;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">Missing Values</div>
                            <div id="qrMissing" style="font-size: 18px; font-weight: 800; color: {{ ($inspectionReport['missing_values'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ number_format($inspectionReport['missing_values'] ?? 0) }}
                            </div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 10px 12px; border-radius: 6px;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">Duplikasi Baris</div>
                            <div id="qrDuplicates" style="font-size: 18px; font-weight: 800; color: {{ ($inspectionReport['duplicate_rows'] ?? 0) > 0 ? '#d97706' : '#16a34a' }};">
                                {{ number_format($inspectionReport['duplicate_rows'] ?? 0) }}
                            </div>
                        </div>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 10px 12px; border-radius: 6px;">
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">Invalid Ratings (&lt;1 atau &gt;5)</div>
                            <div id="qrInvalid" style="font-size: 18px; font-weight: 800; color: {{ ($inspectionReport['invalid_ratings'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }};">
                                {{ number_format($inspectionReport['invalid_ratings'] ?? 0) }}
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Alert Box -->
                    <div id="eligibilityAlert">
                        @if($inspectionReport && $inspectionReport['is_eligible'])
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 14px; border-radius: 6px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-circle-check" style="font-size: 16px; color: #16a34a;"></i>
                                <div>
                                    <strong>Dataset Memenuhi Syarat untuk Diproses.</strong>
                                    <div style="font-size: 12px; color: #15803d;">Seluruh kriteria integritas data, rentang rating, dan jumlah entitas terpenuhi.</div>
                                </div>
                            </div>
                        @else
                            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 14px; border-radius: 6px; font-size: 13px;">
                                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 4px;">
                                    <i class="fa-solid fa-triangle-exclamation" style="color: #dc2626;"></i>
                                    Dataset belum memenuhi syarat untuk diproses:
                                </div>
                                <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                                    @foreach($inspectionReport['reasons'] ?? ['Data tidak mencukupi'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Optional Hyperparameters (Collapsible) -->
        <details style="margin-bottom: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
            <summary style="font-weight: 700; font-size: 13px; color: #334155; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-sliders" style="color: #0284c7;"></i> Parameter Eksperimen Lanjutan (Opsional / Nilai Default Rekomendasi Riset)
            </summary>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-top: 16px;">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">K-Means Min K</label>
                    <input type="number" name="min_k" value="2" min="2" max="8" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">K-Means Max K</label>
                    <input type="number" name="max_k" value="5" min="2" max="8" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Random Seed</label>
                    <input type="number" name="seed" value="42" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Top-N Neighbors CF</label>
                    <input type="number" name="neighbors" value="10" min="1" max="50" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Train/Test Ratio</label>
                    <select name="train_test_ratio" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                        <option value="0.20" selected>80:20 (Rekomendasi Riset)</option>
                        <option value="0.30">70:30</option>
                    </select>
                </div>
            </div>
        </details>

        <!-- Pipeline Stages Overview & Submit Button -->
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-weight: 700; font-size: 13px; color: #1e40af; margin-bottom: 4px;">
                    Tahapan Otomatis yang Akan Dijalankan:
                </div>
                <div style="font-size: 12px; color: #3b82f6; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                    <span>1. Validasi</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>2. Preprocessing</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>3. Feature Eng.</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>4. K-Means (K=2..5)</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>5. Evaluasi K</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>6. Collaborative Filtering</span> <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    <span>7. Evaluasi 80:20</span>
                </div>
            </div>

            <div>
                <button type="submit" id="btnRunAutomatedMl" class="btn-primary" style="padding: 12px 28px; font-size: 15px; font-weight: 700; border-radius: var(--radius-pill); display: inline-flex; align-items: center; gap: 10px;" {{ ($inspectionReport && !$inspectionReport['is_eligible']) ? 'disabled' : '' }}>
                    <i class="fa-solid fa-play"></i> RUN AUTOMATED ML
                </button>
            </div>
        </div>
    </form>
</div>

<!-- SECTION 3: MODEL REGISTRY & RUN HISTORY -->
<div class="detail-card" style="margin-bottom: 32px; background: #ffffff; border-radius: var(--radius-lg); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06); padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-server" style="color: #0284c7;"></i> ML Model Registry (Riwayat Ekserimen)
            </h2>
            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                Daftar seluruh iterasi eksperimen ML yang pernah dijalankan beserta metrik evaluasinya. Admin dapat mengaktifkan model tertentu atau melakukan rollback kapan saja.
            </p>
        </div>
    </div>

    <div class="table-responsive" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                    <th style="padding: 12px 14px;">Run #</th>
                    <th style="padding: 12px 14px;">Dataset</th>
                    <th style="padding: 12px 14px;">Status Model</th>
                    <th style="padding: 12px 14px; text-align: center;">Optimal K</th>
                    <th style="padding: 12px 14px; text-align: right;">Silhouette</th>
                    <th style="padding: 12px 14px; text-align: right;">DBI</th>
                    <th style="padding: 12px 14px; text-align: right;">MAE</th>
                    <th style="padding: 12px 14px; text-align: right;">RMSE</th>
                    <th style="padding: 12px 14px; text-align: right;">Precision@K</th>
                    <th style="padding: 12px 14px;">Waktu Eksekusi</th>
                    <th style="padding: 12px 14px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mlRuns as $run)
                    <tr style="border-bottom: 1px solid #f1f5f9; {{ $run->isActive() ? 'background: #f0fdf4;' : '' }}">
                        <td style="padding: 12px 14px; font-weight: 700;">
                            <a href="{{ route('admin.ml_runs.show', $run) }}" style="color: #0284c7; text-decoration: none;">
                                #{{ $run->id }}
                            </a>
                            @if($run->isActive())
                                <span class="badge badge-success" style="font-size: 10px; margin-left: 6px; padding: 2px 8px; border-radius: var(--radius-pill);">AKTIF</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px;">
                            <span class="badge badge-info" style="font-weight: 700; border-radius: 4px; padding: 3px 8px;">
                                {{ $run->datasetVersion?->version ?? 'v1' }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px;">
                            @if($run->status === 'completed')
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #16a34a; font-weight: 600;">
                                    <i class="fa-solid fa-circle-check"></i> READY
                                </span>
                            @elseif($run->status === 'failed')
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #dc2626; font-weight: 600;" title="{{ $run->error_message }}">
                                    <i class="fa-solid fa-circle-xmark"></i> FAILED
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #d97706; font-weight: 600;">
                                    <i class="fa-solid fa-spinner fa-spin"></i> {{ strtoupper($run->status) }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-weight: 700; color: #0284c7;">
                            {{ $run->k ?? '-' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: monospace;">
                            {{ $run->silhouette_score ? number_format($run->silhouette_score, 4) : '-' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: monospace;">
                            {{ $run->davies_bouldin_score ? number_format($run->davies_bouldin_score, 4) : '-' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: monospace;">
                            {{ $run->mae ? number_format($run->mae, 4) : '-' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: monospace;">
                            {{ $run->rmse ? number_format($run->rmse, 4) : '-' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: monospace;">
                            {{ $run->precision_at_k ? number_format($run->precision_at_k, 1).'%' : '-' }}
                        </td>
                        <td style="padding: 12px 14px; color: #64748b; font-size: 12px;">
                            {{ $run->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                <a href="{{ route('admin.ml_runs.show', $run) }}" class="btn-outline" style="padding: 4px 10px; font-size: 11px; border-radius: 4px;" title="Lihat Hasil Detail">
                                    <i class="fa-solid fa-eye"></i> Hasil
                                </a>

                                @if($run->status === 'completed' && ! $run->isActive())
                                    <form action="{{ route('admin.ml_runs.activate', $run) }}" method="POST" style="margin: 0;" class="activate-run-form" data-run-id="{{ $run->id }}" data-dataset="{{ $run->datasetVersion?->version ?? 'v1' }}" data-k="{{ $run->k ?? 3 }}" data-sil="{{ number_format($run->silhouette_score ?? 0, 4) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary" style="padding: 4px 10px; font-size: 11px; border-radius: 4px; background: #16a34a; border-color: #16a34a;" title="Aktifkan sebagai model sistem">
                                            <i class="fa-solid fa-bolt"></i> Aktifkan
                                        </button>
                                    </form>
                                @endif

                                @if(! $run->isActive())
                                    <form action="{{ route('admin.ml_runs.destroy', $run) }}" method="POST" style="margin: 0;" class="delete-run-form" data-run-id="{{ $run->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-outline" style="padding: 4px 8px; font-size: 11px; border-radius: 4px; color: #dc2626; border-color: #fca5a5;" title="Hapus model">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 32px; color: #94a3b8;">
                            Belum ada riwayat ML Run. Silakan pilih dataset di atas dan klik <strong>[RUN AUTOMATED ML]</strong>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $mlRuns->links() }}
    </div>
</div>

<!-- SECTION 4: ML RUN COMPARISON MATRIX -->
@if($completedRuns->count() >= 2)
    <div class="detail-card" style="margin-bottom: 32px; background: #ffffff; border-radius: var(--radius-lg); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06); padding: 24px;">
        <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-code-compare" style="color: #6366f1;"></i> Matriks Perbandingan Model ML (Decision Support)
            </h2>
            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">
                Bandingkan trade-off metrik klasterisasi dan akurasi Collaborative Filtering untuk membantu Admin memilih model optimal sebelum melakukan aktivasi.
            </p>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="admin-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: center;">
                        <th style="padding: 10px 14px; text-align: left;">Model Run</th>
                        <th style="padding: 10px 14px;">Dataset</th>
                        <th style="padding: 10px 14px;">K</th>
                        <th style="padding: 10px 14px;">Silhouette (↑)</th>
                        <th style="padding: 10px 14px;">DBI (↓)</th>
                        <th style="padding: 10px 14px;">MAE (↓)</th>
                        <th style="padding: 10px 14px;">RMSE (↓)</th>
                        <th style="padding: 10px 14px;">Precision@K (↑)</th>
                        <th style="padding: 10px 14px;">Status Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completedRuns as $cRun)
                        <tr style="border-bottom: 1px solid #f1f5f9; text-align: center; {{ $cRun->isActive() ? 'background: #f0fdf4;' : '' }}">
                            <td style="padding: 10px 14px; text-align: left; font-weight: 700;">
                                <a href="{{ route('admin.ml_runs.show', $cRun) }}" style="color: #0284c7; text-decoration: none;">
                                    ML Run #{{ $cRun->id }}
                                </a>
                            </td>
                            <td style="padding: 10px 14px;">
                                <span class="badge badge-info" style="font-size: 11px;">{{ $cRun->datasetVersion?->version ?? 'v1' }}</span>
                            </td>
                            <td style="padding: 10px 14px; font-weight: 700; color: #0284c7;">{{ $cRun->k }}</td>
                            <td style="padding: 10px 14px; font-family: monospace; font-weight: 600; color: #16a34a;">
                                {{ number_format($cRun->silhouette_score ?? 0, 4) }}
                            </td>
                            <td style="padding: 10px 14px; font-family: monospace; font-weight: 600; color: #d97706;">
                                {{ number_format($cRun->davies_bouldin_score ?? 0, 4) }}
                            </td>
                            <td style="padding: 10px 14px; font-family: monospace;">
                                {{ number_format($cRun->mae ?? 0, 4) }}
                            </td>
                            <td style="padding: 10px 14px; font-family: monospace;">
                                {{ number_format($cRun->rmse ?? 0, 4) }}
                            </td>
                            <td style="padding: 10px 14px; font-family: monospace; font-weight: 600; color: #059669;">
                                {{ number_format($cRun->precision_at_k ?? 0, 1) }}%
                            </td>
                            <td style="padding: 10px 14px;">
                                @if($cRun->isActive())
                                    <span class="badge badge-success" style="font-size: 11px; padding: 3px 10px; border-radius: var(--radius-pill); font-weight: 700;">AKTIF (PRODUKSI)</span>
                                @else
                                    <span style="font-size: 12px; color: #64748b;">Siap Diaktifkan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const datasetSelector = document.getElementById('datasetSelector');
    const btnRun = document.getElementById('btnRunAutomatedMl');
    const form = document.getElementById('automatedMlRunForm');

    // Dynamic dataset summary update on dropdown change
    if (datasetSelector) {
        datasetSelector.addEventListener('change', function() {
            const versionId = this.value;
            if (!versionId) return;

            // Fetch dataset summary via AJAX
            fetch(`/admin/ml-runs/dataset-summary/${versionId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('sumDatasetName').textContent = data.name;
                    document.getElementById('sumDatasetVersion').textContent = data.version;
                    document.getElementById('sumDatasetUsers').textContent = Number(data.inspection.users_count).toLocaleString();
                    document.getElementById('sumDatasetDestinations').textContent = Number(data.inspection.destinations_count).toLocaleString();
                    document.getElementById('sumDatasetRatings').textContent = Number(data.inspection.ratings_count).toLocaleString();
                    document.getElementById('sumDatasetProvinces').textContent = Number(data.inspection.provinces_count).toLocaleString();
                    document.getElementById('sumDatasetUploadedAt').textContent = data.uploaded_at;
                    document.getElementById('datasetStatusBadge').textContent = data.status;

                    document.getElementById('qrTotalRows').textContent = Number(data.inspection.total_rows).toLocaleString();
                    document.getElementById('qrMissing').textContent = Number(data.inspection.missing_values).toLocaleString();
                    document.getElementById('qrMissing').style.color = data.inspection.missing_values > 0 ? '#dc2626' : '#16a34a';
                    document.getElementById('qrDuplicates').textContent = Number(data.inspection.duplicate_rows).toLocaleString();
                    document.getElementById('qrDuplicates').style.color = data.inspection.duplicate_rows > 0 ? '#d97706' : '#16a34a';
                    document.getElementById('qrInvalid').textContent = Number(data.inspection.invalid_ratings).toLocaleString();
                    document.getElementById('qrInvalid').style.color = data.inspection.invalid_ratings > 0 ? '#dc2626' : '#16a34a';

                    const alertContainer = document.getElementById('eligibilityAlert');
                    if (data.inspection.is_eligible) {
                        alertContainer.innerHTML = `
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 14px; border-radius: 6px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-circle-check" style="font-size: 16px; color: #16a34a;"></i>
                                <div>
                                    <strong>Dataset Memenuhi Syarat untuk Diproses.</strong>
                                    <div style="font-size: 12px; color: #15803d;">Seluruh kriteria integritas data, rentang rating, dan jumlah entitas terpenuhi.</div>
                                </div>
                            </div>
                        `;
                        if (btnRun) btnRun.removeAttribute('disabled');
                    } else {
                        const reasonsHtml = data.inspection.reasons.map(r => `<li>${r}</li>`).join('');
                        alertContainer.innerHTML = `
                            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 14px; border-radius: 6px; font-size: 13px;">
                                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 4px;">
                                    <i class="fa-solid fa-triangle-exclamation" style="color: #dc2626;"></i>
                                    Dataset belum memenuhi syarat untuk diproses:
                                </div>
                                <ul style="margin: 0; padding-left: 20px; font-size: 12px;">${reasonsHtml}</ul>
                            </div>
                        `;
                        if (btnRun) btnRun.setAttribute('disabled', 'disabled');
                    }
                })
                .catch(err => {
                    console.error('Error fetching dataset summary:', err);
                });
        });
    }

    // Run ML SweetAlert Confirmation
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedText = datasetSelector.options[datasetSelector.selectedIndex].text;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Jalankan Automated ML?',
                    html: `
                        <p style="font-size: 14px; color: #475569; margin-bottom: 12px;">
                            Sistem akan mengeksekusi pipeline machine learning penuh pada:
                            <br><strong style="color: #0284c7;">${selectedText}</strong>
                        </p>
                        <div style="text-align: left; background: #f8fafc; padding: 12px 16px; border-radius: 8px; font-size: 12px; color: #64748b; border: 1px solid #e2e8f0;">
                            <div>✓ Validasi & Pembersihan Data</div>
                            <div>✓ Feature Engineering & Standarisasi</div>
                            <div>✓ K-Means Clustering Multi-K (2..5)</div>
                            <div>✓ User-Based Collaborative Filtering</div>
                            <div>✓ Evaluasi Akurasi 80:20 (MAE, RMSE)</div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-play"></i> Ya, Jalankan Sekarang',
                    cancelButtonText: 'Batalkan',
                    confirmButtonColor: '#0284c7',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        btnRun.disabled = true;
                        btnRun.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Pipeline...';
                        form.submit();
                    }
                });
            } else {
                if (confirm('Jalankan Automated ML pada dataset ini?')) {
                    btnRun.disabled = true;
                    btnRun.innerHTML = 'Memproses...';
                    form.submit();
                }
            }
        });
    }

    // Activate ML Run SweetAlert Confirmation
    document.querySelectorAll('.activate-run-form').forEach(actForm => {
        actForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const runId = this.getAttribute('data-run-id');
            const dataset = this.getAttribute('data-dataset');
            const k = this.getAttribute('data-k');
            const sil = this.getAttribute('data-sil');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `Aktifkan ML Run #${runId}?`,
                    html: `
                        <div style="font-size: 14px; color: #334155; margin-bottom: 12px;">
                            Gunakan model ini sebagai model rekomendasi aktif sistem?
                        </div>
                        <div style="text-align: left; background: #f8fafc; padding: 12px 16px; border-radius: 8px; font-size: 13px; color: #475569; border: 1px solid #e2e8f0; margin-bottom: 8px;">
                            <div>Dataset: <strong>${dataset}</strong></div>
                            <div>Optimal K: <strong>${k} Klaster</strong></div>
                            <div>Silhouette Score: <strong>${sil}</strong></div>
                        </div>
                        <small style="color: #64748b; font-size: 12px;">
                            Model aktif sebelumnya akan diarsipkan secara aman. Seluruh rekomendasi pengguna akan beralih ke model ini seketika.
                        </small>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-bolt"></i> Ya, Aktifkan Model',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const btn = actForm.querySelector('button[type="submit"]');
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                        actForm.submit();
                    }
                });
            } else {
                if (confirm(`Gunakan ML Run #${runId} sebagai model aktif?`)) {
                    actForm.submit();
                }
            }
        });
    });

    // Delete ML Run SweetAlert Confirmation
    document.querySelectorAll('.delete-run-form').forEach(delForm => {
        delForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const runId = this.getAttribute('data-run-id');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `Hapus ML Run #${runId}?`,
                    text: 'Riwayat eksperimen dan artefak evaluasi model ini akan dihapus dari registry.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-trash"></i> Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        delForm.submit();
                    }
                });
            } else {
                if (confirm(`Hapus ML Run #${runId}?`)) {
                    delForm.submit();
                }
            }
        });
    });
});
</script>
@endpush
@endsection
