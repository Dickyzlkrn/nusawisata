@extends('layouts.app')

@section('title', 'Destinasi Wisata di ' . $province->name . ' — NusaWisata')

@section('content')
<section class="province-detail-section">
    <div class="container">
        <!-- Province Hero Banner -->
        <div class="province-hero">
            <img src="{{ $province->image ?: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1600&q=85' }}" alt="{{ $province->name }}">
            <div class="province-hero-overlay">
                <span class="badge badge-primary" style="margin-bottom: 8px; width: fit-content;">Provinsi Indonesia</span>
                <h1>{{ $province->name }}</h1>
                <p>{{ $province->description }}</p>
            </div>
        </div>

        <!-- Province Interactive Map -->
        @if(!empty($mapMarkers))
            <div style="margin-bottom: 48px;">
                <h3 style="font-size: 20px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 16px;">
                    <i class="fa-solid fa-map-location-dot" style="color: var(--color-primary); margin-right: 6px;"></i>
                    Peta Sebaran Wisata di {{ $province->name }}
                </h3>
                <x-map.leaflet-map 
                    elementId="provinceMap"
                    :lat="$mapMarkers[0]['lat'] ?? -2.5"
                    :lng="$mapMarkers[0]['lng'] ?? 118.0"
                    :zoom="10"
                    height="380px"
                    :markers="$mapMarkers"
                />
            </div>
        @endif

        <!-- Destinations Section -->
        <div class="destination-list-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h2 class="section-title">Destinasi di {{ $province->name }} ({{ $province->destinations_count }})</h2>
                <p class="section-desc">Pilihan objek wisata terbaik yang dapat Anda kunjungi di provinsi ini.</p>
            </div>
            <a href="{{ route('destinations.index', ['province' => $province->slug]) }}" class="btn-outline" style="border-radius: var(--radius-pill); font-size: 13.5px;">
                Filter Selengkapnya
            </a>
        </div>

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
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span class="badge badge-primary">{{ $dest->category }}</span>
                                <span style="font-weight: 700; color: #f59e0b; font-size: 13px;">★ {{ number_format($dest->google_rating, 1) }}</span>
                            </div>
                            <a href="{{ route('destinations.show', $dest) }}" style="text-decoration: none;">
                                <h4>{{ $dest->name }}</h4>
                            </a>
                            <p>{{ Str::limit($dest->description, 90) }}</p>
                            <div class="dest-list-card-footer">
                                <span class="dest-list-card-price">{{ $dest->formatted_price }}</span>
                                <a href="{{ route('destinations.show', $dest) }}" class="btn-outline" style="padding: 8px 16px; font-size: 13px; border-radius: var(--radius-pill);">
                                    Detail
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 48px; display: flex; justify-content: center;">
                {{ $destinations->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 48px; background: var(--color-white); border-radius: var(--radius-xl); border: 1px dashed var(--color-border);">
                <p style="color: var(--color-text-muted);">Belum ada destinasi terdaftar di provinsi ini.</p>
            </div>
        @endif
    </div>
</section>
@endsection
