@extends('layouts.app')

@section('title', 'Dashboard Saya — NusaWisata')

@section('content')
<section class="dashboard-section">
    <div class="container">
        <!-- Greeting Header -->
        <div class="dashboard-greeting" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 36px; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 8px; display: inline-flex;">
                    <i class="fa-solid fa-user-check"></i> Wisatawan Terdaftar
                </span>
                <h1 style="font-size: 32px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">
                    Halo, {{ $user->name }}!
                </h1>
                <p style="color: var(--color-text-muted); font-size: 14px;">
                    Berikut ringkasan ulasan perjalanan dan rekomendasi destinasi yang disesuaikan untuk Anda.
                </p>
            </div>
            <div>
                <a href="{{ route('profile.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); font-size: 13.5px;">
                    <i class="fa-solid fa-gear"></i> Pengaturan Profil
                </a>
            </div>
        </div>

        <!-- Stat Cards Grid -->
        <div class="admin-stats-grid" style="margin-bottom: 48px;">
            <x-cards.stat-card 
                label="Ulasan Diberikan" 
                :value="$totalRatings" 
                icon="fa-solid fa-comments" 
            />
            <x-cards.stat-card 
                label="Rata-rata Rating Saya" 
                :value="number_format($avgRatingGiven, 1) . ' ★'" 
                icon="fa-solid fa-star" 
            />
            <x-cards.stat-card 
                label="Klaster Minat (K-Means)" 
                :value="$user->cluster_id ? 'Klaster ' . $user->cluster_id : 'Belum Terklaster'" 
                icon="fa-solid fa-diagram-project" 
            />
            <x-cards.stat-card 
                label="Status Akun" 
                :value="ucfirst($user->role)" 
                icon="fa-solid fa-shield-halved" 
            />
        </div>

        <!-- Personalized Recommendations -->
        @if($recommendations->count() > 0)
            <div style="margin-bottom: 60px;">
                <x-ui.section-heading 
                    title="Rekomendasi Wisata Sesuai Selera Anda"
                    :subtitle="$user->cluster_id ? 'Dihasilkan oleh model Collaborative Filtering berdasarkan ulasan Anda dan profil wisatawan di Klaster ' . $user->cluster_id . '.' : 'Dihasilkan berdasarkan ulasan komunitas wisatawan nusantara.'"
                    actionText="Lihat Semua Rekomendasi"
                    actionUrl="{{ route('recommendations.index') }}"
                />

                <div class="destination-grid" style="grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    @foreach($recommendations as $rec)
                        <x-cards.destination-card :destination="$rec" />
                    @endforeach
                </div>
            </div>
        @endif

        <!-- My Reviews History -->
        <div class="detail-card">
            <h3 style="font-size: 20px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 20px;">
                Riwayat Ulasan & Rating Saya ({{ $totalRatings }})
            </h3>

            @if($ratings->count() > 0)
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Destinasi</th>
                                <th>Provinsi</th>
                                <th>Rating Saya</th>
                                <th>Komentar</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ratings as $rating)
                                <tr>
                                    <td style="font-weight: 600;">
                                        @if($rating->destination)
                                            <a href="{{ route('destinations.show', $rating->destination) }}" style="color: var(--color-primary-dark); text-decoration: none;">
                                                {{ $rating->destination->name }}
                                            </a>
                                        @else
                                            Destinasi Dihapus
                                        @endif
                                    </td>
                                    <td>{{ $rating->destination->province->name ?? '-' }}</td>
                                    <td>
                                        <div style="color: #f59e0b; font-weight: 700;">
                                            {{ $rating->rating }} ★
                                        </div>
                                    </td>
                                    <td style="max-width: 300px;">
                                        {{ Str::limit($rating->comment ?: 'Tanpa komentar tertulis', 70) }}
                                    </td>
                                    <td style="font-size: 12px; color: var(--color-text-muted);">
                                        {{ $rating->created_at->format('d M Y') }}
                                    </td>
                                    <td>
                                        <form action="{{ route('ratings.destroy', $rating) }}" method="POST" class="confirm-action" data-confirm-title="Hapus Ulasan?" data-confirm-text="Ulasan yang dihapus tidak dapat dipulihkan." data-confirm-btn="Ya, Hapus" data-is-danger="true">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="color: var(--color-danger); background: none; border: none; cursor: pointer; font-size: 13px;" title="Hapus Ulasan">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: center;">
                    {{ $ratings->links() }}
                </div>
            @else
                <div style="text-align: center; padding: 40px; color: var(--color-text-muted);">
                    <i class="fa-regular fa-star" style="font-size: 36px; margin-bottom: 12px; color: var(--color-text-light);"></i>
                    <p>Anda belum memberikan rating pada destinasi wisata.</p>
                    <a href="{{ route('destinations.index') }}" class="btn-primary" style="border-radius: var(--radius-pill); margin-top: 16px;">
                        Mulai Eksplorasi & Beri Rating
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
