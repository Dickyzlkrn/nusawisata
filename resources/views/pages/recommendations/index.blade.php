@extends('layouts.app')

@section('title', 'Sistem Rekomendasi Wisata Cerdas — NusaWisata')

@section('content')
<section class="recommendation-section">
    <div class="container">
        <!-- Header -->
        <div class="recommendation-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; margin-bottom: 32px;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 8px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-microchip"></i> Machine Learning Recommendation Engine
                </span>
                <h1 class="section-title">Rekomendasi Wisata Cerdas</h1>
                <p class="section-desc" style="max-width: 680px;">
                    Sistem rekomendasi hibrida yang mengintegrasikan <strong>User-Based Collaborative Filtering</strong> dan <strong>K-Means Clustering</strong> untuk memberikan pilihan wisata nusantara yang paling relevan dengan preferensi ulasan Anda.
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <button type="button" id="btnGenerateRec" class="btn-primary" style="padding: 12px 24px; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; border-radius: var(--radius-pill); box-shadow: var(--shadow-md);">
                    <i class="fa-solid fa-arrows-rotate" id="btnRecIcon"></i>
                    <span id="btnRecLabel">Cari Rekomendasi Baru</span>
                </button>
            </div>
        </div>

        <!-- Interactive Process Modal Pop-up (Centered Overlay) -->
        <div id="processModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);" aria-modal="true" role="dialog" aria-labelledby="modalTitle">
            <div id="processModalDialog" style="background: #ffffff; border-radius: 24px; max-width: 560px; width: 100%; padding: 32px 30px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(226, 232, 240, 0.9); transform: scale(0.92) translateY(16px); opacity: 0; transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); position: relative; overflow: hidden;">
                
                <!-- Decorative subtle top border accent -->
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #3b82f6, #0ea5e9, #10b981);"></div>

                <!-- Header -->
                <div style="text-align: center; margin-bottom: 22px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 14px; border-radius: var(--radius-pill); margin-bottom: 12px;">
                        <i class="fa-solid fa-microchip" style="animation: spin 3s linear infinite;"></i> Algoritma Machine Learning Bekerja
                    </div>
                    <h3 id="modalTitle" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
                        Menghitung Rekomendasi Wisata
                    </h3>
                    <p id="processSubText" style="font-size: 13.5px; color: #64748b; margin: 0; line-height: 1.5;">
                        Mempersiapkan analisis preferensi perjalanan dan klaster wisatawan...
                    </p>
                </div>

                <!-- Currently Active Stage Card -->
                <div id="activeStageCard" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; transition: all 0.25s ease;">
                    <div id="activeStageIconWrap" style="width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">
                        <i id="activeStageIcon" class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.04em;">Tahap Algoritma</div>
                        <div id="activeStageName" style="font-size: 13.5px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            Memvalidasi preferensi filter pencarian...
                        </div>
                    </div>
                    <div id="activeStagePercent" style="font-size: 14px; font-weight: 700; color: #2563eb; padding-left: 8px;">
                        10%
                    </div>
                </div>

                <!-- Progress Bar -->
                <div style="background: #f1f5f9; height: 7px; border-radius: var(--radius-pill); overflow: hidden; margin-bottom: 20px;">
                    <div id="recProgressBar" style="width: 10%; height: 100%; background: linear-gradient(90deg, #3b82f6, #0284c7); border-radius: var(--radius-pill); transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);"></div>
                </div>

                <!-- Step Checklist -->
                <div id="stageList" style="display: flex; flex-direction: column; gap: 7px; font-size: 13px; color: #475569; max-height: 250px; overflow-y: auto; padding-right: 4px;">
                    <div class="rec-stage-item" id="stage-loading_profile" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Memvalidasi preferensi filter & riwayat ulasan</span>
                    </div>
                    <div class="rec-stage-item" id="stage-feature_engineering" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Menganalisis pola rating dan preferensi perjalanan</span>
                    </div>
                    <div class="rec-stage-item" id="stage-clustering" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Menentukan klaster segmentasi wisatawan (K-Means)</span>
                    </div>
                    <div class="rec-stage-item" id="stage-finding_neighbors" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Mencari wisatawan terdekat di klaster serupa</span>
                    </div>
                    <div class="rec-stage-item" id="stage-calculating_similarity" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Menghitung kemiripan preferensi (Cosine Similarity)</span>
                    </div>
                    <div class="rec-stage-item" id="stage-predicting_ratings" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Memprediksi skor kecocokan destinasi (Weighted Prediction)</span>
                    </div>
                    <div class="rec-stage-item" id="stage-ranking" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label">Menyusun peringkat rekomendasi terbaik (Top-N Ranking)</span>
                    </div>
                    <div class="rec-stage-item" id="stage-completed" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 6px; transition: background 0.2s;">
                        <span class="stage-icon" style="display: inline-block; width: 16px; text-align: center; font-size: 12px; color: #94a3b8;">○</span>
                        <span class="stage-label" style="font-weight: 600;">Rekomendasi siap ditampilkan</span>
                    </div>
                </div>

                <!-- Footer note -->
                <div style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: #94a3b8;">
                    <span><i class="fa-solid fa-lock" style="margin-right: 4px;"></i> Komputasi Model Riil</span>
                    <span>NusaWisata Hybrid Engine</span>
                </div>
            </div>
        </div>

        <!-- Research Context & Algoritma Explainer Banner -->
        <div style="background: #0f172a; color: #fff; padding: 28px 32px; border-radius: var(--radius-xl); margin-bottom: 40px; box-shadow: var(--shadow-md);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: center;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(96, 165, 250, 0.2); color: #93c5fd; padding: 3px 10px; border-radius: var(--radius-pill);">
                            Metodologi Penelitian
                        </span>
                        @if(isset($recommendationResult['cluster_id']))
                            <span style="font-size: 11px; font-weight: 700; background: rgba(34, 197, 94, 0.2); color: #86efac; padding: 3px 10px; border-radius: var(--radius-pill);">
                                Klaster {{ $recommendationResult['cluster_id'] }}
                            </span>
                        @endif
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #fff;">
                        Integrasi K-Means & Collaborative Filtering
                    </h3>
                    <p style="font-size: 13.5px; line-height: 1.65; color: rgba(255, 255, 255, 0.8); margin-bottom: 12px;">
                        Rekomendasi dihitung melalui kesamaan vektor penilaian pengguna (Cosine Similarity) di dalam klaster segmentasi wisatawan. Pendekatan ini mengatasi masalah cold-start dan meningkatkan keragaman rekomendasi di seluruh 38 provinsi Indonesia.
                    </p>
                    @auth
                        <div style="background: rgba(255, 255, 255, 0.08); padding: 10px 14px; border-radius: var(--radius-md); font-size: 13px; color: #e2e8f0;">
                            @if(!empty($recommendationResult['is_cold_start']))
                                <i class="fa-solid fa-circle-info" style="color: #fbbf24; margin-right: 6px;"></i>
                                Anda belum memiliki riwayat rating. Menampilkan rekomendasi awal berbasis popularitas nasional.
                            @else
                                <i class="fa-solid fa-circle-check" style="color: #4ade80; margin-right: 6px;"></i>
                                Anda terhubung ke <strong>Klaster {{ $user->cluster_id ?? 1 }}</strong> dengan {{ count($recommendationResult['neighbors_used'] ?? []) }} wisatawan pembanding terdekat.
                            @endif
                        </div>
                    @else
                        <div style="background: rgba(255, 255, 255, 0.08); padding: 10px 14px; border-radius: var(--radius-md); font-size: 13px; color: #e2e8f0;">
                            <i class="fa-solid fa-arrow-right-to-bracket" style="color: #fbbf24; margin-right: 6px;"></i>
                            <a href="{{ route('login') }}" style="color: #93c5fd; font-weight: 600; text-decoration: underline;">Masuk ke akun Anda</a> untuk rekomendasi terpersonalisasi berdasarkan riwayat ulasan Anda.
                        </div>
                    @endauth
                </div>

                <div style="background: rgba(255, 255, 255, 0.04); padding: 20px 24px; border-radius: var(--radius-lg); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 10px; color: #93c5fd;">Alur Eksekusi Rekomendasi:</h4>
                    <ul style="font-size: 12.5px; line-height: 1.7; color: rgba(255, 255, 255, 0.75); margin: 0; padding-left: 18px;">
                        <li><strong>1. Pengambilan Data:</strong> Membaca rating ulasan wisatawan aktif.</li>
                        <li><strong>2. K-Means Segmentasi:</strong> Mengelompokkan pengguna berdasarkan pola aktivitas & preferensi.</li>
                        <li><strong>3. Cosine Similarity:</strong> Menghitung kemiripan preferensi dengan tetangga terdekat.</li>
                        <li><strong>4. Prediksi Nilai:</strong> Memperkirakan kecocokan destinasi yang belum pernah dikunjungi.</li>
                        <li><strong>5. Peringkat Top-N:</strong> Menyajikan destinasi wisata dengan skor kecocokan tertinggi.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Cold Start Notification Banner (If User Has No Ratings) -->
        @auth
            @if(!empty($recommendationResult['is_cold_start']) || empty($user->ratings()->count()))
                <div id="coldStartBanner" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 18px 24px; border-radius: var(--radius-lg); margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h4 style="font-size: 15px; font-weight: 700; color: #92400e; margin-bottom: 4px;">
                            Kenali NusaWisata — Rekomendasi Awal Wisatawan
                        </h4>
                        <p style="font-size: 13.5px; color: #b45309; margin: 0; max-width: 720px;">
                            Anda belum memberikan ulasan atau rating destinasi. Sistem menyajikan destinasi unggulan paling populer di Indonesia sebagai rekomendasi awal.
                        </p>
                    </div>
                    <a href="{{ route('destinations.index') }}" class="btn-outline" style="font-size: 13px; padding: 8px 18px; border-radius: var(--radius-pill); border-color: #d97706; color: #b45309; background: #fff;">
                        <i class="fa-solid fa-star" style="color: #f59e0b; margin-right: 6px;"></i> Beri Rating Wisata
                    </a>
                </div>
            @endif
        @endauth

        <!-- Travel Discovery & Recommendation Filter Card -->
        <div class="recommendation-filter-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-xl); padding: 24px 28px; margin-bottom: 36px; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Preferensi Pencarian Wisata</h3>
                        <p style="font-size: 12.5px; color: #64748b; margin: 0;">Filter kandidat destinasi sebelum dihitung dan diranking oleh model Machine Learning</p>
                    </div>
                </div>

                @if($hasActiveFilters)
                    <a href="{{ route('recommendations.index') }}" class="btn-outline" style="font-size: 12.5px; padding: 6px 14px; border-radius: var(--radius-pill); text-decoration: none; color: #64748b;">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filter
                    </a>
                @endif
            </div>

            <form id="recommendationFilterForm" method="GET" action="{{ route('recommendations.index') }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <!-- Provinsi / Wilayah -->
                    <div>
                        <label for="filterProvince" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em;">
                            <i class="fa-solid fa-location-dot" style="color: var(--color-accent); margin-right: 4px;"></i> Daerah / Provinsi
                        </label>
                        <select id="filterProvince" name="province" class="form-select" style="width: 100%; font-size: 13.5px; padding: 10px 14px; border-radius: var(--radius-md);">
                            <option value="all">Semua Provinsi (38)</option>
                            @foreach($filterOptions['provinces'] as $p)
                                <option value="{{ $p['id'] }}" {{ (string)($filters['province'] ?? '') === (string)$p['id'] || ($filters['province'] ?? '') === $p['slug'] ? 'selected' : '' }}>
                                    {{ $p['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kategori / Jenis Wisata -->
                    <div>
                        <label for="filterCategory" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em;">
                            <i class="fa-solid fa-layer-group" style="color: #3b82f6; margin-right: 4px;"></i> Jenis Wisata
                        </label>
                        <select id="filterCategory" name="category" class="form-select" style="width: 100%; font-size: 13.5px; padding: 10px 14px; border-radius: var(--radius-md);">
                            @foreach($filterOptions['categories'] as $catKey => $catLabel)
                                <option value="{{ $catKey }}" {{ ($filters['category'] ?? 'all') === $catKey ? 'selected' : '' }}>
                                    {{ $catLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rentang Budget / Harga -->
                    <div>
                        <label for="filterBudget" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em;">
                            <i class="fa-solid fa-wallet" style="color: #10b981; margin-right: 4px;"></i> Rentang Budget
                        </label>
                        <select id="filterBudget" name="budget" class="form-select" style="width: 100%; font-size: 13.5px; padding: 10px 14px; border-radius: var(--radius-md);">
                            @foreach($filterOptions['budgets'] as $bKey => $bLabel)
                                <option value="{{ $bKey }}" {{ ($filters['budget'] ?? 'all') === $bKey ? 'selected' : '' }}>
                                    {{ $bLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Minimal Rating -->
                    <div>
                        <label for="filterMinRating" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em;">
                            <i class="fa-solid fa-star" style="color: #f59e0b; margin-right: 4px;"></i> Minimal Rating
                        </label>
                        <select id="filterMinRating" name="min_rating" class="form-select" style="width: 100%; font-size: 13.5px; padding: 10px 14px; border-radius: var(--radius-md);">
                            @foreach($filterOptions['ratings'] as $rKey => $rLabel)
                                <option value="{{ $rKey }}" {{ (string)($filters['min_rating'] ?? 'all') === (string)$rKey ? 'selected' : '' }}>
                                    {{ $rLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Keyword & Action Buttons Row -->
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 220px; position: relative;">
                        <input type="text" id="filterKeyword" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Cari nama destinasi, pantai, candi, pulau..." class="form-input" style="width: 100%; font-size: 13.5px; padding: 10px 16px; border-radius: var(--radius-md);">
                    </div>

                    <button type="submit" id="btnApplyFilter" class="btn-primary" style="padding: 10px 22px; font-size: 13.5px; font-weight: 600; border-radius: var(--radius-md); display: inline-flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Terapkan & Cari Rekomendasi</span>
                    </button>
                </div>
            </form>

            <!-- Active Filter Badges & Context Info -->
            @if($hasActiveFilters)
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Filter Aktif:</span>
                        @foreach($activeBadges as $badgeKey => $badgeLabel)
                            @php
                                $badgeParams = request()->except($badgeKey);
                                $removeSingleUrl = route('recommendations.index', $badgeParams);
                            @endphp
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 12px; padding: 4px 10px; border-radius: var(--radius-pill); display: inline-flex; align-items: center; gap: 6px;">
                                {{ $badgeLabel }}
                                <a href="{{ $removeSingleUrl }}" style="color: #0284c7; text-decoration: none; font-weight: 700; margin-left: 2px;" title="Hapus filter ini">&times;</a>
                            </span>
                        @endforeach
                        <a href="{{ route('recommendations.index') }}" style="font-size: 12px; color: #ef4444; text-decoration: underline; margin-left: 4px; font-weight: 500;">
                            Reset Semua
                        </a>
                    </div>

                    <div style="font-size: 12.5px; color: #475569;">
                        Lolos Filter: <strong style="color: #0f172a;">{{ $candidateCount ?? $recommendations->count() }} destinasi</strong> kandidat
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Recommendation Results Grid -->
        <div id="resultsSection" style="margin-bottom: 56px;">
            <div class="destinations-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div>
                    <h2 class="section-title" id="resultsTitle">
                        @auth
                            Hasil Rekomendasi Untuk Anda
                        @else
                            Rekomendasi Destinasi Unggulan
                        @endauth
                    </h2>
                    <p class="section-desc" id="resultsDesc" style="margin: 0;">
                        @auth
                            @if(!empty($recommendationResult['is_fallback']))
                                <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px;">
                                    <i class="fa-solid fa-compass"></i> Mode Awal / Populer
                                </span>
                                {{ $recommendationResult['explanation'] ?? 'Destinasi terpopuler nasional berdasarkan ulasan komunitas.' }}
                            @else
                                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px; background: #dcfce7; color: #15803d;">
                                    <i class="fa-solid fa-circle-check"></i> Collaborative Filtering Aktif
                                </span>
                                Destinasi terpilih berdasarkan kemiripan pola rating Anda dengan wisatawan lain di Klaster {{ $recommendationResult['cluster_id'] ?? 1 }}{{ $hasActiveFilters ? ' (sesuai filter)' : '' }}.
                            @endif
                        @else
                            @if(!empty($recommendationResult['is_fallback']) && $hasActiveFilters)
                                <span class="badge badge-info" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px;">
                                    <i class="fa-solid fa-filter"></i> Sesuai Filter
                                </span>
                            @endif
                            Destinasi teratas berdasarkan ulasan komunitas wisatawan nusantara{{ $hasActiveFilters ? ' yang memenuhi kriteria filter' : '' }}.
                        @endauth
                    </p>
                </div>
            </div>

            <div id="destinationsGrid" class="destination-grid">
                @forelse($recommendations as $dest)
                    <div class="dest-list-card" style="position: relative; display: flex; flex-direction: column;">
                        <div class="dest-list-card-img" style="position: relative;">
                            <a href="{{ route('destinations.show', $dest) }}">
                                <img src="{{ $dest->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=80' }}" alt="{{ $dest->name }}">
                            </a>
                            @if(isset($dest->recommendation_rank))
                                <span style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(6px); color: #f59e0b; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: var(--radius-pill); border: 1px solid rgba(245, 158, 11, 0.3);">
                                    #{{ $dest->recommendation_rank }} Rekomendasi
                                </span>
                            @endif
                        </div>
                        <div class="dest-list-card-body" style="flex: 1; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="badge badge-primary">{{ $dest->category }}</span>
                                <span style="font-weight: 700; color: #b45309; background: #fef3c7; padding: 3px 8px; border-radius: var(--radius-pill); font-size: 12px;">
                                    <i class="fa-solid fa-star"></i> Prediksi: {{ number_format($dest->predicted_rating ?? $dest->google_rating, 2) }}
                                </span>
                            </div>
                            <a href="{{ route('destinations.show', $dest) }}" style="text-decoration: none;">
                                <h4 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">{{ $dest->name }}</h4>
                            </a>
                            <div class="dest-list-card-meta" style="margin-bottom: 10px;">
                                <span>
                                    <i class="fa-solid fa-location-dot" style="color: var(--color-accent);"></i>
                                    {{ $dest->province->name ?? 'Indonesia' }}
                                </span>
                                <span>
                                    <i class="fa-solid fa-users"></i>
                                    {{ number_format($dest->review_count) }} ulasan
                                </span>
                            </div>

                            @if(!empty($dest->recommendation_reason))
                                <div style="font-size: 12px; color: #475569; background: #f8fafc; padding: 8px 12px; border-radius: var(--radius-md); margin-bottom: 12px;">
                                    <i class="fa-solid fa-lightbulb" style="color: var(--color-primary); margin-right: 4px;"></i>
                                    {{ $dest->recommendation_reason }}
                                </div>
                            @else
                                <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 12px;">{{ Str::limit($dest->description, 90) }}</p>
                            @endif

                            <div class="dest-list-card-footer" style="margin-top: auto; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <span class="dest-list-card-price" style="font-weight: 700; color: #0f172a;">{{ $dest->formatted_price }}</span>
                                <a href="{{ route('destinations.show', $dest) }}" class="btn-outline" style="padding: 6px 14px; font-size: 12.5px; border-radius: var(--radius-pill);">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: var(--radius-xl); padding: 56px 24px; text-align: center; margin: 16px 0;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; color: #94a3b8; font-size: 24px;">
                            <i class="fa-solid fa-compass"></i>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                            Belum Ditemukan Destinasi yang Sesuai
                        </h3>
                        <p style="font-size: 14px; color: #64748b; max-width: 520px; margin: 0 auto 20px; line-height: 1.6;">
                            Kombinasi preferensi dan filter yang Anda pilih belum menemukan destinasi wisata yang cocok. Coba sesuaikan budget, pilih jenis wisata lain, atau reset filter untuk menemukan destinasi rekomendasi terbaik.
                        </p>
                        <a href="{{ route('recommendations.index') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 22px; border-radius: var(--radius-pill);">
                            <i class="fa-solid fa-rotate-left"></i> Reset Semua Filter
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Popular Destinations Section -->
        @if($popular->count() > 0)
            <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #e2e8f0;">
                <x-ui.section-heading 
                    title="Destinasi Populer Favorit Nasional"
                    subtitle="Tempat-tempat wisata dengan volume kunjungan dan ulasan terbanyak di Indonesia."
                    actionText="Semua Destinasi"
                    actionUrl="{{ route('destinations.index') }}"
                />

                <div class="destination-grid" style="grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    @foreach($popular as $pop)
                        <x-cards.destination-card :destination="$pop" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnGenerateRec');
    const btnIcon = document.getElementById('btnRecIcon');
    const btnLabel = document.getElementById('btnRecLabel');
    const processModal = document.getElementById('processModal');
    const processModalDialog = document.getElementById('processModalDialog');
    const activeStageCard = document.getElementById('activeStageCard');
    const activeStageIconWrap = document.getElementById('activeStageIconWrap');
    const activeStageIcon = document.getElementById('activeStageIcon');
    const activeStageName = document.getElementById('activeStageName');
    const activeStagePercent = document.getElementById('activeStagePercent');
    const progressBar = document.getElementById('recProgressBar');
    const processSubText = document.getElementById('processSubText');
    const destinationsGrid = document.getElementById('destinationsGrid');
    const resultsTitle = document.getElementById('resultsTitle');
    const resultsDesc = document.getElementById('resultsDesc');
    const coldStartBanner = document.getElementById('coldStartBanner');
    const filterForm = document.getElementById('recommendationFilterForm');
    const btnApplyFilter = document.getElementById('btnApplyFilter');

    const stages = [
        {
            id: 'loading_profile',
            label: 'Validasi Filter & Profil Ulasan',
            name: 'Memvalidasi preferensi filter pencarian & riwayat ulasan...',
            sub: 'Menganalisis kriteria pencarian dan memuat profil preferensi perjalanan Anda...',
            percent: 15,
            delay: 650
        },
        {
            id: 'feature_engineering',
            label: 'Ekstraksi Vektor Preferensi',
            name: 'Menganalisis pola rating ulasan perjalanan...',
            sub: 'Membangun representasi vektor ulasan untuk pemodelan machine learning...',
            percent: 30,
            delay: 600
        },
        {
            id: 'clustering',
            label: 'Segmentasi Wisatawan (K-Means)',
            name: 'Memuat profil klaster segmentasi wisatawan...',
            sub: 'Mengidentifikasi klaster wisatawan berdasarkan hasil K-Means sebelumnya...',
            percent: 45,
            delay: 750
        },
        {
            id: 'finding_neighbors',
            label: 'Pencarian Wisatawan Serupa (Peer)',
            name: 'Mencari wisatawan terdekat di klaster yang sama...',
            sub: 'Menyaring wisatawan pembanding dengan kesamaan minat ulasan...',
            percent: 60,
            delay: 650
        },
        {
            id: 'calculating_similarity',
            label: 'Perhitungan Cosine Similarity',
            name: 'Menghitung kemiripan preferensi (Cosine Similarity)...',
            sub: 'Mengukur sudut korelasi kedekatan antar-vektor ulasan wisatawan...',
            percent: 75,
            delay: 700
        },
        {
            id: 'predicting_ratings',
            label: 'Prediksi Skor Nilai Kecocokan',
            name: 'Memprediksi nilai kecocokan destinasi (Weighted Prediction)...',
            sub: 'Menghitung estimasi rating tertimbang untuk destinasi kandidat...',
            percent: 88,
            delay: 700
        },
        {
            id: 'ranking',
            label: 'Perankingan Rekomendasi (Top-N)',
            name: 'Menyusun peringkat rekomendasi terbaik (Top-N Ranking)...',
            sub: 'Mengurutkan destinasi wisata kandidat dengan nilai estimasi tertinggi...',
            percent: 96,
            delay: 600
        },
        {
            id: 'completed',
            label: 'Rekomendasi Siap Ditampilkan',
            name: 'Perhitungan rekomendasi selesai!',
            sub: 'Menyiapkan kartu destinasi wisata terbaik untuk Anda...',
            percent: 100,
            delay: 600
        }
    ];

    function getFilterPayload() {
        return {
            province: document.getElementById('filterProvince')?.value || '',
            category: document.getElementById('filterCategory')?.value || '',
            budget: document.getElementById('filterBudget')?.value || '',
            min_rating: document.getElementById('filterMinRating')?.value || '',
            keyword: document.getElementById('filterKeyword')?.value || ''
        };
    }

    function syncUrlParams(filters) {
        const query = new URLSearchParams();
        if (filters.province && filters.province !== 'all') query.set('province', filters.province);
        if (filters.category && filters.category !== 'all') query.set('category', filters.category);
        if (filters.budget && filters.budget !== 'all') query.set('budget', filters.budget);
        if (filters.min_rating && filters.min_rating !== 'all') query.set('min_rating', filters.min_rating);
        if (filters.keyword && filters.keyword.trim() !== '') query.set('keyword', filters.keyword.trim());

        const newUrl = window.location.pathname + (query.toString() ? '?' + query.toString() : '');
        window.history.replaceState(null, '', newUrl);
    }

    function openModal() {
        document.body.style.overflow = 'hidden';
        processModal.style.display = 'flex';
        void processModal.offsetWidth;
        processModal.style.opacity = '1';
        processModalDialog.style.opacity = '1';
        processModalDialog.style.transform = 'scale(1) translateY(0)';
    }

    function closeModal() {
        processModal.style.opacity = '0';
        processModalDialog.style.opacity = '0';
        processModalDialog.style.transform = 'scale(0.92) translateY(16px)';
        setTimeout(() => {
            processModal.style.display = 'none';
            document.body.style.overflow = '';
        }, 350);
    }

    function resetModalState() {
        progressBar.style.width = '10%';
        activeStagePercent.textContent = '10%';
        activeStagePercent.style.color = '#2563eb';
        activeStageCard.style.borderLeftColor = '#2563eb';
        activeStageCard.style.background = '#f8fafc';
        activeStageIconWrap.style.background = '#eff6ff';
        activeStageIconWrap.style.color = '#2563eb';
        activeStageIcon.className = 'fa-solid fa-spinner fa-spin';
        activeStageName.textContent = 'Memulai proses algoritma rekomendasi...';
        processSubText.textContent = 'Menghubungi engine machine learning backend NusaWisata...';

        stages.forEach(s => {
            const el = document.getElementById(`stage-${s.id}`);
            if (el) {
                el.style.background = 'transparent';
                const icon = el.querySelector('.stage-icon');
                if (icon) {
                    icon.innerHTML = '○';
                    icon.style.color = '#94a3b8';
                }
                const label = el.querySelector('.stage-label');
                if (label) {
                    label.style.fontWeight = 'normal';
                    label.style.color = '#64748b';
                }
            }
        });
    }

    function setStageActive(stageObj, customName = null) {
        activeStageName.textContent = customName || stageObj.name;
        processSubText.textContent = stageObj.sub;
        activeStagePercent.textContent = `${stageObj.percent}%`;
        progressBar.style.width = `${stageObj.percent}%`;

        const el = document.getElementById(`stage-${stageObj.id}`);
        if (el) {
            el.style.background = '#f1f5f9';
            const icon = el.querySelector('.stage-icon');
            if (icon) {
                icon.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color: #2563eb; font-size: 11px;"></i>';
            }
            const label = el.querySelector('.stage-label');
            if (label) {
                label.style.fontWeight = '600';
                label.style.color = '#0f172a';
            }
        }
    }

    function setStageDone(stageObj) {
        const el = document.getElementById(`stage-${stageObj.id}`);
        if (el) {
            el.style.background = 'transparent';
            const icon = el.querySelector('.stage-icon');
            if (icon) {
                icon.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 13px;"></i>';
            }
            const label = el.querySelector('.stage-label');
            if (label) {
                label.style.fontWeight = '500';
                label.style.color = '#334155';
            }
        }
    }

    async function runRecommendationPipeline() {
        if (btn.disabled) return;

        const filters = getFilterPayload();
        syncUrlParams(filters);

        // 1. Set UI to processing state
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        btnIcon.classList.add('fa-spin');
        btnLabel.textContent = 'Memproses...';

        if (btnApplyFilter) {
            btnApplyFilter.disabled = true;
            btnApplyFilter.style.opacity = '0.7';
        }

        // Open Pop-up Animation Modal
        openModal();
        resetModalState();

        try {
            // Start AJAX call to backend immediately
            const requestPromise = fetch('{{ route("recommendations.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    limit: 9,
                    ...filters
                })
            });

            // 2. Iterate through algorithm stages with comfortable, user-friendly pacing
            let backendData = null;

            for (let i = 0; i < stages.length; i++) {
                const stage = stages[i];

                let customStageName = null;
                if (stage.id === 'clustering' && backendData?.cluster_id) {
                    customStageName = `Profil Anda dianalisis di Klaster ${backendData.cluster_id} (K-Means)`;
                    const clusterEl = document.getElementById('stage-clustering');
                    if (clusterEl) {
                        const lbl = clusterEl.querySelector('.stage-label');
                        if (lbl) lbl.textContent = `Klaster Teridentifikasi: Klaster ${backendData.cluster_id}`;
                    }
                }

                setStageActive(stage, customStageName);

                // Fetch backend response early on stage 3 if available
                if (i === 2 && !backendData) {
                    const response = await requestPromise;
                    if (!response.ok) {
                        throw new Error('Gagal memproses rekomendasi.');
                    }
                    backendData = await response.json();
                }

                // Comfortable pacing delay
                await new Promise(resolve => setTimeout(resolve, stage.delay));
                setStageDone(stage);
            }

            // Ensure backendData is ready
            if (!backendData) {
                const response = await requestPromise;
                if (!response.ok) {
                    throw new Error('Gagal memproses rekomendasi.');
                }
                backendData = await response.json();
            }

            // 3. Fetch full calculated results
            const runId = backendData.run_id;
            const resultResp = await fetch(`/recommendations/${runId}/result`, {
                headers: { 'Accept': 'application/json' }
            });
            const resultData = await resultResp.json();
            const payload = resultData.payload || {};

            // 4. Render updated destination cards smoothly
            renderDestinationCards(payload);

            // 5. Completion state in modal
            activeStageCard.style.borderLeftColor = '#16a34a';
            activeStageCard.style.background = '#f0fdf4';
            activeStageIconWrap.style.background = '#dcfce7';
            activeStageIconWrap.style.color = '#16a34a';
            activeStageIcon.className = 'fa-solid fa-check';
            activeStageName.textContent = 'Rekomendasi Berhasil Dihitung!';
            activeStagePercent.textContent = '100%';
            activeStagePercent.style.color = '#16a34a';
            processSubText.textContent = 'Rekomendasi siap! Membuka hasil...';

            // Hold briefly for user satisfaction (650ms), then close modal smoothly
            await new Promise(resolve => setTimeout(resolve, 650));
            closeModal();

            // Smooth scroll to results
            setTimeout(() => {
                document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
            }, 350);

        } catch (err) {
            if (window.NusaAlert) {
                window.NusaAlert.showError('Gagal Memuat Rekomendasi', err.message || 'Terjadi kesalahan saat memproses rekomendasi.');
            } else {
                alert('Terjadi kesalahan: ' + err.message);
            }
            closeModal();
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btnIcon.classList.remove('fa-spin');
            btnLabel.textContent = 'Cari Rekomendasi Baru';

            if (btnApplyFilter) {
                btnApplyFilter.disabled = false;
                btnApplyFilter.style.opacity = '1';
            }
        }
    }

    if (btn) {
        btn.addEventListener('click', runRecommendationPipeline);
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            runRecommendationPipeline();
        });
    }

    function renderDestinationCards(payload) {
        const items = payload.recommendations || [];

        // Handle empty candidates
        if (items.length === 0) {
            if (coldStartBanner) coldStartBanner.style.display = 'none';
            resultsDesc.innerHTML = `<span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px;"><i class="fa-solid fa-triangle-exclamation"></i> Tidak Ditemukan</span> ${payload.explanation || 'Tidak ditemukan destinasi yang sesuai dengan kriteria filter yang Anda tentukan.'}`;
            destinationsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: var(--radius-xl); padding: 56px 24px; text-align: center; margin: 16px 0;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; color: #94a3b8; font-size: 24px;">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                        Belum Ditemukan Destinasi yang Sesuai
                    </h3>
                    <p style="font-size: 14px; color: #64748b; max-width: 520px; margin: 0 auto 20px; line-height: 1.6;">
                        Kombinasi preferensi dan filter yang Anda pilih belum menemukan destinasi wisata yang cocok. Coba sesuaikan budget, pilih jenis wisata lain, atau reset filter untuk menemukan destinasi rekomendasi terbaik.
                    </p>
                    <a href="{{ route('recommendations.index') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 22px; border-radius: var(--radius-pill);">
                        <i class="fa-solid fa-rotate-left"></i> Reset Semua Filter
                    </a>
                </div>
            `;
            return;
        }

        // Update titles & badges
        if (payload.is_cold_start) {
            resultsDesc.innerHTML = `<span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px;"><i class="fa-solid fa-compass"></i> Mode Awal / Populer</span> ${payload.explanation}`;
            if (coldStartBanner) coldStartBanner.style.display = 'flex';
        } else if (payload.is_fallback) {
            resultsDesc.innerHTML = `<span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px;"><i class="fa-solid fa-compass"></i> Mode Populer Sesuai Filter</span> ${payload.explanation}`;
            if (coldStartBanner) coldStartBanner.style.display = 'none';
        } else {
            resultsDesc.innerHTML = `<span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 6px; margin-right: 8px; background: #dcfce7; color: #15803d;"><i class="fa-solid fa-circle-check"></i> Collaborative Filtering Aktif</span> Rekomendasi dihitung berdasarkan kesamaan preferensi wisatawan di Klaster ${payload.cluster_id || 1}.`;
            if (coldStartBanner) coldStartBanner.style.display = 'none';
        }

        let html = '';
        items.forEach(dest => {
            const predRating = parseFloat(dest.predicted_rating || dest.google_rating).toFixed(2);
            html += `
                <div class="dest-list-card" style="position: relative; display: flex; flex-direction: column;">
                    <div class="dest-list-card-img" style="position: relative;">
                        <a href="/destinations/${dest.slug}">
                            <img src="${dest.image}" alt="${dest.name}">
                        </a>
                        <span style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(6px); color: #f59e0b; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: var(--radius-pill); border: 1px solid rgba(245, 158, 11, 0.3);">
                            #${dest.recommendation_rank} Rekomendasi
                        </span>
                    </div>
                    <div class="dest-list-card-body" style="flex: 1; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge badge-primary">${dest.category || 'Wisata'}</span>
                            <span style="font-weight: 700; color: #b45309; background: #fef3c7; padding: 3px 8px; border-radius: var(--radius-pill); font-size: 12px;">
                                <i class="fa-solid fa-star"></i> Prediksi: ${predRating}
                            </span>
                        </div>
                        <a href="/destinations/${dest.slug}" style="text-decoration: none;">
                            <h4 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">${dest.name}</h4>
                        </a>
                        <div class="dest-list-card-meta" style="margin-bottom: 10px;">
                            <span>
                                <i class="fa-solid fa-location-dot" style="color: var(--color-accent);"></i>
                                ${dest.province || 'Indonesia'}
                            </span>
                            <span>
                                <i class="fa-solid fa-users"></i>
                                ${Number(dest.review_count || 0).toLocaleString('id-ID')} ulasan
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #475569; background: #f8fafc; padding: 8px 12px; border-radius: var(--radius-md); margin-bottom: 12px;">
                            <i class="fa-solid fa-lightbulb" style="color: var(--color-primary); margin-right: 4px;"></i>
                            ${dest.recommendation_reason}
                        </div>
                        <div class="dest-list-card-footer" style="margin-top: auto; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <span class="dest-list-card-price" style="font-weight: 700; color: #0f172a;">${dest.formatted_price}</span>
                            <a href="/destinations/${dest.slug}" class="btn-outline" style="padding: 6px 14px; font-size: 12.5px; border-radius: var(--radius-pill);">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        destinationsGrid.innerHTML = html;
    }
});
</script>
@endpush
@endsection
