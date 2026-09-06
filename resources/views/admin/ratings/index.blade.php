@extends('layouts.admin')

@section('title', 'Moderasi Rating & Ulasan')
@section('page-title', 'Rating & Ulasan')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Moderasi Rating & Ulasan Wisatawan</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Pantau dan kelola umpan balik pengguna yang digunakan oleh algoritma Collaborative Filtering.</p>
    </div>
</div>

<div class="admin-chart-card" style="margin-bottom: 24px;">
    <div class="admin-chart-header">
        <h3 class="admin-chart-title">
            <i class="fa-solid fa-star-half-stroke" style="color: #f59e0b;"></i> Distribusi Penilaian Wisatawan (1★ s/d 5★)
        </h3>
        <span style="font-size: 12px; color: #64748b;">Total: {{ number_format(array_sum($ratingDistribution)) }} Ulasan</span>
    </div>
    <div class="admin-chart-canvas-wrap" style="height: 220px;">
        <canvas id="ratingsIndexDistChart"></canvas>
    </div>
</div>

<div class="detail-card">
    <form action="{{ route('admin.ratings.index') }}" method="GET" class="filter-row" style="margin-bottom: 24px;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="search" class="form-input" placeholder="Cari isi ulasan, nama pengguna, atau destinasi..." value="{{ request('search') }}">
        </div>
        <select name="rating" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Bintang</option>
            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Bintang</option>
            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Bintang</option>
            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Bintang</option>
            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Bintang</option>
            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Bintang</option>
        </select>
        <button type="submit" class="btn-primary" style="padding: 13px 20px; border-radius: var(--radius-md);">
            Filter
        </button>
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Destinasi</th>
                    <th>Provinsi</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ratings as $r)
                    <tr>
                        <td style="font-weight: 600;">{{ $r->user->name ?? 'Pengguna' }}</td>
                        <td>
                            <a href="{{ route('destinations.show', $r->destination) }}" target="_blank" style="color: var(--color-primary-dark); text-decoration: none; font-weight: 500;">
                                {{ $r->destination->name ?? '-' }}
                            </a>
                        </td>
                        <td>{{ $r->destination->province->name ?? '-' }}</td>
                        <td>
                            <span style="color: #f59e0b; font-weight: 700;">{{ $r->rating }} ★</span>
                        </td>
                        <td style="max-width: 320px;">
                            {{ $r->comment ?: 'Tanpa komentar' }}
                        </td>
                        <td style="font-size: 12px; color: var(--color-text-muted);">
                            {{ $r->created_at->format('d M Y H:i') }}
                        </td>
                        <td>
                            <form action="{{ route('admin.ratings.destroy', $r) }}" method="POST" class="confirm-action" data-confirm-title="Hapus Ulasan?" data-confirm-text="Apakah Anda yakin ingin menghapus ulasan ini? Data rating destinasi akan disesuaikan kembali." data-confirm-btn="Ya, Hapus" data-is-danger="true">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: none; border: 1px solid #fecaca; color: #ef4444; padding: 6px 10px; border-radius: var(--radius-sm); cursor: pointer;" title="Hapus Ulasan">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
                            Tidak ada data ulasan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $ratings->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const distData = @json($ratingDistribution);
    const canvas = document.getElementById('ratingsIndexDistChart');

    if (canvas && typeof Chart !== 'undefined') {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['1 Bintang', '2 Bintang', '3 Bintang', '4 Bintang', '5 Bintang'],
                datasets: [{
                    label: 'Jumlah Ulasan',
                    data: [
                        distData[1] || 0,
                        distData[2] || 0,
                        distData[3] || 0,
                        distData[4] || 0,
                        distData[5] || 0
                    ],
                    backgroundColor: [
                        '#ef4444',
                        '#f97316',
                        '#facc15',
                        '#60a5fa',
                        '#22c55e'
                    ],
                    borderRadius: 4,
                    maxBarThickness: 40
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
});
</script>
@endpush
