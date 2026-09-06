@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Pengguna Terdaftar')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Daftar Pengguna & Anggota Klaster</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Pantau data akun pengguna dan segmentasi klaster K-Means.</p>
    </div>
</div>

@if(!empty($clusterDistribution))
    <div class="admin-chart-card" style="margin-bottom: 24px;">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">
                <i class="fa-solid fa-diagram-project" style="color: var(--color-primary);"></i> Sebaran Wisatawan Terklaster (K-Means AI)
            </h3>
            <span style="font-size: 12px; color: #64748b;">Total Terklaster: {{ array_sum($clusterDistribution) }} Wisatawan</span>
        </div>
        <div class="admin-chart-canvas-wrap" style="height: 220px;">
            <canvas id="userClusterDistChart"></canvas>
        </div>
    </div>
@endif

<div class="detail-card">
    <form action="{{ route('admin.users.index') }}" method="GET" class="filter-row" style="margin-bottom: 24px;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="search" class="form-input" placeholder="Cari nama atau email pengguna..." value="{{ request('search') }}">
        </div>
        <select name="role" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Peran</option>
            <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User Biasa</option>
            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
        </select>
        <select name="cluster" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Klaster</option>
            <option value="1" {{ request('cluster') == '1' ? 'selected' : '' }}>Klaster 1</option>
            <option value="2" {{ request('cluster') == '2' ? 'selected' : '' }}>Klaster 2</option>
            <option value="3" {{ request('cluster') == '3' ? 'selected' : '' }}>Klaster 3</option>
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
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Klaster AI</th>
                    <th>Ulasan</th>
                    <th>Rata-rata Rating</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td style="font-weight: 600;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </div>
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @if($u->isAdmin())
                                <span class="badge badge-warning">Admin</span>
                            @else
                                <span class="badge badge-primary">User</span>
                            @endif
                        </td>
                        <td>
                            @if($u->cluster_id)
                                <span class="badge badge-info">Klaster {{ $u->cluster_id }}</span>
                            @else
                                <span style="color: var(--color-text-light); font-size: 13px;">-</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $u->ratings_count }}</strong> ulasan
                        </td>
                        <td>
                            @if($u->ratings_avg_rating)
                                <span style="font-weight: 700; color: #f59e0b;">★ {{ number_format($u->ratings_avg_rating, 1) }}</span>
                            @else
                                <span style="color: var(--color-text-light);">-</span>
                            @endif
                        </td>
                        <td style="font-size: 13px; color: var(--color-text-muted);">
                            {{ $u->created_at->format('d M Y') }}
                        </td>
                        <td>
                            @if($u->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="confirm-action" data-confirm-title="Hapus Pengguna?" data-confirm-text="Apakah Anda yakin ingin menghapus pengguna {{ $u->name }}? Semua data rating pengguna ini juga akan dihapus." data-confirm-btn="Ya, Hapus" data-is-danger="true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: 1px solid #fecaca; color: #ef4444; padding: 6px 10px; border-radius: var(--radius-sm); cursor: pointer;" title="Hapus Pengguna">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            @else
                                <span style="font-size: 12px; color: var(--color-text-light); font-style: italic;">Akun Anda</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
                            Tidak ada data pengguna.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $users->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clusterData = @json($clusterDistribution ?? []);
    const canvas = document.getElementById('userClusterDistChart');

    if (canvas && typeof Chart !== 'undefined' && Object.keys(clusterData).length > 0) {
        const clusterColors = {
            1: '#2563eb',
            2: '#10b981',
            3: '#f59e0b',
            4: '#ef4444',
            5: '#8b5cf6'
        };

        const labels = Object.keys(clusterData).map(cId => 'Klaster ' + cId);
        const values = Object.values(clusterData);
        const colors = Object.keys(clusterData).map(cId => clusterColors[cId] || '#64748b');

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Wisatawan',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
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
                            label: function(ctx) { return ctx.raw + ' Wisatawan Terklaster'; }
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
