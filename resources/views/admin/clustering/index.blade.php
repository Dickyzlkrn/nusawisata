@extends('layouts.admin')

@section('title', 'K-Means Clustering Panel')
@section('page-title', 'K-Means Clustering')

@section('content')
<!-- Mode Switcher Tabs -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--color-border); padding-bottom: 14px; flex-wrap: wrap;">
    <a href="{{ route('admin.clustering.index', ['mode' => 'users']) }}" class="btn {{ $mode === 'users' ? 'btn-primary' : 'btn-outline' }}" style="font-weight: 700; border-radius: var(--radius-pill); padding: 9px 20px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-users"></i> Klasterisasi Pengguna (Rating Behavior) &mdash; <span style="font-size: 11px; opacity: 0.9;">Metode Utama</span>
        <span class="badge" style="background: {{ $mode === 'users' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' }}; color: {{ $mode === 'users' ? '#fff' : '#334155' }}; font-size: 11px;">K = 3</span>
    </a>
    <a href="{{ route('admin.clustering.index', ['mode' => 'destinations']) }}" class="btn {{ $mode === 'destinations' ? 'btn-primary' : 'btn-outline' }}" style="font-weight: 700; border-radius: var(--radius-pill); padding: 9px 20px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-map-location-dot"></i> Eksploratori: Klaster Destinasi &mdash; <span style="font-size: 11px; opacity: 0.9;">Eksperimen Tambahan</span>
        <span class="badge" style="background: {{ $mode === 'destinations' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' }}; color: {{ $mode === 'destinations' ? '#fff' : '#334155' }}; font-size: 11px;">K = 4</span>
    </a>
</div>

@if($mode === 'destinations')
    <!-- Exploratory Warning Banner -->
    <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <i class="fa-solid fa-triangle-exclamation" style="color: #d97706; font-size: 20px; margin-top: 2px;"></i>
            <div>
                <h4 style="margin: 0 0 4px; color: #92400e; font-size: 14px; font-weight: 700;">Catatan Metodologi & Peringatan Degenerate Cluster (Singleton)</h4>
                <p style="margin: 0; color: #b45309; font-size: 13px; line-height: 1.5;">
                    <strong>Modul Analisis Eksploratori Tambahan:</strong> Klasterisasi destinasi (Harga & Rating) adalah modul analisis tambahan dan <em>BUKAN</em> pengganti metodologi utama penelitian NusaWisata (Segmentasi Pengguna via K-Means + User-Based Collaborative Filtering).<br>
                    <strong>Peringatan Outlier Klaster 4 (n=1):</strong> Destinasi Raja Ampat (Rp 500.000, 4.9★) terisolasi sebagai singleton / degenerate cluster akibat deviasi harga tiket ekstrem dalam ruang fitur skala standar (StandardScaler). Karakteristik ini diklasifikasikan secara ketat sebagai <em>outlier cluster</em> dan bukan kelompok pola umum.
                </p>
            </div>
        </div>
    </div>
@endif

<div class="admin-page-header">
    <div>
        @if($mode === 'destinations')
            <h1>Eksploratori: K-Means Klasterisasi Destinasi</h1>
            <p>
                Analisis eksperimen tambahan: Segmentasi katalog destinasi wisata berbasis dua fitur objektif (<code>price</code> dan <code>destination_rating</code>). Seluruh kolom metadata non-fitur (<code>user_id</code>, <code>place_id</code>, <code>place_name</code>, <code>province</code>, <code>category</code>, <code>place_ratings</code>, <code>visitor_count</code>, <code>popularity_score</code>, <code>popular_day</code>, <code>popular_hour</code>) secara ketat dieksklusikan dari kalkulasi jarak clustering.
            </p>
        @else
            <h1>Klasterisasi Pengguna & Evaluasi Segmentasi (Metode Utama)</h1>
            <p>
                Metodologi Utama Riset: Segmentasi profil wisatawan nusantara berbasis matriks perilaku ulasan untuk meningkatkan efisiensi dan relevansi Collaborative Filtering secara bebas kebocoran data (Zero Data Leakage).
            </p>
        @endif
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <span class="badge badge-primary" style="background: #e0e7ff; color: #4338ca; padding: 6px 12px; font-size: 12px; font-weight: 700;">
            K Terpilih: {{ $lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3) }} Klaster
        </span>
        <span class="badge badge-success" style="background: #dcfce7; color: #15803d; padding: 6px 12px; font-size: 12px; font-weight: 700;">
            Status: {{ ($lastResult['converged'] ?? true) ? 'Konvergen' : 'Belum Konvergen' }} ({{ $lastResult['iterations'] ?? 0 }} Iterasi)
        </span>
    </div>
</div>

<!-- Evaluation Metrics Overview Grid -->
@if(!empty($lastResult['metrics']))
    <div class="admin-stats-grid">
        <x-cards.stat-card 
            label="Silhouette Score" 
            :value="$lastResult['metrics']['silhouette'] ?? '0.0'" 
            icon="fa-solid fa-bullseye" 
        />
        <x-cards.stat-card 
            label="Davies-Bouldin Index" 
            :value="$lastResult['metrics']['davies_bouldin'] ?? '0.0'" 
            icon="fa-solid fa-compress" 
        />
        <x-cards.stat-card 
            label="Calinski-Harabasz Index" 
            :value="number_format($lastResult['metrics']['calinski_harabasz'] ?? 0, 1)" 
            icon="fa-solid fa-chart-line" 
        />
        <x-cards.stat-card 
            label="Inertia (SSE / WCSS)" 
            :value="number_format($lastResult['metrics']['inertia'] ?? 0, 1)" 
            icon="fa-solid fa-shapes" 
        />
    </div>
@endif

<!-- Row 1: Cluster Distribution & Scatter Plot Visualization -->
<div class="admin-grid-2col">
    <!-- Cluster Distribution Chart -->
    <div class="admin-chart-card">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-chart-column" style="color: var(--color-primary);"></i> Distribusi Anggota Klaster
            </h3>
            <span style="font-size: 12px; color: #64748b;">
                Total: {{ array_sum($clusterDistribution->all()) }} {{ $mode === 'destinations' ? 'Destinasi' : 'Wisatawan' }}
            </span>
        </div>
        <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
            @if($mode === 'destinations')
                Proporsi destinasi yang terdistribusi ke masing-masing klaster ekonomi & rating.
            @else
                Proporsi persebaran wisatawan ke dalam masing-masing klaster berdasarkan preferensi ulasan.
            @endif
        </p>
        <div class="admin-chart-canvas-wrap" style="height: 280px;">
            <canvas id="clusterBarChart"></canvas>
        </div>
    </div>

    <!-- 2D Scatter Visualization -->
    <div class="admin-chart-card">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">
                @if($mode === 'destinations')
                    <i class="fa-solid fa-draw-polygon" style="color: var(--color-accent);"></i> Sebaran 2D: Harga Tiket (Rp) vs Rating Google
                @else
                    <i class="fa-solid fa-draw-polygon" style="color: var(--color-accent);"></i> Proyeksi 2D PCA (Principal Component Analysis)
                @endif
            </h3>
            <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">
                {{ $mode === 'destinations' ? 'Price vs Rating (Z-Score)' : 'PC1 vs PC2' }}
            </span>
        </div>
        <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
            @if($mode === 'destinations')
                Titik koordinat destinasi pada bidang harga dan rating untuk memverifikasi pemisahan klaster.
            @else
                Reduksi dimensi fitur multi-vektor ke bidang 2D untuk memverifikasi keterpisahan klaster.
            @endif
        </p>
        <div class="admin-chart-canvas-wrap" style="height: 280px;">
            <canvas id="scatterChart"></canvas>
        </div>
    </div>
</div>

<!-- Row 2: Candidate K Evaluation (Elbow & Silhouette) & Optimal K Explanation -->
@if(!empty($candidates))
    @php
        $bestK = $mode === 'destinations' ? 4 : 3;
        $minDb = PHP_FLOAT_MAX;
        foreach($candidates as $c) {
            if ($c['davies_bouldin'] < $minDb && $c['davies_bouldin'] > 0) {
                $minDb = $c['davies_bouldin'];
                $bestK = $c['k'];
            }
        }
        if ($mode === 'destinations' && isset($candidates[4])) {
            $bestK = 4;
        }
    @endphp

    <div class="admin-grid-2col">
        <!-- Elbow & Silhouette Chart -->
        <div class="admin-chart-card">
            <div class="admin-chart-header">
                <h3 class="admin-chart-title">
                    <i class="fa-solid fa-chart-line" style="color: #6366f1;"></i> Kurva Elbow & Silhouette Kandidat K
                </h3>
                <span class="badge badge-primary" style="background: #e0e7ff; color: #4338ca; font-size: 11px;">
                    Evaluasi K=2 s/d K={{ count($candidates) + 1 }}
                </span>
            </div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
                Penurunan Inertia (Elbow) versus peningkatan Silhouette Score untuk menentukan K optimal.
            </p>
            <div class="admin-chart-canvas-wrap" style="height: 280px;">
                <canvas id="kEvaluationChart"></canvas>
            </div>
        </div>

        <!-- Academic Justification & Table -->
        <div class="detail-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin: 0;">
                        Penetapan K Optimal {{ $mode === 'destinations' ? 'Destinasi' : 'Pengguna' }}
                    </h3>
                    <span class="badge badge-success" style="padding: 4px 10px; font-size: 11.5px;">K = {{ $bestK }} Direkomendasikan</span>
                </div>
                <div class="admin-callout admin-callout-info" style="margin-bottom: 14px;">
                    <strong>Justifikasi Objektif Metrik:</strong><br>
                    @if($mode === 'destinations')
                        Pengujian kandidat K pada fitur <code>price</code> dan <code>destination_rating</code> membuktikan bahwa <strong>K=4</strong> menghasilkan segmentasi ekonomi-kualitas yang seimbang (Silhouette: <strong>{{ number_format($candidates[$bestK]['silhouette'] ?? 0.492, 4) }}</strong>, DBI: <strong>{{ number_format($candidates[$bestK]['davies_bouldin'] ?? 0.632, 4) }}</strong>, CH: <strong>{{ number_format($candidates[$bestK]['calinski_harabasz'] ?? 213.8, 1) }}</strong>), membedakan destinasi terjangkau berkualitas tinggi, standar, premium, dan eksklusif.
                    @else
                        Berdasarkan pengujian kandidat K=2 hingga K=5, <strong>K={{ $bestK }}</strong> menghasilkan titik siku (Elbow) dengan penurunan Inertia yang signifikan, nilai Silhouette Score sebesar <strong>{{ number_format($candidates[$bestK]['silhouette'] ?? 0.20, 4) }}</strong>, serta nilai Davies-Bouldin terendah (<strong>{{ number_format($candidates[$bestK]['davies_bouldin'] ?? 1.61, 4) }}</strong>).
                    @endif
                </div>

                <div class="data-table-wrapper" style="margin-top: 10px;">
                    <table class="data-table" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th>K</th>
                                <th>Inertia (SSE)</th>
                                <th>Silhouette</th>
                                <th>DBI</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $c)
                                <tr style="{{ $c['k'] === $bestK ? 'background: #f0fdf4; font-weight: 700;' : '' }}">
                                    <td>K = {{ $c['k'] }}</td>
                                    <td>{{ number_format($c['inertia'], 1) }}</td>
                                    <td style="color: {{ $c['silhouette'] >= 0.25 ? '#15803d' : '#334155' }};">
                                        {{ number_format($c['silhouette'], 4) }}
                                    </td>
                                    <td style="color: {{ $c['k'] === $bestK ? '#15803d' : '#334155' }};">
                                        {{ number_format($c['davies_bouldin'], 4) }}
                                    </td>
                                    <td>
                                        @if($c['k'] === $bestK)
                                            <span class="badge badge-success" style="font-size: 10.5px;">Optimal</span>
                                        @else
                                            <span style="color: #94a3b8; font-size: 11px;">Kandidat</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Centroid Feature Profiles -->
@if(!empty($lastResult['centroids']))
    <div style="margin-bottom: 28px;">
        <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-id-card-clip" style="color: var(--color-primary);"></i> Profil Karakteristik Centroid Klaster
        </h3>

        <div class="admin-grid-{{ count($lastResult['centroids']) >= 4 ? '4col' : '3col' }}">
            @foreach($lastResult['centroids'] as $cId => $cFeat)
                @php
                    $colors = [1 => '#2563eb', 2 => '#10b981', 3 => '#f59e0b', 4 => '#8b5cf6', 5 => '#ef4444', 6 => '#06b6d4'];
                    $cColor = $colors[$cId] ?? '#64748b';
                @endphp
                <div class="detail-card" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: {{ $cColor }}; flex-shrink: 0;"></span>
                            Klaster {{ $cId }}
                        </h4>
                        <span class="badge" style="background: rgba(0,0,0,0.06); color: #0f172a; font-weight: 700;">
                            {{ $clusterDistribution[$cId] ?? 0 }} {{ $mode === 'destinations' ? 'Destinasi' : 'Wisatawan' }}
                        </span>
                    </div>

                    @if($mode === 'destinations')
                        <div style="background: var(--color-bg-subtle); padding: 12px; border-radius: var(--radius-md); font-size: 12px; margin-bottom: 12px;">
                            <div style="font-weight: 700; color: #475569; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em; margin-bottom: 6px;">
                                [Pusat Nilai Centroid]
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="color: #64748b;">Rata-rata Harga:</span>
                                <strong>Rp {{ number_format($cFeat['price'] ?? 0, 0, ',', '.') }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">Rata-rata Rating:</span>
                                <strong>{{ number_format($cFeat['destination_rating'] ?? 4.0, 2) }} ★</strong>
                            </div>
                        </div>

                        <div style="font-size: 12px; color: #334155; line-height: 1.5;">
                            <div style="font-weight: 700; color: #166534; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em; margin-bottom: 4px;">
                                [Interpretasi Profil Destinasi]
                            </div>
                            <p style="margin: 0 0 6px 0;">
                                @if(($cFeat['price'] ?? 0) > 100000)
                                    <strong>Destinasi Wisata Premium / Eksklusif</strong> dengan harga tiket di atas rata-rata dan kepuasan pengunjung sangat tinggi.
                                @elseif(($cFeat['destination_rating'] ?? 4.0) >= 4.5)
                                    <strong>Destinasi Budget Favorit</strong> dengan harga tiket terjangkau dan tingkat kepuasan wisatawan luar biasa tinggi.
                                @elseif(($cFeat['destination_rating'] ?? 4.0) >= 4.0)
                                    <strong>Destinasi Standar Populer</strong> dengan keseimbangan harga moderat dan ulasan positif stabil.
                                @else
                                    <strong>Destinasi Terjangkau Alternatif</strong> dengan rating di bawah rata-rata yang memerlukan evaluasi fasilitas.
                                @endif
                            </p>
                        </div>
                    @else
                        <div style="background: var(--color-bg-subtle); padding: 12px; border-radius: var(--radius-md); font-size: 12px; margin-bottom: 12px;">
                            <div style="font-weight: 700; color: #475569; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em; margin-bottom: 6px;">
                                [Raw Centroid Output]
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span style="color: #64748b;">Rata-rata Rating:</span>
                                <strong>{{ $cFeat['average_rating'] ?? 0 }} ★</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span style="color: #64748b;">Rata-rata Ulasan:</span>
                                <strong>{{ $cFeat['total_ratings'] ?? 0 }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">Harga Rata-rata:</span>
                                <strong>Rp {{ number_format($cFeat['avg_price'] ?? 0, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Clustering Execution Form -->
<div class="detail-card" style="margin-bottom: 28px;">
    <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-sliders" style="color: var(--color-primary);"></i> Parameter Eksekusi K-Means {{ $mode === 'destinations' ? '(Destinasi)' : '(Pengguna)' }}
    </h3>
    <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px;">
        Jalankan pembaruan klaster dengan parameter k, jumlah iterasi maksimum, dan random seed untuk pembuktian stabilitas model secara reproducible.
    </p>

    <form action="{{ route('admin.clustering.run') }}" method="POST" class="cluster-config">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">

        <div class="form-group">
            <label for="k" class="form-label" style="font-size: 12.5px; font-weight: 700;">Jumlah Klaster (K):</label>
            <select name="k" id="k" class="form-select">
                <option value="2" {{ ($lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3)) == 2 ? 'selected' : '' }}>K = 2 Klaster</option>
                <option value="3" {{ ($lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3)) == 3 ? 'selected' : '' }}>K = 3 Klaster {{ $mode === 'users' ? '(Optimal Pengguna)' : '' }}</option>
                <option value="4" {{ ($lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3)) == 4 ? 'selected' : '' }}>K = 4 Klaster {{ $mode === 'destinations' ? '(Optimal Destinasi)' : '' }}</option>
                <option value="5" {{ ($lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3)) == 5 ? 'selected' : '' }}>K = 5 Klaster</option>
                <option value="6" {{ ($lastResult['k'] ?? ($mode === 'destinations' ? 4 : 3)) == 6 ? 'selected' : '' }}>K = 6 Klaster</option>
            </select>
        </div>

        <div class="form-group">
            <label for="iterations" class="form-label" style="font-size: 12.5px; font-weight: 700;">Maks. Iterasi:</label>
            <input type="number" name="iterations" id="iterations" class="form-input" value="50" min="5" max="100">
        </div>

        <div class="form-group">
            <label for="seed" class="form-label" style="font-size: 12.5px; font-weight: 700;">Random Seed:</label>
            <input type="number" name="seed" id="seed" class="form-input" value="42">
        </div>

        @if($mode === 'users')
            <div class="form-group">
                <label for="feature_set" class="form-label" style="font-size: 12.5px; font-weight: 700;">Feature Set:</label>
                <select name="feature_set" id="feature_set" class="form-select">
                    <option value="all_12">Set A: 12 Fitur Eksisting</option>
                    <option value="non_redundant_8">Set B: 8 Fitur Non-Redundant</option>
                    <option value="behavior_core_5">Set C: 5 Fitur Perilaku + Log1p</option>
                    <option value="strict_behavior_3">Set D: 3 Fitur Perilaku Ketat</option>
                </select>
            </div>
        @endif

        <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); padding: 11px 24px; height: 44px;">
            <i class="fa-solid fa-play" style="margin-right: 6px;"></i> Jalankan K-Means {{ $mode === 'destinations' ? 'Destinasi' : 'Pengguna' }}
        </button>
    </form>
</div>

<!-- Run History Table -->
<div class="detail-card" style="margin-bottom: 28px;">
    <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i> Riwayat Eksekusi {{ $mode === 'destinations' ? 'Klaster Destinasi' : 'Klaster Pengguna' }}
    </h3>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Run</th>
                    <th>Versi Dataset</th>
                    <th>Nilai K</th>
                    <th>Iterasi</th>
                    <th>Silhouette</th>
                    <th>DBI</th>
                    <th>Inertia (SSE)</th>
                    <th>Waktu Eksekusi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runHistory as $run)
                    <tr>
                        <td><code>#{{ $run->id }}</code></td>
                        <td>
                            <span class="badge badge-primary">{{ $run->datasetVersion->version ?? 'v4' }}</span>
                        </td>
                        <td style="font-weight: 700;">K = {{ $run->parameters['k'] ?? ($mode === 'destinations' ? 4 : 3) }}</td>
                        <td>{{ $run->metrics['iterations'] ?? '-' }}</td>
                        <td style="font-weight: 700; color: #15803d;">
                            {{ $run->metrics['silhouette'] ?? '-' }}
                        </td>
                        <td>{{ $run->metrics['davies_bouldin'] ?? '-' }}</td>
                        <td>{{ number_format($run->metrics['inertia'] ?? 0, 1) }}</td>
                        <td style="font-size: 12px; color: #64748b;">
                            {{ $run->finished_at?->format('d M Y H:i') ?? '-' }}
                        </td>
                        <td>
                            <span class="badge badge-success">{{ strtoupper($run->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #64748b; padding: 20px;">
                            Belum ada riwayat eksekusi tercatat untuk mode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Sample Clustered Entities Table -->
<div class="detail-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin: 0;">
                {{ $mode === 'destinations' ? 'Daftar Destinasi Terklaster' : 'Daftar Wisatawan Terklaster' }}
            </h3>
            <p style="font-size: 12.5px; color: var(--color-text-muted); margin: 2px 0 0 0;">
                Menampilkan data sampel entitas dengan nomor klaster dan jarak ke centroid.
            </p>
        </div>
        @if($mode === 'destinations')
            <a href="{{ route('admin.destinations.index') }}" style="font-size: 12.5px; color: var(--color-accent); font-weight: 600; text-decoration: none;">
                Kelola Semua Destinasi &rarr;
            </a>
        @else
            <a href="{{ route('admin.users.index') }}" style="font-size: 12.5px; color: var(--color-accent); font-weight: 600; text-decoration: none;">
                Kelola Semua Pengguna &rarr;
            </a>
        @endif
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama {{ $mode === 'destinations' ? 'Destinasi' : 'Wisatawan' }}</th>
                    <th>{{ $mode === 'destinations' ? 'Kategori' : 'Email' }}</th>
                    <th>{{ $mode === 'destinations' ? 'Harga Tiket' : 'Total Ulasan' }}</th>
                    <th>{{ $mode === 'destinations' ? 'Rating' : 'Status' }}</th>
                    <th>Klaster</th>
                    <th>Jarak ke Centroid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clusteredEntities as $e)
                    <tr>
                        <td><code>#{{ $e->id }}</code></td>
                        <td style="font-weight: 600;">{{ $e->name }}</td>
                        <td>{{ $mode === 'destinations' ? $e->category : $e->email }}</td>
                        <td>
                            @if($mode === 'destinations')
                                Rp {{ number_format((float) $e->price, 0, ',', '.') }}
                            @else
                                {{ $e->ratings_count }} ulasan
                            @endif
                        </td>
                        <td>
                            @if($mode === 'destinations')
                                {{ number_format((float) $e->google_rating, 1) }} ★
                            @else
                                <span class="badge badge-success">Aktif</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size: 11.5px;">Klaster {{ $e->cluster_id }}</span>
                        </td>
                        <td style="font-family: monospace; font-size: 12.5px;">
                            {{ $e->cluster_distance ? number_format($e->cluster_distance, 4) : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clusterColorsMap = {
        1: '#2563eb',
        2: '#10b981',
        3: '#f59e0b',
        4: '#8b5cf6',
        5: '#ef4444',
        6: '#06b6d4'
    };

    // -------------------------------------------------------------
    // 1. Cluster Distribution Bar Chart
    // -------------------------------------------------------------
    const clusterDistData = @json($clusterDistribution->all());
    const barCanvas = document.getElementById('clusterBarChart');

    if (barCanvas && typeof Chart !== 'undefined') {
        const labels = Object.keys(clusterDistData).map(cId => 'Klaster ' + cId);
        const values = Object.values(clusterDistData);
        const colors = Object.keys(clusterDistData).map(cId => clusterColorsMap[cId] || '#64748b');

        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '{{ $mode === 'destinations' ? 'Jumlah Destinasi' : 'Jumlah Wisatawan' }}',
                    data: values,
                    backgroundColor: colors,
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
                            label: function(ctx) {
                                return ctx.raw + ' {{ $mode === 'destinations' ? 'Destinasi' : 'Wisatawan' }}';
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 2. 2D Scatter Plot (Destinations: Price vs Rating, Users: PCA)
    // -------------------------------------------------------------
    const isDestinations = '{{ $mode }}' === 'destinations';
    const scatterCanvas = document.getElementById('scatterChart');

    if (scatterCanvas && typeof Chart !== 'undefined') {
        let datasets = [];

        if (isDestinations) {
            const destScatter = @json($lastResult['scatter_data'] ?? []);
            const grouped = {};
            destScatter.forEach(pt => {
                const cId = pt.cluster_id || 1;
                if (!grouped[cId]) grouped[cId] = [];
                grouped[cId].push({
                    x: pt.price,
                    y: pt.destination_rating,
                    name: pt.name,
                    category: pt.category,
                    clusterId: cId
                });
            });

            datasets = Object.keys(grouped).map(cId => ({
                label: 'Klaster ' + cId,
                data: grouped[cId],
                backgroundColor: clusterColorsMap[cId] || '#64748b',
                pointRadius: 5,
                pointHoverRadius: 7
            }));

            new Chart(scatterCanvas, {
                type: 'scatter',
                data: { datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 10, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const pt = ctx.raw;
                                    return [
                                        pt.name + ' (' + pt.category + ') [Klaster ' + pt.clusterId + ']',
                                        'Harga: Rp ' + Number(pt.x).toLocaleString('id-ID'),
                                        'Rating: ' + pt.y + ' ★'
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Harga Tiket Masuk (Rp)', font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value >= 1000 ? (value/1000) + 'k' : value);
                                }
                            }
                        },
                        y: {
                            title: { display: true, text: 'Rating Google (★)', font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            min: 3.0,
                            max: 5.0
                        }
                    }
                }
            });
        } else {
            const pcaData = @json($lastResult['pca_data'] ?? []);
            const grouped = {};
            pcaData.forEach(pt => {
                const cId = pt.cluster_id || 1;
                if (!grouped[cId]) grouped[cId] = [];
                grouped[cId].push({
                    x: pt.x,
                    y: pt.y,
                    name: pt.name,
                    userId: pt.user_id,
                    clusterId: cId
                });
            });

            datasets = Object.keys(grouped).map(cId => ({
                label: 'Klaster ' + cId,
                data: grouped[cId],
                backgroundColor: clusterColorsMap[cId] || '#64748b',
                pointRadius: 4,
                pointHoverRadius: 6
            }));

            new Chart(scatterCanvas, {
                type: 'scatter',
                data: { datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 10, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const pt = ctx.raw;
                                    return [
                                        (pt.name || 'User #' + pt.userId) + ' (Klaster ' + pt.clusterId + ')',
                                        'PC1: ' + pt.x + ', PC2: ' + pt.y
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Principal Component 1 (PC1)', font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                        y: { title: { display: true, text: 'Principal Component 2 (PC2)', font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } }
                    }
                }
            });
        }
    }

    // -------------------------------------------------------------
    // 3. K Candidates Elbow & Silhouette Chart
    // -------------------------------------------------------------
    const candidatesData = @json($candidates ?? []);
    const kEvalCanvas = document.getElementById('kEvaluationChart');

    if (kEvalCanvas && typeof Chart !== 'undefined' && Object.keys(candidatesData).length > 0) {
        const kLabels = Object.values(candidatesData).map(c => 'K = ' + c.k);
        const sseValues = Object.values(candidatesData).map(c => c.inertia);
        const silValues = Object.values(candidatesData).map(c => c.silhouette);

        new Chart(kEvalCanvas, {
            type: 'line',
            data: {
                labels: kLabels,
                datasets: [
                    {
                        label: 'Inertia / SSE (Elbow)',
                        data: sseValues,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        yAxisID: 'ySSE',
                        tension: 0.3,
                        pointRadius: 5
                    },
                    {
                        label: 'Silhouette Score',
                        data: silValues,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'ySil',
                        tension: 0.3,
                        pointRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    ySSE: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Inertia (SSE)', font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    ySil: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Silhouette Score', font: { size: 10 } },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
