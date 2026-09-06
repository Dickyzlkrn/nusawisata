@extends('layouts.admin')

@section('title', 'Dokumentasi Sistem & Panduan Riset')
@section('page-title', 'Dokumentasi Sistem & Metodologi')

@section('content')
<div class="admin-page-header" style="margin-bottom: 32px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
            Panduan Teknis Sistem
        </div>
        <h1 style="font-size: 24px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 6px 0;">
            Buku Panduan Teknis & Dokumentasi Riset NusaWisata
        </h1>
        <p style="color: var(--color-text-muted); font-size: 14px; margin: 0; max-width: 800px; line-height: 1.6;">
            Panduan komprehensif bagi administrator mengenai arsitektur sistem, skema basis data, siklus pelatihan algoritma K-Means, dan evaluasi Collaborative Filtering.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('documentation') }}" target="_blank" class="btn-outline" style="border-radius: var(--radius-pill); font-size: 13px;">
            Dokumentasi Publik &rarr;
        </a>
    </div>
</div>

<!-- 1. STATISTIK SISTEM AKTIF (CLEAN TYPOGRAPHY) -->
<div class="admin-stats-grid" style="margin-bottom: 32px;">
    <div class="stat-card">
        <div class="stat-value" style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; font-family: monospace;">
            {{ number_format($totalDestinations) }}
        </div>
        <div class="stat-label" style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
            Objek Wisata
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; font-family: monospace;">
            {{ $totalProvinces }}
        </div>
        <div class="stat-label" style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
            Provinsi Terdaftar
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; font-family: monospace;">
            {{ number_format($totalRatings) }}
        </div>
        <div class="stat-label" style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
            Total Ulasan Masuk
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; font-family: monospace;">
            {{ $matrixStats['sparsity_percent'] ?? 97.4 }}%
        </div>
        <div class="stat-label" style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
            Sparsitas Matriks
        </div>
    </div>
</div>

<!-- 2. DIAGRAM ARSITEKTUR RELASI ENTITAS (DATABASE ERD) -->
<div class="detail-card" style="margin-bottom: 32px;">
    <div style="margin-bottom: 18px;">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 4px;">
            Skema Data
        </div>
        <h3 style="font-size: 17px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 4px 0;">
            Diagram Arsitektur Relasi Basis Data (ERD)
        </h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin: 0;">
            Visualisasi relasi antar tabel utama yang menopang sistem rekomendasi NusaWisata.
        </p>
    </div>

    <!-- Clean Technical SVG ERD Diagram -->
    <div style="background: #0f172a; border-radius: var(--radius-md); padding: 24px; overflow-x: auto;">
        <svg viewBox="0 0 850 240" width="100%" height="240" preserveAspectRatio="xMidYMid meet" style="min-width: 750px;">
            <defs>
                <marker id="erdArrow" markerWidth="7" markerHeight="7" refX="5" refY="3.5" orient="auto">
                    <polygon points="0 0, 7 3.5, 0 7" fill="#94a3b8" />
                </marker>
            </defs>

            <!-- Table: PROVINCES -->
            <g transform="translate(30, 30)">
                <rect width="160" height="150" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1" />
                <rect width="160" height="32" rx="6" fill="#334155" />
                <text x="80" y="21" fill="#f8fafc" font-size="12" font-weight="600" text-anchor="middle">provinces</text>
                <text x="14" y="55" fill="#38bdf8" font-size="11" font-family="monospace">PK id (BIGINT)</text>
                <text x="14" y="75" fill="#cbd5e1" font-size="11" font-family="monospace">name (VARCHAR)</text>
                <text x="14" y="95" fill="#cbd5e1" font-size="11" font-family="monospace">slug (VARCHAR)</text>
                <text x="14" y="115" fill="#cbd5e1" font-size="11" font-family="monospace">latitude / long</text>
                <text x="14" y="135" fill="#cbd5e1" font-size="11" font-family="monospace">island (VARCHAR)</text>
            </g>

            <!-- Relation 1: provinces -> destinations -->
            <path d="M 190 90 L 250 90" fill="none" stroke="#64748b" stroke-width="1.5" marker-end="url(#erdArrow)" />
            <text x="220" y="80" fill="#94a3b8" font-size="10" text-anchor="middle">1 : N</text>

            <!-- Table: DESTINATIONS -->
            <g transform="translate(250, 20)">
                <rect width="170" height="180" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1" />
                <rect width="170" height="32" rx="6" fill="#334155" />
                <text x="85" y="21" fill="#f8fafc" font-size="12" font-weight="600" text-anchor="middle">destinations</text>
                <text x="14" y="55" fill="#38bdf8" font-size="11" font-family="monospace">PK id (BIGINT)</text>
                <text x="14" y="73" fill="#fcd34d" font-size="11" font-family="monospace">FK province_id</text>
                <text x="14" y="91" fill="#cbd5e1" font-size="11" font-family="monospace">name (VARCHAR)</text>
                <text x="14" y="109" fill="#cbd5e1" font-size="11" font-family="monospace">category (VARCHAR)</text>
                <text x="14" y="127" fill="#cbd5e1" font-size="11" font-family="monospace">price (DECIMAL)</text>
                <text x="14" y="145" fill="#cbd5e1" font-size="11" font-family="monospace">google_rating</text>
                <text x="14" y="163" fill="#cbd5e1" font-size="11" font-family="monospace">place_id (UNIQUE)</text>
            </g>

            <!-- Relation 2: destinations -> ratings -->
            <path d="M 420 90 L 480 90" fill="none" stroke="#64748b" stroke-width="1.5" marker-end="url(#erdArrow)" />
            <text x="450" y="80" fill="#94a3b8" font-size="10" text-anchor="middle">1 : N</text>

            <!-- Table: RATINGS -->
            <g transform="translate(480, 30)">
                <rect width="160" height="150" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1" />
                <rect width="160" height="32" rx="6" fill="#334155" />
                <text x="80" y="21" fill="#f8fafc" font-size="12" font-weight="600" text-anchor="middle">ratings</text>
                <text x="14" y="55" fill="#38bdf8" font-size="11" font-family="monospace">PK id (BIGINT)</text>
                <text x="14" y="75" fill="#fcd34d" font-size="11" font-family="monospace">FK destination_id</text>
                <text x="14" y="95" fill="#fcd34d" font-size="11" font-family="monospace">FK user_id</text>
                <text x="14" y="115" fill="#cbd5e1" font-size="11" font-family="monospace">rating (1..5)</text>
                <text x="14" y="135" fill="#cbd5e1" font-size="11" font-family="monospace">comment (TEXT)</text>
            </g>

            <!-- Relation 3: users -> ratings -->
            <path d="M 680 90 L 640 90" fill="none" stroke="#64748b" stroke-width="1.5" marker-end="url(#erdArrow)" />
            <text x="660" y="80" fill="#94a3b8" font-size="10" text-anchor="middle">N : 1</text>

            <!-- Table: USERS -->
            <g transform="translate(680, 20)">
                <rect width="140" height="180" rx="6" fill="#1e293b" stroke="#334155" stroke-width="1" />
                <rect width="140" height="32" rx="6" fill="#334155" />
                <text x="70" y="21" fill="#f8fafc" font-size="12" font-weight="600" text-anchor="middle">users</text>
                <text x="12" y="55" fill="#38bdf8" font-size="11" font-family="monospace">PK id (BIGINT)</text>
                <text x="12" y="73" fill="#cbd5e1" font-size="11" font-family="monospace">name (VARCHAR)</text>
                <text x="12" y="91" fill="#cbd5e1" font-size="11" font-family="monospace">email (UNIQUE)</text>
                <text x="12" y="109" fill="#cbd5e1" font-size="11" font-family="monospace">role ('admin'/'user')</text>
                <text x="12" y="127" fill="#cbd5e1" font-size="11" font-family="monospace">cluster_id (INT)</text>
                <text x="12" y="145" fill="#cbd5e1" font-size="11" font-family="monospace">cluster_distance</text>
                <text x="12" y="163" fill="#cbd5e1" font-size="11" font-family="monospace">timestamps</text>
            </g>
        </svg>
    </div>
</div>

<!-- 3. SIKLUS RETRAINING ALGORITMA (EDITORIAL 4 PHASES) -->
<div class="detail-card" style="margin-bottom: 32px;">
    <div style="margin-bottom: 18px;">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 4px;">
            Pipeline ML
        </div>
        <h3 style="font-size: 17px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 4px 0;">
            Siklus Pelatihan Algoritma (Lifecycle & Retraining Pipeline)
        </h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin: 0;">
            Empat tahapan berkala untuk memperbarui model klasterisasi dan sinkronisasi preferensi wisatawan.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        
        <!-- Phase 1 -->
        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 18px; border-radius: var(--radius-md);">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 6px; font-family: monospace;">Fase 01</div>
            <h4 style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 6px 0;">Ingesti & Validasi</h4>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                Data dari berkas Excel atau crawler dipetakan ke tabel <code>destinations</code> dan <code>ratings</code> dengan sanitasi <code>place_id</code> unik.
            </p>
        </div>

        <!-- Phase 2 -->
        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 18px; border-radius: var(--radius-md);">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 6px; font-family: monospace;">Fase 02</div>
            <h4 style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 6px 0;">Ekstraksi 12 Dimensi</h4>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                Mengekstrak profil ulasan per pengguna: rata-rata rating, deviasi standar, sebaran bintang, preferensi wilayah dan harga, lalu distandarisasi Z-Score.
            </p>
        </div>

        <!-- Phase 3 -->
        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 18px; border-radius: var(--radius-md);">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 6px; font-family: monospace;">Fase 03</div>
            <h4 style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 6px 0;">K-Means Execution</h4>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                Inisialisasi K-Means++, iterasi penetapan titik Euclidean hingga konvergensi (&lt; 0.0001). Evaluasi DBI untuk validasi nilai K terbaik.
            </p>
        </div>

        <!-- Phase 4 -->
        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 18px; border-radius: var(--radius-md);">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 6px; font-family: monospace;">Fase 04</div>
            <h4 style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 6px 0;">Database Update</h4>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                Kolom <code>cluster_id</code> dan <code>cluster_distance</code> pengguna diperbarui. Log ringkasan metrik disimpan ke tabel <code>ml_runs</code>.
            </p>
        </div>

    </div>
</div>

<!-- 4. PANDUAN INTERPRETASI METRIK AKADEMIK -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    
    <!-- K-Means Metrics Guide -->
    <div class="detail-card">
        <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 14px 0;">
            Panduan Metrik Evaluasi K-Means
        </h3>
        
        <div style="margin-bottom: 16px;">
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">1. Davies-Bouldin Index (DBI)</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Mengukur rasio penyebaran dalam klaster terhadap pemisahan antar-klaster. Nilai lebih kecil menunjukkan kualitas pemisahan lebih baik. K = 3 menghasilkan DBI terendah (1.6173) dibanding K lain.
            </p>
        </div>

        <div style="margin-bottom: 16px;">
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">2. Silhouette Coefficient</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Mengukur kedekatan titik dalam klasternya dibanding klaster tetangga (rentang -1 hingga +1). Nilai positif membuktikan titik terpetakan pada klaster yang tepat.
            </p>
        </div>

        <div>
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">3. Inertia / SSE (Sum of Squared Errors)</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Jumlah kuadrat jarak sampel ke centroid terdekatnya. Penurunan kurva yang melandai (Elbow Method) menandai nilai K optimal.
            </p>
        </div>
    </div>

    <!-- CF Evaluation Guide -->
    <div class="detail-card">
        <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 14px 0;">
            Panduan Evaluasi Collaborative Filtering
        </h3>

        <div style="margin-bottom: 16px;">
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">1. 80/20 Train-Test Validation</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Data ulasan dibagi 80% data latih dan 20% data uji. Prediksi diuji terhadap rating nyata wisatawan untuk mengukur simpangan galat.
            </p>
        </div>

        <div style="margin-bottom: 16px;">
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">2. Mean Absolute Error (MAE)</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Rata-rata selisih absolut antara prediksi dan rating aktual. Nilai di bawah 1.0 mengindikasikan deviasi kesalahan kurang dari 1 skala bintang.
            </p>
        </div>

        <div>
            <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text-main);">3. Root Mean Squared Error (RMSE)</div>
            <p style="font-size: 12.5px; color: var(--color-text-muted); line-height: 1.5; margin: 4px 0 0 0;">
                Akar dari rata-rata kuadrat galat prediksi yang memberikan penalti lebih besar pada selisih skor ekstrem untuk menjamin konsistensi.
            </p>
        </div>
    </div>

</div>

<!-- 5. RIWAYAT EKSEKUSI TRAINING TERAKHIR (ML RUNS LOG) -->
<div class="detail-card" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0;">
                Log Eksekusi Algoritma Terkini (Tabel ml_runs)
            </h3>
        </div>
        <span style="font-size: 12px; color: var(--color-text-muted); font-family: monospace;">Audit Trail ML</span>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Algoritma</th>
                    <th>Parameter</th>
                    <th>Metrik Evaluasi</th>
                    <th>Waktu Eksekusi</th>
                    <th>Tanggal Eksekusi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentMlRuns as $run)
                    <tr>
                        <td style="font-family: monospace; font-weight: 600; color: var(--color-primary-dark);">
                            #{{ $run->id }}
                        </td>
                        <td>
                            @if($run->algorithm === 'kmeans')
                                <span style="font-weight: 600; color: var(--color-primary-dark);">K-Means Clustering</span>
                            @else
                                <span style="font-weight: 600; color: var(--color-primary-dark);">Collaborative Filtering</span>
                            @endif
                        </td>
                        <td style="font-size: 12.5px;">
                            @if(!empty($run->parameters['k']))
                                K = {{ $run->parameters['k'] }} (iter: {{ $run->parameters['max_iterations'] ?? 300 }})
                            @elseif(!empty($run->parameters['top_n']))
                                Top-N = {{ $run->parameters['top_n'] }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="font-size: 12.5px; font-family: monospace;">
                            @if($run->algorithm === 'kmeans' && !empty($run->metrics))
                                DBI: {{ $run->metrics['davies_bouldin'] ?? '-' }} | Sil: {{ $run->metrics['silhouette'] ?? '-' }}
                            @elseif(!empty($run->metrics))
                                MAE: {{ $run->metrics['mae'] ?? '-' }} | RMSE: {{ $run->metrics['rmse'] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 12px;">
                            @if($run->started_at && $run->finished_at)
                                {{ $run->started_at->diffInMilliseconds($run->finished_at) }} ms
                            @else
                                {{ $run->status }}
                            @endif
                        </td>
                        <td style="color: var(--color-text-muted); font-size: 12px;">
                            {{ $run->created_at->format('d M Y, H:i:s') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                            Belum ada riwayat eksekusi algoritma di tabel <code>ml_runs</code>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 6. PANDUAN PERINTAH CLI ARTISAN (CLI COMMAND REFERENCE) -->
<div class="detail-card">
    <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 14px 0;">
        Referensi Perintah CLI Artisan
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 4px;">Import Dataset Otomatis</div>
            <code style="font-size: 13px; color: var(--color-primary-dark); font-weight: 600;">php artisan dataset:import</code>
            <p style="font-size: 12px; color: var(--color-text-muted); margin: 6px 0 0 0;">
                Membaca file <code>database/seeders/data/destinasi_wisata_indonesia_2000.xlsx</code> dan memperbarui tabel destinasi & ratings.
            </p>
        </div>

        <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 14px 16px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; margin-bottom: 4px;">Training K-Means CLI</div>
            <code style="font-size: 13px; color: var(--color-primary-dark); font-weight: 600;">php artisan ml:cluster --k=3</code>
            <p style="font-size: 12px; color: var(--color-text-muted); margin: 6px 0 0 0;">
                Mengekstrak fitur, menjalankan K-Means dengan K = 3, dan memperbarui <code>cluster_id</code> semua pengguna.
            </p>
        </div>
    </div>
</div>
@endsection
