@extends('layouts.app')

@section('title', 'Katalog Destinasi Wisata Indonesia — NusaWisata')

@section('content')
<section class="destination-list-section">
    <div class="container">
        <!-- Page Header -->
        <div class="destination-list-header">
            <h1 class="section-title">Katalog Destinasi Wisata Nusantara</h1>
            <p class="section-desc">Temukan destinasi liburan impian dari 38 provinsi di Indonesia dengan filter pencarian cerdas.</p>
        </div>

        <!-- Filter & Search Bar -->
        <form action="{{ route('destinations.index') }}" method="GET" class="filter-row">
            <!-- Search Keyword -->
            <div style="flex: 1; min-width: 260px;">
                <input type="text" name="search" class="form-input" placeholder="Cari nama destinasi atau kota..." value="{{ request('search') }}">
            </div>

            <!-- Province Filter -->
            <select name="province" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Provinsi (38)</option>
                @foreach($provinces as $p)
                    <option value="{{ $p->slug }}" {{ request('province') == $p->slug ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>

            <!-- Category Filter -->
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                        {{ $cat }}
                    </option>
                @endforeach
            </select>

            <!-- Sort By -->
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="popular" {{ request('sort', 'popular') == 'popular' ? 'selected' : '' }}>Paling Populer</option>
                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Rating Tertinggi</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Harga: Terendah</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Harga: Tertinggi</option>
                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nama: A - Z</option>
            </select>

            <button type="submit" class="btn-primary" style="padding: 13px 24px; border-radius: var(--radius-md);">
                <i class="fa-solid fa-filter"></i> Filter
            </button>

            @if(request()->anyFilled(['search', 'province', 'category', 'sort']))
                <a href="{{ route('destinations.index') }}" class="btn-outline" style="padding: 13px 20px; border-radius: var(--radius-md); text-decoration: none;">
                    Reset
                </a>
            @endif
        </form>

        <!-- Destination Cards Grid -->
        @if($destinations->count() > 0)
            <div class="destination-grid">
                @foreach($destinations as $dest)
                    <div class="dest-list-card">
                        <div class="dest-list-card-img">
                            <a href="{{ route('destinations.show', $dest) }}">
                                <img src="{{ $dest->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=80' }}" alt="{{ $dest->name }}">
                            </a>
                        </div>
                        <div class="dest-list-card-body">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                @if($dest->category)
                                    <span class="badge badge-primary">{{ $dest->category }}</span>
                                @endif
                                <div style="display: flex; align-items: center; gap: 4px; font-weight: 700; color: #f59e0b; font-size: 13px;">
                                    <i class="fa-solid fa-star"></i> {{ number_format($dest->google_rating, 1) }}
                                </div>
                            </div>

                            <a href="{{ route('destinations.show', $dest) }}" style="text-decoration: none;">
                                <h4>{{ $dest->name }}</h4>
                            </a>

                            <div class="dest-list-card-meta">
                                <span>
                                    <i class="fa-solid fa-location-dot" style="color: var(--color-accent);"></i>
                                    {{ $dest->province->name ?? 'Indonesia' }}
                                </span>
                                <span>
                                    <i class="fa-solid fa-comments"></i>
                                    {{ number_format($dest->review_count) }} ulasan
                                </span>
                            </div>

                            <p>{{ Str::limit($dest->description, 100) }}</p>

                            <div class="dest-list-card-footer">
                                <div class="dest-list-card-price">
                                    {{ $dest->formatted_price }}
                                    @if($dest->price > 0)
                                        <small>/ orang</small>
                                    @endif
                                </div>
                                <a href="{{ route('destinations.show', $dest) }}" class="btn-outline" style="padding: 8px 16px; font-size: 13px; border-radius: var(--radius-pill);">
                                    <span>Detail</span>
                                    <i class="fa-solid fa-chevron-right" style="font-size: 11px; margin-left: 4px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div style="margin-top: 48px; display: flex; justify-content: center;">
                {{ $destinations->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 64px 20px; background: var(--color-white); border-radius: var(--radius-xl); border: 1px dashed var(--color-border); margin-top: 32px;">
                <i class="fa-solid fa-magnifying-glass-location" style="font-size: 48px; color: var(--color-text-light); margin-bottom: 16px;"></i>
                <h3 style="font-size: 20px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px;">Tidak Ada Destinasi Ditemukan</h3>
                <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 24px;">Silakan coba kata kunci lain atau ubah filter pencarian Anda.</p>
                <a href="{{ route('destinations.index') }}" class="btn-primary" style="border-radius: var(--radius-pill);">
                    Lihat Semua Destinasi
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
