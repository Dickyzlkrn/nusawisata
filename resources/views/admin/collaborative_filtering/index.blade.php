@extends('layouts.admin')

@section('title', 'Collaborative Filtering Panel')
@section('page-title', 'Collaborative Filtering')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Analisis User-Based Collaborative Filtering</h1>
        <p>
            Sistem rekomendasi berbasis perhitungan kemiripan vektor ulasan (<strong>Cosine Similarity</strong>) di dalam klaster preferensi wisatawan.
        </p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <span class="badge badge-primary" style="background: #e0e7ff; color: #4338ca; padding: 6px 12px; font-size: 12px; font-weight: 700;">
            Metode: User-Based CF
        </span>
        <span class="badge badge-info" style="background: #e0f2fe; color: #0369a1; padding: 6px 12px; font-size: 12px; font-weight: 700;">
            Metrik Kemiripan: Cosine Similarity
        </span>
    </div>
</div>

<!-- Matrix Statistics Grid -->
<div class="admin-stats-grid">
    <x-cards.stat-card 
        label="Total Wisatawan Aktif" 
        :value="number_format($matrixStats['total_users'])" 
        icon="fa-solid fa-users" 
    />
    <x-cards.stat-card 
        label="Destinasi Dinilai" 
        :value="number_format($matrixStats['total_destinations'])" 
        icon="fa-solid fa-map-location-dot" 
    />
    <x-cards.stat-card 
        label="Total Ulasan Masuk" 
        :value="number_format($matrixStats['total_ratings'])" 
        icon="fa-solid fa-star" 
    />
    <x-cards.stat-card 
        label="Tingkat Kerapatan (Density)" 
        :value="$matrixStats['density_percent'] . '%'" 
        icon="fa-solid fa-chart-simple" 
    />
</div>

<!-- Row 1: Rating Distribution Chart & Matrix Sparsity / Accuracy Card -->
<div class="admin-grid-2col">
    <!-- Left: Rating Distribution Chart -->
    <div class="admin-chart-card">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-star-half-stroke" style="color: #f59e0b;"></i> Distribusi Penilaian Wisatawan (Place_Ratings)
            </h3>
            <span style="font-size: 12px; color: #64748b;">Total: {{ number_format(array_sum($ratingDistribution)) }} Ulasan</span>
        </div>
        <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
            Sebaran skor rating 1★ hingga 5★ yang diberikan wisatawan pada dataset aktif.
        </p>
        <div class="admin-chart-canvas-wrap" style="height: 280px;">
            <canvas id="cfRatingDistributionChart"></canvas>
        </div>
    </div>

    <!-- Right: Matrix Sparsity & Model Accuracy Widget -->
    <div class="detail-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <h3 style="color: #fff; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-info" style="color: #60a5fa;"></i> Karakteristik Matriks & Evaluasi Akurasi
                </h3>
                <form action="{{ route('admin.collaborative_filtering.evaluate') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-primary" style="padding: 5px 14px; font-size: 11.5px; border-radius: var(--radius-pill); background: #3b82f6;">
                        <i class="fa-solid fa-arrows-rotate"></i> Uji Ulang (80:20)
                    </button>
                </form>
            </div>

            <p style="font-size: 12.5px; color: rgba(255, 255, 255, 0.85); line-height: 1.6; margin-bottom: 14px;">
                Matriks ulasan berukuran <strong>{{ number_format($matrixStats['matrix_size']) }} sel</strong> dengan tingkat kelangkaan (<strong>Sparsity</strong>) sebesar <strong>{{ $matrixStats['sparsity_percent'] }}%</strong>.
            </p>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px;">
                <div style="background: rgba(255, 255, 255, 0.08); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="font-size: 11px; color: rgba(255, 255, 255, 0.65);">Sparsity Matriks</div>
                    <div style="font-size: 20px; font-weight: 800; color: #f59e0b;">{{ $matrixStats['sparsity_percent'] }}%</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.08); padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="font-size: 11px; color: rgba(255, 255, 255, 0.65);">Rata-rata Ulasan / User</div>
                    <div style="font-size: 20px; font-weight: 800; color: #38bdf8;">{{ $matrixStats['avg_ratings_per_user'] }}</div>
                </div>
            </div>

            @if($evaluationResult)
                <div style="background: rgba(0, 0, 0, 0.25); padding: 12px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">
                        Hasil Evaluasi Model (Hold-out Test 20%)
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; text-align: center;">
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 6px; border-radius: 6px;">
                            <div style="font-size: 10px; color: #94a3b8;">MAE</div>
                            <div style="font-size: 15px; font-weight: 800; color: #67e8f9;">{{ $evaluationResult['mae'] ?? '-' }}</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 6px; border-radius: 6px;">
                            <div style="font-size: 10px; color: #94a3b8;">RMSE</div>
                            <div style="font-size: 15px; font-weight: 800; color: #67e8f9;">{{ $evaluationResult['rmse'] ?? '-' }}</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 6px; border-radius: 6px;">
                            <div style="font-size: 10px; color: #94a3b8;">Precision@5</div>
                            <div style="font-size: 15px; font-weight: 800; color: #4ade80;">{{ $evaluationResult['precision_at_k'] ?? 0 }}%</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 6px; border-radius: 6px;">
                            <div style="font-size: 10px; color: #94a3b8;">Recall@5</div>
                            <div style="font-size: 15px; font-weight: 800; color: #4ade80;">{{ $evaluationResult['recall_at_k'] ?? 0 }}%</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div style="margin-top: 14px; font-size: 11.5px; color: #93c5fd;">
            Formula Prediksi: <code>p(u, i) = &sum;(sim(u, v) &times; r(v, i)) / &sum;|sim(u, v)|</code>
        </div>
    </div>
</div>

<!-- Simulation Controls -->
<div class="detail-card" style="margin-bottom: 28px;">
    <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-flask" style="color: var(--color-primary);"></i> Simulator Rekomendasi Wisatawan
    </h3>
    <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 18px;">
        Pilih profil wisatawan untuk menguji pencarian tetangga terdekat (Nearest Neighbors), kemiripan Cosine Similarity, dan perankingan destinasi yang diprediksi.
    </p>

    <form action="{{ route('admin.collaborative_filtering.simulate') }}" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 14px; align-items: flex-end;">
        @csrf
        <div class="form-group" style="margin: 0;">
            <label for="user_id" class="form-label" style="font-size: 12.5px; font-weight: 700;">Pilih Pengguna Target:</label>
            <select name="user_id" id="user_id" class="form-select">
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $u->id == $selectedUserId ? 'selected' : '' }}>
                        #{{ $u->id }} - {{ $u->name }} (Klaster {{ $u->cluster_id ?? 'Belum' }} | {{ $u->ratings_count }} ulasan)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label for="neighbors" class="form-label" style="font-size: 12.5px; font-weight: 700;">Tetangga Terdekat (Top-N):</label>
            <input type="number" name="neighbors" id="neighbors" class="form-input" value="{{ $topNNeighbors }}" min="1" max="30">
        </div>

        <div class="form-group" style="margin: 0;">
            <label for="limit" class="form-label" style="font-size: 12.5px; font-weight: 700;">Jumlah Rekomendasi (Top-K):</label>
            <input type="number" name="limit" id="limit" class="form-input" value="{{ $topKLimit }}" min="1" max="20">
        </div>

        <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); height: 44px; padding: 0 24px;">
            <i class="fa-solid fa-calculator" style="margin-right: 6px;"></i> Hitung Rekomendasi
        </button>
    </form>
</div>

<!-- Simulation Results & Visualizations -->
@if($selectedUser)
    <!-- Target User Preference Profile Card -->
    <div class="detail-card" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">
                    Profil Preferensi: {{ $selectedUser->name }} (ID #{{ $selectedUser->id }})
                </h4>
                <div style="font-size: 12.5px; color: #64748b; margin-top: 2px;">
                    Email: <code>{{ $selectedUser->email }}</code> &bull; Anggota Klaster: <strong>Klaster {{ $selectedUser->cluster_id ?? 'Belum terklaster' }}</strong>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <div style="text-align: center; background: #f8fafc; padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 11px; color: #64748b;">Total Ulasan</div>
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">{{ $userRatings->count() }}</div>
                </div>
                <div style="text-align: center; background: #f8fafc; padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 11px; color: #64748b;">Rata-rata Rating</div>
                    <div style="font-size: 16px; font-weight: 800; color: #f59e0b;">
                        ★ {{ number_format($userRatings->avg('rating') ?: 0, 1) }}
                    </div>
                </div>
            </div>
        </div>

        @if($userCategoryPrefs->isNotEmpty())
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 8px;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Kategori Paling Sering Dinilai:</span>
                @foreach($userCategoryPrefs as $cp)
                    <span class="badge badge-primary" style="font-size: 11px; padding: 3px 8px;">
                        {{ $cp->category }} ({{ $cp->count }} ulasan &bull; ★{{ number_format($cp->avg_rating, 1) }})
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @if($simulationResult)
        <!-- Row 2: Nearest Neighbors Similarity Chart & Predicted Ratings Chart -->
        <div class="admin-grid-2col" style="margin-bottom: 28px;">
            <!-- Left: Nearest Neighbors Cosine Similarity -->
            <div class="admin-chart-card">
                <div class="admin-chart-header">
                    <h3 class="admin-chart-title">
                        <i class="fa-solid fa-users-between-lines" style="color: #2563eb;"></i> Skor Kemiripan Tetangga (Cosine Similarity)
                    </h3>
                    <span class="badge badge-primary" style="font-size: 11px;">Top-{{ count($simulationResult['neighbors_used']) }} Neighbors</span>
                </div>
                <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
                    Tingkat kemiripan pola penilaian wisatawan pembanding di Klaster {{ $simulationResult['cluster_id'] ?? 'Umum' }}.
                </p>
                <div class="admin-chart-canvas-wrap" style="height: 260px;">
                    <canvas id="neighborSimilarityChart"></canvas>
                </div>
            </div>

            <!-- Right: Predicted Rating for Candidates -->
            <div class="admin-chart-card">
                <div class="admin-chart-header">
                    <h3 class="admin-chart-title">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color: #10b981;"></i> Nilai Prediksi Rating Destinasi (Top-K)
                    </h3>
                    <span class="badge badge-success" style="font-size: 11px;">Skor 1.0 - 5.0</span>
                </div>
                <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 12px;">
                    Hasil kalkulasi Weighted Sum Rating berdasarkan ulasan tetangga terdekat.
                </p>
                <div class="admin-chart-canvas-wrap" style="height: 260px;">
                    <canvas id="predictedRatingsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recommendation Traceability & Explanation Card -->
        <div class="detail-card" style="margin-bottom: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <h3 style="font-size: 17px; font-weight: 700; color: var(--color-primary-dark); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: #6366f1;"></i> Rantai Penelusuran Rekomendasi (Traceability)
                </h3>
                <span style="font-size: 12px; color: #64748b;">
                    {{ count($simulationResult['recommendations']) }} Destinasi Direkomendasikan
                </span>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peringkat</th>
                            <th>Destinasi Wisata</th>
                            <th>Provinsi</th>
                            <th>Kategori</th>
                            <th>Prediksi Rating</th>
                            <th>Tetangga Pendukung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($simulationResult['recommendations'] as $dest)
                            @php
                                $destModel = $dest instanceof \App\Models\Destination ? $dest : ($dest['destination'] ?? null);
                                $destName = $destModel?->name ?? ($dest['name'] ?? 'Destinasi');
                                $destProv = $destModel?->province?->name ?? ($dest['province'] ?? '-');
                                $destCat = $destModel?->category ?? ($dest['category'] ?? 'Wisata Alam');
                                $predRating = $destModel?->predicted_rating ?? ($dest['predicted_rating'] ?? 4.5);
                                $rank = $destModel?->recommendation_rank ?? ($dest['rank'] ?? $loop->iteration);
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 800; font-size: 12px; border-radius: 50%; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                        {{ $rank }}
                                    </span>
                                </td>
                                <td style="font-weight: 700;">
                                    @if($destModel)
                                        <a href="{{ route('destinations.show', $destModel) }}" target="_blank" style="color: var(--color-primary-dark); text-decoration: none;">
                                            {{ $destName }}
                                        </a>
                                    @else
                                        {{ $destName }}
                                    @endif
                                </td>
                                <td>{{ $destProv }}</td>
                                <td>
                                    <span class="badge badge-primary">{{ $destCat }}</span>
                                </td>
                                <td>
                                    <strong style="color: #2563eb; font-size: 15px;">{{ number_format((float)$predRating, 2) }}</strong>
                                    <span style="color: #f59e0b; margin-left: 2px;">★</span>
                                </td>
                                <td style="font-size: 12.5px; color: #64748b;">
                                    <i class="fa-solid fa-user-group" style="color: #10b981; margin-right: 4px;"></i>
                                    {{ count($simulationResult['neighbors_used'] ?? []) }} tetangga serupa
                                </td>
                                <td>
                                    @if($destModel)
                                        <a href="{{ route('destinations.show', $destModel) }}" target="_blank" class="btn-outline" style="font-size: 11.5px; padding: 4px 10px; border-radius: var(--radius-sm); text-decoration: none;">
                                            Buka &rarr;
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b; padding: 20px;">
                                    Tidak ada rekomendasi destinasi yang memenuhi kriteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. CF Rating Distribution Bar Chart
    // -------------------------------------------------------------
    const ratingDist = @json($ratingDistribution);
    const ratingCanvas = document.getElementById('cfRatingDistributionChart');

    if (ratingCanvas && typeof Chart !== 'undefined') {
        const labels = ['1 Bintang', '2 Bintang', '3 Bintang', '4 Bintang', '5 Bintang'];
        const values = [
            ratingDist[1] || 0,
            ratingDist[2] || 0,
            ratingDist[3] || 0,
            ratingDist[4] || 0,
            ratingDist[5] || 0
        ];

        new Chart(ratingCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Ulasan',
                    data: values,
                    backgroundColor: [
                        '#ef4444',
                        '#f97316',
                        '#facc15',
                        '#60a5fa',
                        '#22c55e'
                    ],
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
                            label: function(ctx) { return ctx.raw + ' Ulasan'; }
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
    // 2. Nearest Neighbors Cosine Similarity Horizontal Bar Chart
    // -------------------------------------------------------------
    const neighborsData = @json($simulationResult['neighbors_used'] ?? []);
    const neighborCanvas = document.getElementById('neighborSimilarityChart');

    if (neighborCanvas && typeof Chart !== 'undefined' && neighborsData.length > 0) {
        const nLabels = neighborsData.map(n => n.name + ' (#' + n.user_id + ')');
        const nScores = neighborsData.map(n => n.similarity);

        new Chart(neighborCanvas, {
            type: 'bar',
            data: {
                labels: nLabels,
                datasets: [{
                    label: 'Cosine Similarity',
                    data: nScores,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderRadius: 5,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return 'Kemiripan Cosine: ' + ctx.raw; }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 1.0,
                        ticks: { stepSize: 0.2 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. Predicted Ratings Horizontal Bar Chart
    // -------------------------------------------------------------
    const recsData = @json($simulationResult['recommendations'] ?? []);
    const recCanvas = document.getElementById('predictedRatingsChart');

    if (recCanvas && typeof Chart !== 'undefined' && recsData.length > 0) {
        const rLabels = recsData.map(r => r.name || (r.destination ? r.destination.name : 'Destinasi'));
        const rScores = recsData.map(r => parseFloat(r.predicted_rating || (r.destination ? r.destination.predicted_rating : 4.0)).toFixed(2));

        new Chart(recCanvas, {
            type: 'bar',
            data: {
                labels: rLabels,
                datasets: [{
                    label: 'Predicted Rating',
                    data: rScores,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 5,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return 'Prediksi Skor: ★ ' + ctx.raw; }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 1.0,
                        max: 5.0,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
