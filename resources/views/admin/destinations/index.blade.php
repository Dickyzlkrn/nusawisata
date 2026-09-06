@extends('layouts.admin')

@section('title', 'Kelola Destinasi Wisata')
@section('page-title', 'Destinasi Wisata')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Katalog Destinasi Wisata</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Kelola data tempat wisata, koordinat peta, dan harga tiket masuk.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="{{ route('admin.dashboard') }}#adminDashboardMap" class="btn-outline" style="border-radius: var(--radius-pill); text-decoration: none; padding: 10px 18px;">
            <i class="fa-solid fa-map-location-dot" style="margin-right: 6px; color: #ef4444;"></i> Lihat di Peta
        </a>
        <a href="{{ route('admin.destinations.create') }}" class="btn-primary" style="border-radius: var(--radius-pill); padding: 10px 18px;">
            <i class="fa-solid fa-plus" style="margin-right: 6px;"></i> Tambah Destinasi Baru
        </a>
    </div>
</div>

<div class="detail-card" style="margin-bottom: 24px;">
    <!-- Filter Bar -->
    <form action="{{ route('admin.destinations.index') }}" method="GET" class="filter-row" style="margin-bottom: 24px;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-input" placeholder="Cari nama destinasi..." value="{{ request('search') }}">
        </div>
        <select name="province_id" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Provinsi</option>
            @foreach($provinces as $p)
                <option value="{{ $p->id }}" {{ request('province_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
            @endforeach
        </select>
        <select name="category" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            @foreach(['Wisata Alam', 'Budaya & Sejarah', 'Wisata Bahari', 'Taman Hiburan', 'Wisata Religi', 'Kuliner & Belanja'] as $cat)
                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary" style="padding: 13px 20px; border-radius: var(--radius-md);">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'province_id', 'category']))
            <a href="{{ route('admin.destinations.index') }}" class="btn-outline" style="padding: 13px 18px; border-radius: var(--radius-md); text-decoration: none;">
                Reset
            </a>
        @endif
    </form>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Destinasi</th>
                    <th>Provinsi</th>
                    <th>Kategori</th>
                    <th>Harga Tiket</th>
                    <th>Rating</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($destinations as $dest)
                    <tr>
                        <td>
                            <img src="{{ $dest->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=100&q=80' }}" alt="{{ $dest->name }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-md);">
                        </td>
                        <td style="font-weight: 600;">
                            <a href="{{ route('destinations.show', $dest) }}" target="_blank" style="color: var(--color-primary-dark); text-decoration: none;">
                                {{ $dest->name }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px; color: var(--color-text-light);"></i>
                            </a>
                        </td>
                        <td>{{ $dest->province->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-primary">{{ $dest->category ?? 'Umum' }}</span>
                        </td>
                        <td style="font-weight: 600;">{{ $dest->formatted_price }}</td>
                        <td>
                            <span style="font-weight: 700; color: #f59e0b;">★ {{ number_format($dest->google_rating, 1) }}</span>
                            <small style="color: var(--color-text-light);">({{ number_format($dest->review_count) }})</small>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('admin.destinations.edit', $dest) }}" class="btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: var(--radius-sm); text-decoration: none;">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <form action="{{ route('admin.destinations.destroy', $dest) }}" method="POST" class="confirm-action" data-confirm-title="Hapus Destinasi?" data-confirm-text="Apakah Anda yakin ingin menghapus destinasi {{ $dest->name }}? Tindakan ini tidak dapat dibatalkan." data-confirm-btn="Ya, Hapus" data-is-danger="true">
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
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--color-text-muted);">
                            Tidak ada data destinasi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $destinations->links() }}
    </div>
</div>
@endsection
