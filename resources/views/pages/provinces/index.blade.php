@extends('layouts.app')

@section('title', '38 Provinsi Wisata Indonesia — NusaWisata')

@section('content')
<section class="destination-list-section">
    <div class="container">
        <!-- Header -->
        <div class="destination-list-header">
            <h1 class="section-title">Jelajahi 38 Provinsi Nusantara</h1>
            <p class="section-desc">Kekayaan alam dan keragaman budaya Indonesia tersebar di 38 provinsi dari barat hingga timur.</p>
        </div>

        <!-- Search Province -->
        <form action="{{ route('provinces.index') }}" method="GET" class="filter-row" style="margin-bottom: 40px;">
            <div style="flex: 1; max-width: 450px;">
                <input type="text" name="search" class="form-input" placeholder="Cari nama provinsi (cth: Bali, Papua, Jawa)..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn-primary" style="padding: 13px 24px; border-radius: var(--radius-md);">
                <i class="fa-solid fa-magnifying-glass"></i> Cari Provinsi
            </button>
            @if(request('search'))
                <a href="{{ route('provinces.index') }}" class="btn-outline" style="padding: 13px 20px; border-radius: var(--radius-md); text-decoration: none;">
                    Reset
                </a>
            @endif
        </form>

        <!-- Provinces Grid -->
        <div class="provinces-grid">
            @forelse($provinces as $province)
                <x-cards.province-card :province="$province" />
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--color-white); border-radius: var(--radius-xl); border: 1px dashed var(--color-border);">
                    <p style="color: var(--color-text-muted);">Provinsi tidak ditemukan untuk pencarian "{{ request('search') }}".</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
