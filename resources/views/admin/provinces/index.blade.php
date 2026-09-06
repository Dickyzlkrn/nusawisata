@extends('layouts.admin')

@section('title', 'Kelola 38 Provinsi Indonesia')
@section('page-title', 'Data 38 Provinsi')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>38 Provinsi Indonesia</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Kelola data wilayah provinsi dan informasi pariwisata daerah.</p>
    </div>
    <a href="{{ route('admin.provinces.create') }}" class="btn-primary" style="border-radius: var(--radius-pill);">
        <i class="fa-solid fa-plus" style="margin-right: 6px;"></i> Tambah Provinsi
    </a>
</div>

@if($topProvinces->isNotEmpty())
    <div class="admin-chart-card" style="margin-bottom: 24px;">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-chart-column" style="color: var(--color-primary);"></i> Top 10 Provinsi Berdasarkan Jumlah Destinasi Wisata
            </h3>
            <span style="font-size: 12px; color: #64748b;">Sebaran Objek Wisata Terdaftar</span>
        </div>
        <div class="admin-chart-canvas-wrap" style="height: 240px;">
            <canvas id="topProvincesChart"></canvas>
        </div>
    </div>
@endif

<div class="detail-card">
    <form action="{{ route('admin.provinces.index') }}" method="GET" class="filter-row" style="margin-bottom: 24px;">
        <div style="flex: 1; max-width: 400px;">
            <input type="text" name="search" class="form-input" placeholder="Cari nama provinsi..." value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn-primary" style="padding: 13px 20px; border-radius: var(--radius-md);">
            Cari
        </button>
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Provinsi</th>
                    <th>Slug</th>
                    <th>Jumlah Destinasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($provinces as $prov)
                    <tr>
                        <td>
                            <img src="{{ $prov->image ?: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=100&q=80' }}" alt="{{ $prov->name }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: var(--radius-md);">
                        </td>
                        <td style="font-weight: 600;">
                            <a href="{{ route('provinces.show', $prov) }}" target="_blank" style="color: var(--color-primary-dark); text-decoration: none;">
                                {{ $prov->name }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px; color: var(--color-text-light);"></i>
                            </a>
                        </td>
                        <td><code>{{ $prov->slug }}</code></td>
                        <td>
                            <span class="badge badge-primary">{{ $prov->destinations_count }} Destinasi</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('admin.provinces.edit', $prov) }}" class="btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: var(--radius-sm); text-decoration: none;">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <form action="{{ route('admin.provinces.destroy', $prov) }}" method="POST" class="confirm-action" data-confirm-title="Hapus Provinsi?" data-confirm-text="Apakah Anda yakin ingin menghapus provinsi {{ $prov->name }}? Destinasi di provinsi ini mungkin akan terpengaruh." data-confirm-btn="Ya, Hapus" data-is-danger="true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: 1px solid #fecaca; color: #ef4444; padding: 6px 10px; border-radius: var(--radius-sm); cursor: pointer;" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
                            Tidak ada data provinsi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $provinces->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const topProvs = @json($topProvinces);
    const canvas = document.getElementById('topProvincesChart');

    if (canvas && typeof Chart !== 'undefined' && topProvs.length > 0) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: topProvs.map(p => p.name),
                datasets: [{
                    label: 'Jumlah Destinasi',
                    data: topProvs.map(p => p.destinations_count),
                    backgroundColor: 'rgba(26, 86, 219, 0.85)',
                    borderRadius: 4,
                    maxBarThickness: 35
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
                                return ctx.raw + ' Destinasi Wisata';
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
});
</script>
@endpush
