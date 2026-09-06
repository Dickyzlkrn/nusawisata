@extends('layouts.app')

@section('title', $destination->name . ' — NusaWisata')

@section('content')
<section class="dest-detail-section">
    <div class="container">
        <!-- Hero Header -->
        <div class="dest-detail-hero">
            <img src="{{ $destination->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1600&q=85' }}" alt="{{ $destination->name }}">
            <div class="dest-detail-hero-overlay">
                <div class="dest-detail-hero-tags">
                    @if($destination->category)
                        <span class="badge badge-primary" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px); color: #fff;">
                            {{ $destination->category }}
                        </span>
                    @endif
                    <a href="{{ route('provinces.show', $destination->province) }}" class="badge badge-info" style="text-decoration: none; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px); color: #fff;">
                        <i class="fa-solid fa-location-dot"></i> {{ $destination->province->name ?? 'Indonesia' }}
                    </a>
                </div>
                <h1>{{ $destination->name }}</h1>
                <div style="display: flex; align-items: center; gap: 16px; margin-top: 8px;">
                    <x-ui.rating-stars :rating="$destination->effective_rating" :reviewsCount="$destination->review_count" />
                </div>
            </div>
        </div>

        <!-- Detail Grid -->
        <div class="dest-detail-grid">
            <!-- Left Main Column -->
            <div class="dest-detail-main">
                <!-- Description -->
                <div class="detail-card" style="margin-bottom: 32px;">
                    <h2>Tentang {{ $destination->name }}</h2>
                    <p style="white-space: pre-line; line-height: 1.8;">{{ $destination->description }}</p>
                </div>

                <!-- Leaflet Interactive Map -->
                @if($destination->latitude && $destination->longitude)
                    <div class="detail-card" style="margin-bottom: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h2>Lokasi & Peta Interaktif</h2>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $destination->latitude }},{{ $destination->longitude }}" target="_blank" class="btn-outline" style="font-size: 13px; padding: 6px 14px; border-radius: var(--radius-pill);">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Google Maps
                            </a>
                        </div>
                        <x-map.leaflet-map 
                            elementId="destinationDetailMap"
                            :lat="$destination->latitude"
                            :lng="$destination->longitude"
                            :zoom="14"
                            height="380px"
                            :title="$destination->name"
                            :description="$destination->province->name ?? ''"
                        />
                    </div>
                @endif

                <!-- User Rating / Review Submission Form -->
                <div class="detail-card" style="margin-bottom: 32px;" id="ulasanForm">
                    <h2>Beri Ulasan & Rating</h2>
                    <p style="font-size: 14px; color: var(--color-text-muted); margin-bottom: 20px;">
                        Rating Anda sangat berharga untuk melatih algoritma <strong>Collaborative Filtering</strong> dan memberikan rekomendasi yang lebih presisi kepada wisatawan lain.
                    </p>

                    @auth
                        <form action="{{ route('ratings.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="destination_id" value="{{ $destination->id }}">

                            <div class="form-group">
                                <label class="form-label">Pilih Rating Bintang:</label>
                                <x-forms.star-rating-input :value="$userRating->rating ?? 5" />
                            </div>

                            <div class="form-group">
                                <label for="comment" class="form-label">Tulis Pengalaman Anda (Opsional):</label>
                                <textarea name="comment" id="comment" class="form-textarea" placeholder="Bagikan tips kunjungan, suasana tempat, atau keindahan yang Anda temui...">{{ old('comment', $userRating->comment ?? '') }}</textarea>
                            </div>

                            <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill);">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>{{ $userRating ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}</span>
                            </button>
                        </form>
                    @else
                        <div style="background: var(--color-bg-subtle); padding: 24px; border-radius: var(--radius-lg); text-align: center;">
                            <i class="fa-solid fa-lock" style="font-size: 28px; color: var(--color-primary); margin-bottom: 12px;"></i>
                            <h4 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">Masuk untuk Memberikan Rating</h4>
                            <p style="font-size: 13.5px; color: var(--color-text-muted); margin-bottom: 16px;">Bergabunglah dengan komunitas NusaWisata untuk mencatat destinasi dan mendapatkan rekomendasi berbasis AI.</p>
                            <a href="{{ route('login') }}" class="btn-primary" style="border-radius: var(--radius-pill);">
                                Masuk ke Akun
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Community Reviews List -->
                <div class="detail-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2>Ulasan Komunitas ({{ $destination->ratings->count() }})</h2>
                    </div>

                    @if($destination->ratings->count() > 0)
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            @foreach($destination->ratings as $review)
                                <div style="padding-bottom: 20px; border-bottom: 1px solid var(--color-border-light);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                                {{ strtoupper(substr($review->user->name ?? 'Pengguna', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div style="font-size: 14px; font-weight: 700; color: var(--color-primary-dark);">
                                                    {{ $review->user->name ?? 'Pengguna NusaWisata' }}
                                                </div>
                                                <div style="font-size: 12px; color: var(--color-text-light);">
                                                    {{ $review->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        <x-ui.rating-stars :rating="$review->rating" :showNumber="false" />
                                    </div>
                                    @if($review->comment)
                                        <p style="font-size: 14px; line-height: 1.6; color: var(--color-text-main); margin-top: 8px;">
                                            {{ $review->comment }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="color: var(--color-text-muted); font-size: 14px; text-align: center; padding: 24px 0;">
                            Belum ada ulasan komunitas untuk destinasi ini. Jadilah yang pertama memberikan ulasan!
                        </p>
                    @endif
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div class="dest-detail-sidebar">
                <!-- Info Summary Card -->
                <div class="detail-card">
                    <h3>Informasi Wisata</h3>

                    <div class="dest-info-row">
                        <span class="dest-info-label">
                            <i class="fa-solid fa-archway"></i> Provinsi
                        </span>
                        <a href="{{ route('provinces.show', $destination->province) }}" class="dest-info-value" style="color: var(--color-accent); text-decoration: none;">
                            {{ $destination->province->name ?? '-' }}
                        </a>
                    </div>

                    <div class="dest-info-row">
                        <span class="dest-info-label">
                            <i class="fa-solid fa-tags"></i> Kategori
                        </span>
                        <span class="dest-info-value">{{ $destination->category ?? 'Umum' }}</span>
                    </div>

                    <div class="dest-info-row">
                        <span class="dest-info-label">
                            <i class="fa-solid fa-ticket"></i> Tiket Masuk
                        </span>
                        <span class="dest-info-value" style="color: #10b981;">{{ $destination->formatted_price }}</span>
                    </div>

                    <div class="dest-info-row">
                        <span class="dest-info-label">
                            <i class="fa-solid fa-star"></i> Skor Ulasan
                        </span>
                        <span class="dest-info-value">{{ number_format($destination->google_rating, 1) }} / 5.0</span>
                    </div>

                    <div class="dest-info-row">
                        <span class="dest-info-label">
                            <i class="fa-solid fa-users"></i> Total Review
                        </span>
                        <span class="dest-info-value">{{ number_format($destination->review_count) }}</span>
                    </div>

                    @if($destination->latitude && $destination->longitude)
                        <div class="dest-info-row">
                            <span class="dest-info-label">
                                <i class="fa-solid fa-compass"></i> Koordinat
                            </span>
                            <span class="dest-info-value" style="font-size: 12px; font-family: monospace;">
                                {{ round($destination->latitude, 4) }}, {{ round($destination->longitude, 4) }}
                            </span>
                        </div>
                    @endif

                    <a href="#ulasanForm" class="btn-primary" style="width: 100%; justify-content: center; border-radius: var(--radius-pill); margin-top: 20px;">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Beri Ulasan Sekarang</span>
                    </a>
                </div>

                <!-- Daily Popularity Heatmap / Hours Card -->
                <div class="detail-card">
                    <h3>Estimasi Kepadatan Harian</h3>
                    <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
                        Grafik popularitas kunjungan wisatawan sepanjang pekan.
                    </p>

                    <x-ui.popularity-chart :popularities="$destination->popularities" />
                </div>
            </div>
        </div>

        <!-- Related Destinations -->
        @if($relatedDestinations->count() > 0)
            <div style="margin-top: 64px;">
                <x-ui.section-heading 
                    title="Destinasi Serupa Lainnya"
                    subtitle="Temukan tempat wisata menarik lainnya di kawasan yang berdekatan atau dalam kategori yang sama."
                    actionText="Lihat Katalog"
                    actionUrl="{{ route('destinations.index') }}"
                />

                <div class="destination-grid">
                    @foreach($relatedDestinations as $related)
                        <div class="dest-list-card">
                            <div class="dest-list-card-img">
                                <a href="{{ route('destinations.show', $related) }}">
                                    <img src="{{ $related->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=80' }}" alt="{{ $related->name }}">
                                </a>
                            </div>
                            <div class="dest-list-card-body">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span class="badge badge-primary">{{ $related->category }}</span>
                                    <span style="font-weight: 700; color: #f59e0b; font-size: 13px;">★ {{ number_format($related->google_rating, 1) }}</span>
                                </div>
                                <a href="{{ route('destinations.show', $related) }}" style="text-decoration: none;">
                                    <h4>{{ $related->name }}</h4>
                                </a>
                                <p>{{ Str::limit($related->description, 80) }}</p>
                                <div class="dest-list-card-footer">
                                    <span class="dest-list-card-price">{{ $related->formatted_price }}</span>
                                    <a href="{{ route('destinations.show', $related) }}" class="btn-outline" style="padding: 6px 14px; font-size: 12px; border-radius: var(--radius-pill);">
                                        Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
