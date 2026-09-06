@extends('layouts.app')

@section('title', 'NusaWisata — Sistem Rekomendasi Wisata Indonesia')

@section('content')
    <!-- 1. HERO SECTION -->
    <x-ui.hero 
        title="Eksplorasi Keindahan Wisata Nusantara Indonesia"
        subtitle="Temukan ribuan destinasi menakjubkan di 38 provinsi nusantara dengan rekomendasi cerdas berbasis Collaborative Filtering dan K-Means Clustering."
        buttonText="Mulai Menjelajah"
        buttonUrl="#destinasi"
    />

    <!-- Smart Recommendation Search Bar Overlay -->
    <div class="container" style="margin-top: -36px; margin-bottom: 44px; position: relative; z-index: 20;">
        <div class="hero-rec-card">
            
            <!-- Header Tag & Link -->
            <div class="hero-rec-header">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 12px; border-radius: var(--radius-pill); text-transform: uppercase; letter-spacing: 0.04em;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Rekomendasi Cerdas
                    </span>
                    <span style="font-size: 13px; color: var(--color-text-muted);">
                        Pilih preferensi liburan Anda untuk langsung dihitung oleh AI Machine Learning
                    </span>
                </div>
                <a href="{{ route('recommendations.index') }}" style="font-size: 13px; font-weight: 600; color: var(--color-primary-dark); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <span>Buka Engine Lengkap</span>
                    <i class="fa-solid fa-arrow-right" style="font-size: 11px;"></i>
                </a>
            </div>

            <!-- Recommendation Search Form -->
            <form action="{{ route('recommendations.index') }}" method="GET">
                <div class="hero-rec-fields">
                    <!-- 1. Kata Kunci -->
                    <div class="hero-rec-field">
                        <label for="heroKeyword" class="hero-rec-label">
                            <i class="fa-solid fa-magnifying-glass" style="color: var(--color-accent);"></i> Kata Kunci
                        </label>
                        <input type="text" id="heroKeyword" name="keyword" placeholder="Nama pantai, candi, pulau..." class="hero-rec-input">
                    </div>

                    <!-- 2. Provinsi -->
                    <div class="hero-rec-field">
                        <label for="heroProvince" class="hero-rec-label">
                            <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> Daerah / Provinsi
                        </label>
                        <select id="heroProvince" name="province" class="hero-rec-select">
                            <option value="all">Semua Provinsi (38)</option>
                            @foreach($filterOptions['provinces'] ?? [] as $p)
                                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Kategori -->
                    <div class="hero-rec-field">
                        <label for="heroCategory" class="hero-rec-label">
                            <i class="fa-solid fa-layer-group" style="color: #3b82f6;"></i> Kategori
                        </label>
                        <select id="heroCategory" name="category" class="hero-rec-select">
                            @foreach($filterOptions['categories'] ?? [] as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Rentang Budget -->
                    <div class="hero-rec-field">
                        <label for="heroBudget" class="hero-rec-label">
                            <i class="fa-solid fa-wallet" style="color: #10b981;"></i> Rentang Budget
                        </label>
                        <select id="heroBudget" name="budget" class="hero-rec-select">
                            @foreach($filterOptions['budgets'] ?? [] as $bKey => $bLabel)
                                <option value="{{ $bKey }}">{{ $bLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 5. Tombol Submit -->
                    <div class="hero-rec-field hero-rec-btn-wrap">
                        <button type="submit" class="btn-primary hero-rec-submit">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Cari Rekomendasi</span>
                        </button>
                    </div>
                </div>

                <!-- Rekomendasi Cepat / Quick Badges -->
                <div class="hero-rec-tags">
                    <span class="hero-rec-tag-title">Rekomendasi Cepat:</span>
                    <a href="{{ route('recommendations.index', ['category' => 'Alam']) }}" class="badge badge-primary" style="text-decoration: none;">
                        <i class="fa-solid fa-mountain-sun"></i> Wisata Alam
                    </a>
                    <a href="{{ route('recommendations.index', ['category' => 'Budaya']) }}" class="badge badge-info" style="text-decoration: none;">
                        <i class="fa-solid fa-landmark"></i> Budaya
                    </a>
                    <a href="{{ route('recommendations.index', ['category' => 'Bahari']) }}" class="badge badge-success" style="text-decoration: none;">
                        <i class="fa-solid fa-water"></i> Bahari
                    </a>
                    <a href="{{ route('recommendations.index', ['category' => 'Sejarah']) }}" class="badge badge-warning" style="text-decoration: none;">
                        <i class="fa-solid fa-monument"></i> Sejarah
                    </a>
                    <a href="{{ route('recommendations.index', ['budget' => 'free']) }}" class="badge" style="background: #f1f5f9; color: #334155; border: 1px solid var(--color-border); text-decoration: none;">
                        <i class="fa-solid fa-ticket"></i> Masuk Gratis
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. FITUR UNGGULAN & METODOLOGI RISET -->
    <section class="services-section">
        <div class="container">
            <div class="services-header">
                <h2 class="section-title">Teknologi Cerdas untuk Liburan Nusantara Anda</h2>
                <p class="section-desc">
                    NusaWisata memadukan kecerdasan algoritma data science untuk memberikan rekomendasi perjalanan yang akurat dan personal.
                </p>
            </div>

            <div class="services-grid">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fa-solid fa-brain" style="font-size: 28px;"></i>
                    </div>
                    <h4>Collaborative Filtering</h4>
                    <p>Menganalisis kemiripan preferensi ulasan antar-pengguna untuk memprediksi tempat wisata yang paling cocok untuk Anda.</p>
                </div>

                <div class="service-item">
                    <div class="service-icon">
                        <i class="fa-solid fa-diagram-project" style="font-size: 28px;"></i>
                    </div>
                    <h4>K-Means Clustering</h4>
                    <p>Mengelompokkan pengguna ke dalam klaster minat spesifik untuk meningkatkan akurasi dan keragaman rekomendasi wisata.</p>
                </div>

                <div class="service-item">
                    <div class="service-icon">
                        <i class="fa-solid fa-map-location-dot" style="font-size: 28px;"></i>
                    </div>
                    <h4>Katalog 38 Provinsi</h4>
                    <p>Jelajahi keajaiban alam dan sejarah dari ujung barat Sabang hingga ujung timur Merauke dalam satu platform.</p>
                </div>

                <div class="service-item">
                    <div class="service-icon">
                        <i class="fa-solid fa-clock" style="font-size: 28px;"></i>
                    </div>
                    <h4>Waktu Populer Kunjungan</h4>
                    <p>Estimasi grafik kepadatan kunjungan setiap hari agar Anda dapat merencanakan waktu liburan terbaik tanpa antrean.</p>
                </div>
            </div>

            <!-- Callout: Link to Documentation (Clean Editorial) -->
            <div style="margin-top: 36px; background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h4 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 4px 0;">
                        Metodologi & Cara Kerja Algoritma Rekomendasi
                    </h4>
                    <p style="font-size: 13.5px; color: var(--color-text-muted); margin: 0;">
                        Pelajari alur pipeline data, ekstraksi 12 fitur, normalisasi Z-Score, hingga evaluasi akurasi sistem.
                    </p>
                </div>
                <a href="{{ route('documentation') }}" class="btn-primary" style="border-radius: var(--radius-pill); padding: 9px 20px; font-size: 13px; text-decoration: none;">
                    <span>Buka Dokumentasi Sistem</span>
                    <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- 3. FEATURED HIGHLIGHT (BALI / ICONIC DESTINATION) -->
    @if($featured)
        <section class="featured-section">
            <div class="container">
                <div class="featured-grid">
                    <div class="featured-text-col">
                        <span class="badge badge-primary" style="margin-bottom: 12px; display: inline-flex;">
                            <i class="fa-solid fa-crown" style="margin-right: 4px;"></i> Destinasi Unggulan
                        </span>
                        <h2 class="section-title">{{ $featured->name }}</h2>
                        <p class="section-desc" style="margin-bottom: 24px;">
                            {{ Str::limit($featured->description, 180) }}
                        </p>
                        <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                            <div>
                                <span style="font-size: 13px; color: var(--color-text-muted);">Rating Pengunjung</span>
                                <div style="font-size: 20px; font-weight: 800; color: var(--color-primary-dark);">
                                    <i class="fa-solid fa-star" style="color: #fbbf24; font-size: 16px;"></i> {{ number_format($featured->google_rating, 1) }}
                                </div>
                            </div>
                            <div style="padding-left: 16px;">
                                <span style="font-size: 13px; color: var(--color-text-muted);">Tiket Masuk</span>
                                <div style="font-size: 20px; font-weight: 800; color: var(--color-primary-dark);">
                                    {{ $featured->formatted_price }}
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('destinations.show', $featured) }}" class="btn-primary" style="border-radius: var(--radius-pill); width: fit-content;">
                            <span>Lihat Detail Lengkap</span>
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    </div>

                    <div class="featured-card-wrapper">
                        <div class="featured-main-img">
                            <img src="{{ $featured->image }}" alt="{{ $featured->name }}">
                        </div>
                        <div class="featured-info-col">
                            <div class="featured-sub-img">
                                <img src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=600&q=80" alt="Detail Scenery">
                            </div>
                            <h3>{{ $featured->province->name ?? 'Indonesia' }}</h3>
                            <p>Destinasi favorit wisatawan domestik maupun mancanegara dengan keindahan alam tiada tara.</p>
                            <a href="{{ route('provinces.show', $featured->province) }}" class="view-details-link">
                                <span>Eksplorasi Provinsi</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- 4. EXPLORE HANDPICKED DESTINATIONS (CARDS GRID) -->
    <section class="destinations-section" id="destinasi">
        <div class="container">
            <x-ui.section-heading 
                title="Destinasi Wisata Pilihan Nusantara"
                subtitle="Koleksi tempat wisata terpopuler dan bernilai tinggi yang telah dinikmati oleh ribuan wisatawan."
                actionText="Lihat Semua Destinasi"
                actionUrl="{{ route('destinations.index') }}"
            />

            <!-- Top Grid (2 cards) -->
            <div class="destinations-grid-top">
                @foreach($topDestinations as $dest)
                    <x-cards.destination-card :destination="$dest" size="top" />
                @endforeach
            </div>

            <!-- Bottom Grid (3 cards) -->
            <div class="destinations-grid-bottom">
                @foreach($bottomDestinations as $dest)
                    <x-cards.destination-card :destination="$dest" size="bottom" />
                @endforeach
            </div>
        </div>
    </section>

    <!-- 5. PETA SEBARAN DESTINASI WISATA NUSANTARA (INTERACTIVE MAP) -->
    <section class="interactive-map-section" style="padding: 60px 0; background: var(--color-bg-subtle);">
        <div class="container">
            <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 32px 28px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);">
                <!-- Section Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="badge badge-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; font-size: 12px; font-weight: 700;">
                                <i class="fa-solid fa-map-location-dot"></i> Peta Wisata Interaktif
                            </span>
                            @if($activeDataset)
                                <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-size: 11.5px; font-weight: 600;">
                                    <i class="fa-solid fa-database" style="color: #64748b;"></i> Dataset: {{ $activeDataset->name }}
                                </span>
                            @endif
                        </div>
                        <h2 class="section-title" style="margin-bottom: 6px; font-size: 26px;">Peta Sebaran Destinasi Wisata Indonesia</h2>
                        <p class="section-desc" style="margin: 0; max-width: 720px;">
                            Eksplorasi seluruh titik destinasi wisata di 38 provinsi nusantara dari dataset aktif secara interaktif. Klik marker untuk melihat ulasan, tiket masuk, dan informasi lengkap.
                        </p>
                    </div>

                    <!-- Counter Badges -->
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px 14px; border-radius: var(--radius-pill);">
                            <i class="fa-solid fa-location-pin" style="color: #16a34a; font-size: 14px;"></i>
                            <span style="font-size: 13px; font-weight: 700; color: #166534;"><span id="publicMapCount">{{ count($mapDestinations) }}</span> Destinasi Terpetakan</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: var(--radius-pill);">
                            <i class="fa-solid fa-earth-asia" style="color: #2563eb; font-size: 14px;"></i>
                            <span style="font-size: 13px; font-weight: 700; color: #1e40af;">{{ count($mapProvinces) }} Provinsi</span>
                        </div>
                    </div>
                </div>

                <!-- Map Filter Bar -->
                <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; justify-content: space-between; background: #f8fafc; padding: 14px 18px; border-radius: var(--radius-lg); border: 1px solid var(--color-border);" class="public-map-toolbar">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; flex: 1; min-width: 260px;" class="public-map-filters">
                        <!-- Filter Provinsi -->
                        <div style="min-width: 180px; flex: 1;">
                            <select id="publicMapProvince" class="form-select" style="width: 100%; font-size: 13px; padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff;">
                                <option value="">Semua Provinsi ({{ count($mapDestinations) }})</option>
                                @foreach($mapProvinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Kategori -->
                        <div style="min-width: 160px; flex: 1;">
                            <select id="publicMapCategory" class="form-select" style="width: 100%; font-size: 13px; padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff;">
                                <option value="">Semua Kategori</option>
                                @foreach($mapCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div style="position: relative; min-width: 200px; flex: 1.5;">
                            <input type="text" id="publicMapSearch" placeholder="Cari nama destinasi..." style="width: 100%; font-size: 13px; padding: 8px 12px 8px 34px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: #ffffff;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #94a3b8;"></i>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" id="publicMapResetBtn" class="btn-outline" style="font-size: 12.5px; padding: 7px 14px; border-radius: var(--radius-md); height: 38px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Region Quick Focus Buttons -->
                <div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--color-text-muted); margin-right: 4px;">Fokus Wilayah:</span>
                    <button type="button" class="map-region-btn" data-lat="-2.5489" data-lng="118.0148" data-zoom="5" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: var(--color-primary-dark); font-weight: 600;">Semua Indonesia</button>
                    <button type="button" class="map-region-btn" data-lat="-7.536" data-lng="110.712" data-zoom="7" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Jawa</button>
                    <button type="button" class="map-region-btn" data-lat="-8.409" data-lng="115.188" data-zoom="9" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Bali & Lombok</button>
                    <button type="button" class="map-region-btn" data-lat="-0.589" data-lng="101.343" data-zoom="6" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Sumatera</button>
                    <button type="button" class="map-region-btn" data-lat="-1.681" data-lng="113.382" data-zoom="6" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Kalimantan</button>
                    <button type="button" class="map-region-btn" data-lat="-1.430" data-lng="121.445" data-zoom="6" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Sulawesi</button>
                    <button type="button" class="map-region-btn" data-lat="-8.652" data-lng="121.079" data-zoom="7" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Nusa Tenggara</button>
                    <button type="button" class="map-region-btn" data-lat="-3.238" data-lng="130.145" data-zoom="6" style="background: #ffffff; border: 1px solid var(--color-border); font-size: 12px; padding: 4px 12px; border-radius: var(--radius-pill); cursor: pointer; color: #475569;">Maluku & Papua</button>
                </div>

                <!-- Map Canvas Container -->
                <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-border); box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);">
                    <div id="publicHomeMap" style="width: 100%; height: 500px; z-index: 10;"></div>
                </div>

                <!-- Map Footer Info -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font-size: 12.5px; color: var(--color-text-muted); flex-wrap: wrap; gap: 8px;">
                    <span>
                        <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-right: 4px;"></i> Klik marker destinasi pada peta untuk melihat foto, harga tiket, rating, dan tautan detail wisata.
                    </span>
                    <a href="{{ route('destinations.index') }}" style="color: var(--color-primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <span>Lihat Semua Katalog</span>
                        <i class="fa-solid fa-arrow-right" style="font-size: 11px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. 38 PROVINSI INDONESIA PREVIEW -->
    <section class="provinces-home-section">
        <div class="container">
            <x-ui.section-heading 
                title="Jelajahi 38 Provinsi Nusantara"
                subtitle="Dari Sabang sampai Merauke, temukan keunikan budaya dan bentang alam di setiap provinsi."
                actionText="Semua 38 Provinsi"
                actionUrl="{{ route('provinces.index') }}"
            />

            <div class="provinces-grid">
                @foreach($provinces as $province)
                    <x-cards.province-card :province="$province" />
                @endforeach
            </div>
        </div>
    </section>

    <!-- 6. WISATA POPULER & VIRAL (DEAL CARDS PATTERN) -->
    @if($deals->count() >= 2)
        <section class="top-rated-section">
            <div class="container">
                <div class="top-rated-grid">
                    <div class="top-rated-left">
                        <span class="badge badge-warning" style="margin-bottom: 12px; display: inline-flex;">
                            <i class="fa-solid fa-fire"></i> Paling Banyak Diulas
                        </span>
                        <h2 class="section-title">Destinasi Paling Hits di Indonesia</h2>
                        <p class="section-desc">
                            Tempat-tempat dengan ulasan dan kunjungan tertinggi di mana Anda dapat menikmati pesona magis Indonesia.
                        </p>
                        <a href="{{ route('destinations.index', ['sort' => 'popular']) }}" class="btn-primary" style="border-radius: var(--radius-pill);">
                            <span>Jelajahi Tempat Hits</span>
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    </div>

                    <div class="top-rated-cards">
                        @foreach($deals as $deal)
                            <a href="{{ route('destinations.show', $deal) }}" class="deal-card" style="text-decoration: none; display: block;">
                                <img src="{{ $deal->image }}" alt="{{ $deal->name }}">
                                <div class="deal-card-overlay">
                                    <div class="deal-title">{{ $deal->name }}</div>
                                    <div class="deal-badge">
                                        <div class="deal-number">{{ number_format($deal->review_count / 1000, 0) }}k+</div>
                                        <div class="deal-unit">Ulasan</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- 7. PERSONALIZED RECOMMENDATIONS (IF LOGGED IN) -->
    @if($recommendations->count() > 0)
        <section class="destinations-section" style="background: var(--color-bg-subtle); padding: 60px 0; margin-bottom: 60px;">
            <div class="container">
                <div class="destinations-header">
                    <div>
                        <span class="badge badge-primary" style="margin-bottom: 8px; display: inline-flex;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Personalisasi Untuk Anda
                        </span>
                        <h2 class="section-title">Rekomendasi Berdasarkan Profil Minat Anda</h2>
                        <p class="section-desc">Dihasilkan oleh model Collaborative Filtering & Klaster K-Means Anda.</p>
                    </div>
                    <a href="{{ route('recommendations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill);">
                        <span>Buka Menu Rekomendasi</span>
                        <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                    </a>
                </div>

                <div class="destinations-grid-top" style="grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    @foreach($recommendations as $rec)
                        <x-cards.destination-card :destination="$rec" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- 8. ULASAN & TESTIMONI WISATAWAN -->
    <section class="testimonial-section">
        <div class="container">
            <div class="testimonial-banner">
                <div class="testimonial-card">
                    <div style="color: #fbbf24; font-size: 20px; margin-bottom: 12px;">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-quote">
                        "NusaWisata benar-benar membantu saya dan keluarga merencanakan liburan ke Labuan Bajo dan Nusa Penida. Rekomendasi yang diberikan sangat akurat sesuai minat kami yang menyukai wisata bahari dan alam terbuka!"
                    </p>
                    <div class="testimonial-user">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80" alt="Traveler" class="testimonial-avatar">
                        <div class="testimonial-meta">
                            <div class="testimonial-name">Anindya Putri</div>
                            <div class="testimonial-role">Travel Blogger & Explorer</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <style>
            .home-map-popup .leaflet-popup-content-wrapper {
                padding: 0 !important;
                overflow: hidden;
                border-radius: 12px !important;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15) !important;
                border: 1px solid var(--color-border) !important;
            }
            .home-map-popup .leaflet-popup-content {
                margin: 0 !important;
                width: 250px !important;
                line-height: 1.4 !important;
            }
            @media (max-width: 768px) {
                #publicHomeMap {
                    height: 380px !important;
                }
                .public-map-toolbar {
                    flex-direction: column !important;
                    align-items: stretch !important;
                }
                .public-map-filters {
                    flex-direction: column !important;
                    align-items: stretch !important;
                }
                .public-map-filters > div {
                    width: 100% !important;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const rawDestinations = @json($mapDestinations);
                const mapEl = document.getElementById('publicHomeMap');
                if (!mapEl || typeof L === 'undefined') return;

                // Center of Indonesian Archipelago
                const defaultLat = -2.548926;
                const defaultLng = 118.0148634;
                const defaultZoom = 5;

                const map = L.map('publicHomeMap', {
                    scrollWheelZoom: false,
                }).setView([defaultLat, defaultLng], defaultZoom);

                // Enable scroll on click
                map.on('focus', function () {
                    map.scrollWheelZoom.enable();
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);

                let currentMarkers = [];

                function buildPopupContent(d) {
                    let imgHtml = '';
                    if (d.image) {
                        imgHtml = '<div style="width: 100%; height: 110px; overflow: hidden; border-radius: 11px 11px 0 0; background: #e2e8f0;">' +
                            '<img src="' + d.image + '" alt="' + d.name + '" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.parentElement.style.display=\'none\'">' +
                            '</div>';
                    }

                    let ratingHtml = '';
                    if (d.rating) {
                        ratingHtml = '<span style="font-size: 11.5px; font-weight: 700; color: #f59e0b; display: inline-flex; align-items: center; gap: 3px;">' +
                            '<i class="fa-solid fa-star" style="font-size: 10px;"></i> ' + parseFloat(d.rating).toFixed(1) +
                            '</span>';
                    }

                    return '<div style="width: 250px; font-family: inherit;">' +
                        imgHtml +
                        '<div style="padding: 10px 12px;">' +
                            '<div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 4px;">' +
                                '<span style="font-size: 10.5px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 9999px;">' +
                                    (d.category || 'Wisata') +
                                '</span>' +
                                ratingHtml +
                            '</div>' +
                            '<h4 style="margin: 0 0 4px 0; font-size: 13.5px; font-weight: 700; color: #0f172a; line-height: 1.3;">' +
                                d.name +
                            '</h4>' +
                            '<div style="font-size: 12px; color: #64748b; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">' +
                                '<i class="fa-solid fa-location-dot" style="color: #ef4444; font-size: 11px;"></i>' +
                                '<span>' + (d.province || 'Indonesia') + '</span>' +
                            '</div>' +
                            '<div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; margin-top: 6px; border-top: 1px solid #f1f5f9; font-size: 12px;">' +
                                '<div>' +
                                    '<span style="color: #94a3b8; font-size: 10.5px; display: block;">Tiket Masuk</span>' +
                                    '<strong style="color: #0f172a; font-size: 12px;">' + (d.price || 'Gratis') + '</strong>' +
                                '</div>' +
                                '<a href="' + d.url + '" style="background: #1a56db; color: #ffffff; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">' +
                                    '<span>Detail</span>' +
                                    '<i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>' +
                                '</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }

                function renderMarkers(items) {
                    currentMarkers.forEach(function (m) { map.removeLayer(m); });
                    currentMarkers = [];

                    const bounds = [];
                    items.forEach(function (d) {
                        if (d.lat && d.lng) {
                            const marker = L.marker([d.lat, d.lng]).addTo(map);
                            marker.bindPopup(buildPopupContent(d), {
                                maxWidth: 280,
                                className: 'home-map-popup'
                            });
                            currentMarkers.push(marker);
                            bounds.push([d.lat, d.lng]);
                        }
                    });

                    const countEl = document.getElementById('publicMapCount');
                    if (countEl) {
                        countEl.textContent = items.length;
                    }

                    if (bounds.length > 1) {
                        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
                    } else if (bounds.length === 1) {
                        map.setView(bounds[0], 12);
                    }
                }

                renderMarkers(rawDestinations);

                // Filtering logic
                const provinceSelect = document.getElementById('publicMapProvince');
                const categorySelect = document.getElementById('publicMapCategory');
                const searchInput = document.getElementById('publicMapSearch');
                const resetBtn = document.getElementById('publicMapResetBtn');

                function applyFilter() {
                    const prov = (provinceSelect ? provinceSelect.value : '').toLowerCase();
                    const cat = (categorySelect ? categorySelect.value : '').toLowerCase();
                    const q = (searchInput ? searchInput.value.trim() : '').toLowerCase();

                    const filtered = rawDestinations.filter(function (d) {
                        const matchProv = !prov || (d.province && d.province.toLowerCase() === prov);
                        const matchCat = !cat || (d.category && d.category.toLowerCase() === cat);
                        const matchQ = !q ||
                            (d.name && d.name.toLowerCase().indexOf(q) !== -1) ||
                            (d.province && d.province.toLowerCase().indexOf(q) !== -1);
                        return matchProv && matchCat && matchQ;
                    });

                    renderMarkers(filtered);
                }

                if (provinceSelect) provinceSelect.addEventListener('change', applyFilter);
                if (categorySelect) categorySelect.addEventListener('change', applyFilter);
                if (searchInput) searchInput.addEventListener('input', applyFilter);

                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        if (provinceSelect) provinceSelect.value = '';
                        if (categorySelect) categorySelect.value = '';
                        if (searchInput) searchInput.value = '';
                        renderMarkers(rawDestinations);
                        map.setView([defaultLat, defaultLng], defaultZoom);
                    });
                }

                // Regional focus quick buttons
                document.querySelectorAll('.map-region-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const lat = parseFloat(this.getAttribute('data-lat'));
                        const lng = parseFloat(this.getAttribute('data-lng'));
                        const zoom = parseInt(this.getAttribute('data-zoom'), 10);
                        map.flyTo([lat, lng], zoom, { duration: 1.2 });
                    });
                });
            });
        </script>
    @endpush
@endsection
