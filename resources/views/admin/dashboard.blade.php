@extends('layouts.admin')

@section('title', 'Admin Analytics Dashboard')
@section('page-title', 'Overview Sistem & Monitoring Riset')

@section('content')
<!-- Admin Welcome Banner -->
<div class="admin-page-header">
    <div>
        <h1>Dashboard Riset NusaWisata</h1>
        <p>
            Sistem Pemantauan dan Analisis Rekomendasi Wisata Indonesia menggunakan <strong>Collaborative Filtering</strong> dan <strong>K-Means Clustering</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <span class="badge badge-primary" style="background: #e0e7ff; color: #4338ca; padding: 6px 14px; font-size: 12px; font-weight: 700;">
            <i class="fa-solid fa-microchip" style="margin-right: 5px;"></i> ML Engine: Active
        </span>
        <a href="{{ route('admin.documentation.index') }}" class="btn-outline" style="font-size: 12.5px; padding: 7px 16px; border-radius: var(--radius-pill); text-decoration: none;">
            <i class="fa-solid fa-book-open" style="margin-right: 6px;"></i> Panduan Riset
        </a>
    </div>
</div>

<!-- Key Metrics Grid -->
<div class="admin-stats-grid">
    <x-cards.stat-card 
        label="Total Destinasi Wisata" 
        :value="number_format($totalDestinations)" 
        icon="fa-solid fa-map-location-dot" 
    />
    <x-cards.stat-card 
        label="Cakupan Provinsi" 
        :value="$totalProvinces . ' / 38'" 
        icon="fa-solid fa-archway" 
    />
    <x-cards.stat-card 
        label="Pengguna Terdaftar" 
        :value="number_format($totalUsers)" 
        icon="fa-solid fa-users" 
    />
    <x-cards.stat-card 
        label="Total Rating & Ulasan" 
        :value="number_format($totalRatings)" 
        icon="fa-solid fa-star" 
    />
</div>

<!-- Active Dataset Spotlight -->
@if($activeDataset)
    <div class="detail-card" style="margin-bottom: 28px; background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                    <span class="badge badge-success" style="background: #dcfce7; color: #15803d; font-weight: 700; padding: 3px 10px; border-radius: var(--radius-pill); font-size: 11.5px;">
                        ACTIVE DATASET ({{ $activeDataset->version }})
                    </span>
                    <span style="font-size: 12px; color: #64748b;">
                        Dataset Rujukan Sistem &bull; Status: <strong style="color: #16a34a;">Siap & Aktif</strong>
                    </span>
                </div>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">
                    {{ $activeDataset->name }}
                </h2>
                <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                    Berkas: <code>{{ $activeDataset->original_filename }}</code> &bull; Terakhir diproses: <strong>{{ $activeDataset->activated_at?->format('d M Y H:i') ?? '-' }}</strong>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="text-align: right; font-size: 12px; color: #64748b;">
                    <div>K-Means: <strong style="color: #16a34a;">READY (K={{ $latestKmeans?->parameters['k'] ?? 3 }})</strong></div>
                    <div>CF Engine: <strong style="color: #2563eb;">READY (Cosine Similarity)</strong></div>
                </div>
                <a href="{{ route('admin.dataset.index') }}" class="btn-outline" style="font-size: 12.5px; padding: 7px 16px; border-radius: var(--radius-pill); text-decoration: none;">
                    Kelola Dataset &rarr;
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Active ML Run Spotlight -->
@if($activeMlRun)
    <div class="detail-card" style="margin-bottom: 28px; background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                    <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 800; padding: 3px 10px; border-radius: var(--radius-pill); font-size: 11.5px;">
                        <i class="fa-solid fa-bolt"></i> ACTIVE ML RUN (#{{ $activeMlRun->id }})
                    </span>
                    <span style="font-size: 12px; color: #64748b;">
                        Dataset: <strong>{{ $activeMlRun->datasetVersion?->version ?? 'v1' }}</strong> &bull; Status: <strong style="color: #16a34a;">ACTIVE</strong>
                    </span>
                </div>
                <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">
                    Model Produksi: K-Means (K={{ $activeMlRun->k ?? 3 }}) + User-Based Collaborative Filtering
                </h2>
                <div style="display: flex; gap: 16px; font-size: 12.5px; color: #475569; margin-top: 6px; flex-wrap: wrap;">
                    <span>Silhouette: <strong>{{ number_format($activeMlRun->silhouette_score ?? 0, 4) }}</strong></span>
                    <span>DBI: <strong>{{ number_format($activeMlRun->davies_bouldin_score ?? 0, 4) }}</strong></span>
                    <span>MAE: <strong>{{ number_format($activeMlRun->mae ?? 0, 4) }}</strong></span>
                    <span>RMSE: <strong>{{ number_format($activeMlRun->rmse ?? 0, 4) }}</strong></span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="{{ route('admin.ml_runs.show', $activeMlRun) }}" class="btn-primary" style="font-size: 12.5px; padding: 7px 16px; border-radius: var(--radius-pill); text-decoration: none;">
                    <i class="fa-solid fa-eye" style="margin-right: 4px;"></i> Lihat Hasil
                </a>
                <a href="{{ route('admin.ml_runs.index') }}" class="btn-outline" style="font-size: 12.5px; padding: 7px 14px; border-radius: var(--radius-pill); text-decoration: none;">
                    Model Registry &rarr;
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Destination Map Section (Leaflet + OpenStreetMap) -->
<div class="detail-card" style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-map-location-dot" style="color: #ef4444;"></i> Peta Sebaran Destinasi Wisata Indonesia
            </h3>
            <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0 0;">
                Memvisualisasikan seluruh destinasi dengan koordinat GPS dari dataset aktif (OpenStreetMap & Leaflet).
            </p>
        </div>

        <!-- Map Filter Controls -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <select id="mapProvinceFilter" class="form-select" style="font-size: 12px; padding: 6px 12px; height: 34px; min-width: 150px;">
                <option value="">Semua Provinsi ({{ count($mapDestinations) }})</option>
                @foreach($mapProvinces as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </select>
            <select id="mapCategoryFilter" class="form-select" style="font-size: 12px; padding: 6px 12px; height: 34px; min-width: 140px;">
                <option value="">Semua Kategori</option>
                @foreach($mapCategories as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            <button type="button" id="mapResetBtn" class="btn-outline" style="font-size: 12px; padding: 6px 12px; height: 34px; border-radius: var(--radius-sm);">
                Reset
            </button>
        </div>
    </div>

    <!-- Map Container -->
    <div class="admin-map-wrap" style="height: 380px;">
        <div id="adminDashboardMap" style="width: 100%; height: 100%; z-index: 10;"></div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; color: #64748b; flex-wrap: wrap; gap: 8px;">
        <span><i class="fa-solid fa-location-pin" style="color: #1a56db; margin-right: 4px;"></i> Total <strong>{{ count($mapDestinations) }}</strong> destinasi terplot pada peta</span>
        <span>Klik marker destinasi untuk melihat ringkasan nama, provinsi, kategori, dan rating</span>
    </div>
</div>

<!-- K-Means Clustering Analytics Section -->
<div class="admin-chart-card" style="margin-bottom: 28px;">
    <div class="admin-chart-header">
        <div>
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-diagram-project" style="color: var(--color-primary);"></i> Analisis K-Means Clustering
            </h3>
            <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0 0;">
                Distribusi wisatawan nusantara ke dalam klaster preferensi dan visualisasi reduksi dimensi 2D PCA.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if(!empty($kmeansData['metrics']))
                <span class="badge" style="background: #f0fdf4; color: #166534; font-size: 11.5px; font-weight: 700; border: 1px solid #bbf7d0;">
                    Silhouette: {{ $kmeansData['metrics']['silhouette'] ?? '-' }}
                </span>
                <span class="badge" style="background: #eff6ff; color: #1e40af; font-size: 11.5px; font-weight: 700; border: 1px solid #bfdbfe;">
                    DBI: {{ $kmeansData['metrics']['davies_bouldin'] ?? '-' }}
                </span>
            @endif
            <a href="{{ route('admin.clustering.index') }}" class="btn-outline" style="font-size: 12px; padding: 5px 12px; border-radius: var(--radius-pill); text-decoration: none;">
                Detail K-Means &rarr;
            </a>
        </div>
    </div>

    <div class="admin-grid-2col" style="margin-bottom: 0;">
        <!-- Left: Cluster Distribution Bar Chart -->
        <div>
            <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; display: flex; justify-content: space-between;">
                <span>Distribusi Anggota Klaster (K = {{ $kmeansData['k'] ?? count($clusterCounts) }})</span>
                <span style="color: #64748b; font-weight: normal;">{{ array_sum($clusterCounts) }} Wisatawan Terklaster</span>
            </div>
            <div class="admin-chart-canvas-wrap" style="height: 280px;">
                <canvas id="clusterDistributionChart"></canvas>
            </div>
        </div>

        <!-- Right: 2D PCA Scatter Plot -->
        <div>
            <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; display: flex; justify-content: space-between;">
                <span>Visualisasi 2D PCA (PC1 vs PC2)</span>
                <span style="color: #64748b; font-weight: normal;">Scatter Plot Interaktif</span>
            </div>
            <div class="admin-chart-canvas-wrap" style="height: 280px;">
                <canvas id="pcaScatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Collaborative Filtering Analytics Section -->
<div class="admin-chart-card" style="margin-bottom: 28px;">
    <div class="admin-chart-header">
        <div>
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-network-wired" style="color: var(--color-accent);"></i> Analisis User-Based Collaborative Filtering
            </h3>
            <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0 0;">
                Distribusi penilaian ulasan dan metrik kelangkaan matriks interaksi (Sparsity) wisatawan-destinasi.
            </p>
        </div>
        <a href="{{ route('admin.collaborative_filtering.index') }}" class="btn-outline" style="font-size: 12px; padding: 5px 12px; border-radius: var(--radius-pill); text-decoration: none;">
            Simulator & Evaluasi CF &rarr;
        </a>
    </div>

    <div class="admin-grid-2col" style="margin-bottom: 0;">
        <!-- Left: Rating Score Distribution Chart -->
        <div>
            <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px;">
                Distribusi Skor Rating Wisatawan (1★ sampai 5★)
            </div>
            <div class="admin-chart-canvas-wrap" style="height: 260px;">
                <canvas id="ratingDistributionChart"></canvas>
            </div>
        </div>

        <!-- Right: Matrix Sparsity & Characteristics Card -->
        <div style="display: flex; flex-direction: column; justify-content: space-between; background: var(--color-bg-subtle); border-radius: var(--radius-lg); padding: 20px; border: 1px solid var(--color-border-light);">
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                    <i class="fa-solid fa-calculator" style="color: #2563eb; margin-right: 6px;"></i> Karakteristik Matriks Rating
                </div>
                <p style="font-size: 12.5px; color: #64748b; line-height: 1.6; margin-bottom: 16px;">
                    Formula Kelangkaan: <code>Sparsity = 1 - (Ulasan / (Pengguna &times; Destinasi))</code>. Matriks yang jarang (sparse) menjadi pertimbangan utama penggunaan klaster K-Means untuk membatasi pencarian Nearest Neighbors (Cosine Similarity).
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div style="background: #ffffff; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <span style="font-size: 11.5px; color: #64748b;">Tingkat Kelangkaan (Sparsity)</span>
                        <div style="font-size: 22px; font-weight: 800; color: #b45309;">{{ $matrixStats['sparsity_percent'] ?? '0' }}%</div>
                    </div>
                    <div style="background: #ffffff; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <span style="font-size: 11.5px; color: #64748b;">Kerapatan (Density)</span>
                        <div style="font-size: 22px; font-weight: 800; color: #15803d;">{{ $matrixStats['density_percent'] ?? '0' }}%</div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                    <div>&bull; Total Sel Matriks: <strong>{{ number_format(($matrixStats['matrix_size'] ?? 0)) }}</strong></div>
                    <div>&bull; Rata-rata Ulasan per Wisatawan: <strong>{{ $matrixStats['avg_ratings_per_user'] ?? 0 }} ulasan</strong></div>
                    <div>&bull; Metode Kemiripan: <strong>Cosine Similarity (User-Based CF)</strong></div>
                </div>
            </div>

            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--color-border);">
                <a href="{{ route('admin.collaborative_filtering.index') }}" style="font-size: 12.5px; font-weight: 700; color: var(--color-primary); text-decoration: none;">
                    Buka Simulator Rekomendasi Pengguna &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout: Top Destinations & Recent Community Reviews -->
<div class="admin-grid-2col">
    <!-- Top Destinations -->
    <div class="detail-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin: 0;">
                <i class="fa-solid fa-trophy" style="color: #f59e0b; margin-right: 6px;"></i> Destinasi Terpopuler
            </h3>
            <a href="{{ route('admin.destinations.index') }}" style="font-size: 12.5px; color: var(--color-accent); text-decoration: none; font-weight: 600;">
                Semua Destinasi &rarr;
            </a>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Destinasi</th>
                        <th>Rating</th>
                        <th>Ulasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topDestinations as $dest)
                        <tr>
                            <td style="font-weight: 600;">
                                <a href="{{ route('destinations.show', $dest) }}" target="_blank" style="color: var(--color-primary-dark); text-decoration: none;">
                                    {{ $dest->name }}
                                </a>
                                <small style="display: block; color: var(--color-text-light); font-weight: normal;">
                                    {{ $dest->province->name ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: #f59e0b;">★ {{ number_format($dest->google_rating, 1) }}</span>
                            </td>
                            <td>{{ number_format($dest->review_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">Belum ada data destinasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Reviews -->
    <div class="detail-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin: 0;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #6366f1; margin-right: 6px;"></i> Ulasan Wisatawan Terbaru
            </h3>
            <a href="{{ route('admin.ratings.index') }}" style="font-size: 12.5px; color: var(--color-accent); text-decoration: none; font-weight: 600;">
                Semua Ulasan &rarr;
            </a>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Destinasi</th>
                        <th>Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRatings as $r)
                        <tr>
                            <td style="font-weight: 600;">
                                {{ $r->user->name ?? 'Anonim' }}
                                <small style="display: block; color: var(--color-text-light); font-weight: normal;">
                                    {{ $r->created_at?->diffForHumans() ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <span style="font-weight: 500;">{{ $r->destination->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: #f59e0b;">★ {{ $r->rating }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">Belum ada ulasan terbaru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. Destination Map Initialization (Leaflet + OSM)
    // -------------------------------------------------------------
    const rawDestinations = @json($mapDestinations);
    const mapEl = document.getElementById('adminDashboardMap');

    if (mapEl && typeof L !== 'undefined' && window.NusaMap) {
        // Center of Indonesia Archipelago
        const defaultLat = -2.548926;
        const defaultLng = 118.0148634;
        const defaultZoom = 5;

        const mapInstance = NusaMap.init('adminDashboardMap', defaultLat, defaultLng, defaultZoom);
        let currentMarkers = [];

        function renderMarkers(items) {
            // Remove existing markers
            currentMarkers.forEach(m => mapInstance.removeLayer(m));
            currentMarkers = [];

            const bounds = [];
            items.forEach(d => {
                if (d.lat && d.lng) {
                    const marker = NusaMap.addMarker(mapInstance, d.lat, d.lng, d.name, {
                        province: d.province,
                        category: d.category,
                        rating: d.rating,
                        url: d.url
                    });
                    if (marker) {
                        currentMarkers.push(marker);
                        bounds.push([d.lat, d.lng]);
                    }
                }
            });

            if (bounds.length > 1) {
                mapInstance.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
            } else if (bounds.length === 1) {
                mapInstance.setView(bounds[0], 12);
            }
        }

        renderMarkers(rawDestinations);

        // Filter event listeners
        const provinceFilter = document.getElementById('mapProvinceFilter');
        const categoryFilter = document.getElementById('mapCategoryFilter');
        const resetBtn = document.getElementById('mapResetBtn');

        function applyMapFilter() {
            const selectedProv = provinceFilter ? provinceFilter.value : '';
            const selectedCat = categoryFilter ? categoryFilter.value : '';

            const filtered = rawDestinations.filter(d => {
                const matchProv = !selectedProv || d.province === selectedProv;
                const matchCat = !selectedCat || d.category === selectedCat;
                return matchProv && matchCat;
            });

            renderMarkers(filtered);
        }

        if (provinceFilter) provinceFilter.addEventListener('change', applyMapFilter);
        if (categoryFilter) categoryFilter.addEventListener('change', applyMapFilter);
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (provinceFilter) provinceFilter.value = '';
                if (categoryFilter) categoryFilter.value = '';
                renderMarkers(rawDestinations);
                mapInstance.setView([defaultLat, defaultLng], defaultZoom);
            });
        }
    }

    // -------------------------------------------------------------
    // 2. K-Means Cluster Distribution Chart (Bar Chart)
    // -------------------------------------------------------------
    const clusterCountsData = @json($clusterCounts);
    const clusterCanvas = document.getElementById('clusterDistributionChart');

    if (clusterCanvas && typeof Chart !== 'undefined') {
        const clusterLabels = Object.keys(clusterCountsData).map(cId => 'Klaster ' + cId);
        const clusterValues = Object.values(clusterCountsData);
        const clusterColors = [
            'rgba(37, 99, 235, 0.85)',   // Cluster 1 (Blue)
            'rgba(16, 185, 129, 0.85)',  // Cluster 2 (Green)
            'rgba(245, 158, 11, 0.85)',  // Cluster 3 (Amber)
            'rgba(239, 68, 68, 0.85)',   // Cluster 4 (Red)
            'rgba(147, 51, 234, 0.85)'   // Cluster 5 (Purple)
        ];

        new Chart(clusterCanvas, {
            type: 'bar',
            data: {
                labels: clusterLabels,
                datasets: [{
                    label: 'Jumlah Wisatawan',
                    data: clusterValues,
                    backgroundColor: clusterColors.slice(0, clusterLabels.length),
                    borderRadius: 6,
                    maxBarThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw + ' Profil Wisatawan';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. K-Means 2D PCA Scatter Plot
    // -------------------------------------------------------------
    const pcaData = @json($kmeansData['pca_data'] ?? []);
    const pcaCanvas = document.getElementById('pcaScatterChart');

    if (pcaCanvas && typeof Chart !== 'undefined' && pcaData.length > 0) {
        // Group points by cluster
        const clusterColorsMap = {
            1: '#2563eb',
            2: '#10b981',
            3: '#f59e0b',
            4: '#ef4444',
            5: '#8b5cf6'
        };

        const clustersMap = {};
        pcaData.forEach(pt => {
            const cId = pt.cluster_id || 1;
            if (!clustersMap[cId]) clustersMap[cId] = [];
            clustersMap[cId].push({
                x: pt.x,
                y: pt.y,
                name: pt.name,
                userId: pt.user_id,
                clusterId: cId
            });
        });

        const datasets = Object.keys(clustersMap).map(cId => ({
            label: 'Klaster ' + cId,
            data: clustersMap[cId],
            backgroundColor: clusterColorsMap[cId] || '#64748b',
            pointRadius: 4,
            pointHoverRadius: 6
        }));

        new Chart(pcaCanvas, {
            type: 'scatter',
            data: { datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 10, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const pt = context.raw;
                                return [
                                    (pt.name || 'User #' + pt.userId) + ' (Klaster ' + pt.clusterId + ')',
                                    'PC1: ' + pt.x + ', PC2: ' + pt.y
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Principal Component 1 (PC1)', font: { size: 11 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    y: {
                        title: { display: true, text: 'Principal Component 2 (PC2)', font: { size: 11 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 4. CF Rating Distribution Chart (Bar Chart 1★ - 5★)
    // -------------------------------------------------------------
    const ratingDistData = @json($ratingDistribution);
    const ratingCanvas = document.getElementById('ratingDistributionChart');

    if (ratingCanvas && typeof Chart !== 'undefined') {
        const ratingLabels = ['1 Bintang', '2 Bintang', '3 Bintang', '4 Bintang', '5 Bintang'];
        const ratingValues = [
            ratingDistData[1] || 0,
            ratingDistData[2] || 0,
            ratingDistData[3] || 0,
            ratingDistData[4] || 0,
            ratingDistData[5] || 0
        ];

        new Chart(ratingCanvas, {
            type: 'bar',
            data: {
                labels: ratingLabels,
                datasets: [{
                    label: 'Jumlah Ulasan',
                    data: ratingValues,
                    backgroundColor: [
                        '#ef4444',
                        '#f97316',
                        '#facc15',
                        '#60a5fa',
                        '#22c55e'
                    ],
                    borderRadius: 5,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw + ' Ulasan';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
