@extends('layouts.admin')

@section('title', "ML Run #{$run->id} Hasil Eksperimen — NusaWisata")
@section('page-title', "ML Run #{$run->id}")

@section('content')
<div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="{{ route('admin.ml_runs.index') }}" style="color: #64748b; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Registry
            </a>
            <span style="color: #cbd5e1;">/</span>
            <span style="font-size: 13px; color: #0284c7; font-weight: 700;">ML Run #{{ $run->id }}</span>
        </div>
        <h1 style="display: flex; align-items: center; gap: 12px; margin: 0;">
            ML Run #{{ $run->id }} — Laporan Hasil & Visualisasi
            @if($run->isActive())
                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 13px; font-weight: 800; padding: 4px 14px; border-radius: var(--radius-pill); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-circle-check"></i> MODEL AKTIF (PRODUKSI)
                </span>
            @else
                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 13px; font-weight: 700; padding: 4px 14px; border-radius: var(--radius-pill);">
                    READY
                </span>
            @endif
        </h1>
        <p style="color: var(--color-text-muted); font-size: 14px; margin-top: 4px;">
            Dataset: <strong>{{ $run->datasetVersion?->version ?? 'v1' }}</strong> ({{ $run->datasetVersion?->name ?? 'Dataset' }}) • Dijalankan pada {{ $run->created_at->format('d M Y H:i:s') }}
        </p>
    </div>

    <div style="display: flex; gap: 10px; align-items: center;">
        @if(! $run->isActive() && $run->status === 'completed')
            <form action="{{ route('admin.ml_runs.activate', $run) }}" method="POST" id="activateModelForm" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-primary" style="background: #16a34a; border-color: #16a34a; border-radius: var(--radius-pill); padding: 10px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-bolt"></i> Tetapkan Sebagai Model Aktif
                </button>
            </form>
        @endif
    </div>
</div>

<!-- SECTION 1: RUN SUMMARY STATS -->
<div class="admin-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Optimal K (Klaster)</div>
        <div style="font-size: 26px; font-weight: 800; color: #0284c7; margin-top: 4px;">{{ $run->k ?? 3 }}</div>
        <small style="color: #94a3b8; font-size: 11px;">Segmentasi Wisatawan</small>
    </div>
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Silhouette Score</div>
        <div style="font-size: 26px; font-weight: 800; color: #16a34a; margin-top: 4px;">{{ number_format($run->silhouette_score ?? 0, 4) }}</div>
        <small style="color: #94a3b8; font-size: 11px;">Kualitas Pemisahan Klaster</small>
    </div>
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Davies-Bouldin (DBI)</div>
        <div style="font-size: 26px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ number_format($run->davies_bouldin_score ?? 0, 4) }}</div>
        <small style="color: #94a3b8; font-size: 11px;">Rasio Kompak / Terpisah</small>
    </div>
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">CF MAE (80:20 Split)</div>
        <div style="font-size: 26px; font-weight: 800; color: #6366f1; margin-top: 4px;">{{ number_format($run->mae ?? 0, 4) }}</div>
        <small style="color: #94a3b8; font-size: 11px;">Rata-rata Selisih Prediksi</small>
    </div>
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">CF RMSE</div>
        <div style="font-size: 26px; font-weight: 800; color: #ec4899; margin-top: 4px;">{{ number_format($run->rmse ?? 0, 4) }}</div>
        <small style="color: #94a3b8; font-size: 11px;">Akar Rata-rata Kuadrat Eror</small>
    </div>
    <div class="stat-card" style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Precision@K</div>
        <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">{{ number_format($run->precision_at_k ?? 0, 1) }}%</div>
        <small style="color: #94a3b8; font-size: 11px;">Rekomendasi Relevan Top-N</small>
    </div>
</div>

<!-- SECTION 2: K-MEANS CLUSTERING VISUALIZATIONS -->
<div style="margin-bottom: 32px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <span style="background: #e0f2fe; color: #0284c7; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 4px;">MODUL 1</span>
        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Hasil & Visualisasi K-Means Clustering</h2>
    </div>

    <div class="admin-grid-2col" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <!-- Chart 1: Cluster Distribution -->
        <div class="admin-chart-card" style="background: #ffffff; padding: 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="admin-chart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-column" style="color: #0284c7;"></i> Distribusi Anggota Klaster
                </h3>
                <span class="badge badge-info" style="font-size: 11px;">K = {{ $run->k }}</span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">
                Jumlah wisatawan yang terpetakan ke masing-masing klaster profil perjalanan.
            </p>
            <div style="height: 280px; position: relative;">
                <canvas id="clusterDistChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: 2D PCA Projection Scatter Plot -->
        <div class="admin-chart-card" style="background: #ffffff; padding: 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="admin-chart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-draw-polygon" style="color: #6366f1;"></i> Proyeksi 2D PCA (Scatter Plot)
                </h3>
                <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">PC1 vs PC2</span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">
                Visualisasi reduksi dimensi ruang fitur wisatawan ke bidang 2D untuk memvalidasi separabilitas klaster.
            </p>
            <div style="height: 280px; position: relative;">
                <canvas id="pcaScatterChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Candidate K Evaluation Curves -->
    @if(!empty($run->summary['k_candidates']))
        <div class="admin-chart-card" style="background: #ffffff; padding: 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px;">
            <div class="admin-chart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-line" style="color: #10b981;"></i> Kurva Evaluasi Kandidat K (Elbow & Silhouette)
                </h3>
                <span style="font-size: 12px; color: #16a34a; font-weight: 700;">
                    <i class="fa-solid fa-star"></i> Terpilih: K = {{ $run->k }}
                </span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">
                Grafik perbandingan skor Silhouette dan Davies-Bouldin Index (DBI) pada rentang nilai K yang diuji untuk menentukan jumlah klaster optimal secara objektif.
            </p>
            <div style="height: 260px; position: relative;">
                <canvas id="kEvalChart"></canvas>
            </div>
        </div>
    @endif
</div>

<!-- SECTION 3: COLLABORATIVE FILTERING & EVALUATION -->
<div style="margin-bottom: 32px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <span style="background: #fdf4ff; color: #c026d3; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 4px;">MODUL 2</span>
        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">User-Based Collaborative Filtering & Evaluasi</h2>
    </div>

    <!-- Matrix Statistics & CF Config Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Ukuran Matriks Pengguna × Destinasi</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                {{ number_format($run->summary['cf_stats']['matrix_size'] ?? 0) }}
            </div>
            <small style="color: #64748b; font-size: 12px;">
                {{ $run->summary['cf_stats']['total_users'] ?? 0 }} Pengguna × {{ $run->summary['cf_stats']['total_destinations'] ?? 0 }} Destinasi
            </small>
        </div>

        <div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Sparsitas Matriks (Sparsity)</div>
            <div style="font-size: 22px; font-weight: 800; color: #ea580c; margin-top: 4px;">
                {{ number_format($run->summary['cf_stats']['sparsity_percent'] ?? 0, 2) }}%
            </div>
            <small style="color: #64748b; font-size: 12px;">
                Densitas Data Ulasan: {{ number_format($run->summary['cf_stats']['density_percent'] ?? 0, 2) }}%
            </small>
        </div>

        <div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Metode Kesamaan (Similarity)</div>
            <div style="font-size: 20px; font-weight: 800; color: #0284c7; margin-top: 4px;">
                Cosine Similarity
            </div>
            <small style="color: #64748b; font-size: 12px;">Top-{{ $run->parameters['neighbors_count'] ?? 10 }} Tetangga Terdekat</small>
        </div>

        <div style="background: #ffffff; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Metode Prediksi Rating</div>
            <div style="font-size: 20px; font-weight: 800; color: #6366f1; margin-top: 4px;">
                Weighted Average
            </div>
            <small style="color: #64748b; font-size: 12px;">Bobot Berdasarkan Nilai Similarity</small>
        </div>
    </div>

    <!-- Traceability Architecture & Reproducibility -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-route" style="color: #0284c7;"></i> Rekayasa Alur Rekomendasi & Traceability (End-to-End)
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; text-align: center;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">1. Target User</div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;">User #ID</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">2. Active Model</div>
                <div style="font-size: 13px; font-weight: 700; color: #0284c7; margin-top: 2px;">ML Run #{{ $run->id }}</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">3. Dataset Riset</div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;">{{ $run->datasetVersion?->version ?? 'v1' }}</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">4. Klaster Segmen</div>
                <div style="font-size: 13px; font-weight: 700; color: #16a34a; margin-top: 2px;">Klaster {{ $run->k }}</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">5. Tetangga Serupa</div>
                <div style="font-size: 13px; font-weight: 700; color: #6366f1; margin-top: 2px;">Top-{{ $run->parameters['neighbors_count'] ?? 10 }} Peers</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">6. Prediksi Rating</div>
                <div style="font-size: 13px; font-weight: 700; color: #d97706; margin-top: 2px;">Weighted Score</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 10px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600;">7. Output Top-N</div>
                <div style="font-size: 13px; font-weight: 700; color: #059669; margin-top: 2px;">Destinasi Terbaik</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Cluster Distribution Chart
    const clusterData = @json($run->summary['cluster_distribution'] ?? []);
    const clusterLabels = Object.keys(clusterData).map(k => `Klaster ${k}`);
    const clusterCounts = Object.values(clusterData);
    const clusterColors = ['#0284c7', '#10b981', '#f59e0b', '#6366f1', '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e'];

    const ctxDist = document.getElementById('clusterDistChart');
    if (ctxDist) {
        new Chart(ctxDist, {
            type: 'bar',
            data: {
                labels: clusterLabels,
                datasets: [{
                    label: 'Jumlah Wisatawan',
                    data: clusterCounts,
                    backgroundColor: clusterColors.slice(0, clusterLabels.length),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 10 }
                    }
                }
            }
        });
    }

    // 2. 2D PCA Scatter Plot
    const rawPca = @json($run->summary['pca_data'] ?? []);
    const pcaDatasets = [];
    const clustersSet = [...new Set(rawPca.map(p => p.cluster_id))].sort();

    clustersSet.forEach((cId, idx) => {
        const points = rawPca.filter(p => p.cluster_id === cId).map(p => ({
            x: p.x,
            y: p.y,
            name: p.name
        }));

        pcaDatasets.push({
            label: `Klaster ${cId}`,
            data: points,
            backgroundColor: clusterColors[idx % clusterColors.length],
            borderColor: clusterColors[idx % clusterColors.length],
            pointRadius: 5,
            pointHoverRadius: 8
        });
    });

    const ctxPca = document.getElementById('pcaScatterChart');
    if (ctxPca && pcaDatasets.length > 0) {
        new Chart(ctxPca, {
            type: 'scatter',
            data: { datasets: pcaDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const pt = context.raw;
                                return `${pt.name || 'User'}: (${pt.x}, ${pt.y})`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Principal Component 1 (PC1)' }
                    },
                    y: {
                        title: { display: true, text: 'Principal Component 2 (PC2)' }
                    }
                }
            }
        });
    }

    // 3. K Evaluation Curves (Elbow & Silhouette)
    const candidates = @json($run->summary['k_candidates'] ?? []);
    const kValues = Object.keys(candidates);
    if (kValues.length > 0) {
        const silScores = kValues.map(k => candidates[k].silhouette);
        const dbiScores = kValues.map(k => candidates[k].davies_bouldin);

        const ctxK = document.getElementById('kEvalChart');
        if (ctxK) {
            new Chart(ctxK, {
                type: 'line',
                data: {
                    labels: kValues.map(k => `K = ${k}`),
                    datasets: [
                        {
                            label: 'Silhouette Score (Makin tinggi makin baik)',
                            data: silScores,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Davies-Bouldin Index (Makin rendah makin baik)',
                            data: dbiScores,
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.05)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Silhouette' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Davies-Bouldin' }
                        }
                    }
                }
            });
        }
    }

    // Activate Confirmation SweetAlert
    const actForm = document.getElementById('activateModelForm');
    if (actForm) {
        actForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Gunakan ML Run Ini Sebagai Model Aktif?',
                    html: `
                        <p style="font-size: 14px; color: #475569; margin-bottom: 12px;">
                            Model <strong>ML Run #{{ $run->id }}</strong> (K={{ $run->k }}, Silhouette={{ number_format($run->silhouette_score ?? 0, 4) }})
                            akan menjadi acuan utama seluruh rekomendasi wisata di sistem.
                        </p>
                        <small style="color: #64748b; font-size: 12px;">
                            Model aktif sebelumnya akan diarsipkan secara aman.
                        </small>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-bolt"></i> Ya, Aktifkan Sekarang',
                    cancelButtonText: 'Batalkan',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        actForm.submit();
                    }
                });
            } else {
                if (confirm('Aktifkan model ML ini sebagai model rekomendasi sistem?')) {
                    actForm.submit();
                }
            }
        });
    }
});
</script>
@endpush
@endsection
