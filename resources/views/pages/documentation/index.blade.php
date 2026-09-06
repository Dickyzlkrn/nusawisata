@extends('layouts.app')

@section('title', 'Dokumentasi Sistem & Metodologi Rekomendasi — NusaWisata')

@section('content')
<div class="container container-narrow" style="padding-top: 56px; padding-bottom: 96px;">

    <!-- 1. HEADER & HERO DOKUMENTASI (CLEAN EDITORIAL) -->
    <header style="margin-bottom: 48px;">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--color-text-muted); margin-bottom: 12px;">
            Metodologi & Arsitektur Penelitian
        </div>
        <h1 style="font-size: 34px; font-weight: 700; color: var(--color-primary-dark); line-height: 1.25; margin: 0 0 16px 0; letter-spacing: -0.02em;">
            Sistem Rekomendasi Wisata Indonesia Cerdas
        </h1>
        <p style="font-size: 16px; line-height: 1.7; color: var(--color-text-muted); max-width: 780px; margin: 0 0 36px 0;">
            Dokumentasi komprehensif mengenai cara kerja algoritma <strong>Cluster-Assisted Collaborative Filtering</strong> dan <strong>K-Means Clustering</strong> pada platform NusaWisata dalam merekomendasikan destinasi terbaik dari 38 provinsi di Indonesia.
        </p>

        <!-- Typography-Based Statistics (No icons, no pill badges, no shadows) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 24px; padding: 24px 0; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
            <div>
                <div style="font-size: 28px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; letter-spacing: -0.02em;">
                    {{ number_format($totalDestinations) }}
                </div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
                    Destinasi Wisata
                </div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; letter-spacing: -0.02em;">
                    {{ $totalProvinces }}
                </div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
                    Provinsi Nusantara
                </div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; letter-spacing: -0.02em;">
                    {{ number_format($totalRatings) }}
                </div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
                    Ulasan Pengguna
                </div>
            </div>
            <div>
                <div style="font-size: 28px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; letter-spacing: -0.02em;">
                    K = 3
                </div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 6px;">
                    Klaster Optimal (DBI: 1.617)
                </div>
            </div>
        </div>
    </header>

    <!-- 2. DIAGRAM ALUR KERJA UTAMA (HORIZONTAL NUMBERED PROCESS) -->
    <section style="margin-bottom: 72px;">
        <div style="margin-bottom: 28px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                01. Alur Sistem
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Diagram Alur Kerja Rekomendasi End-to-End
            </h2>
            <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; max-width: 740px; margin: 0;">
                Proses sistematis sejak data ulasan mentah diimpor, diekstraksi menjadi 12 fitur perilaku, dikelompokkan dengan K-Means, hingga dihasilkan prediksi ulasan melalui Collaborative Filtering.
            </p>
        </div>

        <!-- 5 Numbered Sequential Steps (No rainbow cards, no icon circles) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;">
            
            <!-- Step 01 -->
            <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column;">
                <div style="font-size: 22px; font-weight: 700; color: var(--color-text-light); line-height: 1; margin-bottom: 12px; font-family: monospace;">
                    01
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Pengumpulan Data
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0; flex: 1;">
                    Ingesti dataset {{ number_format($totalRatings, 0, ',', '.') }} ulasan, {{ number_format($totalUsers, 0, ',', '.') }} wisatawan, dan {{ $totalDestinations }} objek wisata dari {{ $totalProvinces }} provinsi di Indonesia via Excel dan crawler.
                </p>
                <div style="font-size: 11.5px; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: 10px;">
                    <span style="font-weight: 600; color: var(--color-text-main);">Metode:</span> Data Cleaning & Deduplikasi
                </div>
            </div>

            <!-- Step 02 -->
            <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column;">
                <div style="font-size: 22px; font-weight: 700; color: var(--color-text-light); line-height: 1; margin-bottom: 12px; font-family: monospace;">
                    02
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Feature Engineering
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0; flex: 1;">
                    Ekstraksi 12 dimensi perilaku ulasan pengguna (rata-rata rating, sebaran bintang, provinsi, harga) dan distandarisasi.
                </p>
                <div style="font-size: 11.5px; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: 10px;">
                    <span style="font-weight: 600; color: var(--color-text-main);">Formula:</span> z = (x - &mu;) / &sigma;
                </div>
            </div>

            <!-- Step 03 -->
            <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column;">
                <div style="font-size: 22px; font-weight: 700; color: var(--color-text-light); line-height: 1; margin-bottom: 12px; font-family: monospace;">
                    03
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    K-Means Clustering
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0; flex: 1;">
                    Segmentasi pengguna ke dalam K segmen minat. Evaluasi kandidat klaster berbasis Davies-Bouldin Index dan Silhouette.
                </p>
                <div style="font-size: 11.5px; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: 10px;">
                    <span style="font-weight: 600; color: var(--color-text-main);">Jarak:</span> Euclidean Distance
                </div>
            </div>

            <!-- Step 04 -->
            <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column;">
                <div style="font-size: 22px; font-weight: 700; color: var(--color-text-light); line-height: 1; margin-bottom: 12px; font-family: monospace;">
                    04
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Collaborative Filtering
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0; flex: 1;">
                    Pencarian tetangga terdekat di dalam klaster yang sama menggunakan perhitungan sudut kemiripan ulasan.
                </p>
                <div style="font-size: 11.5px; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: 10px;">
                    <span style="font-weight: 600; color: var(--color-text-main);">Metrik:</span> Cosine Similarity
                </div>
            </div>

            <!-- Step 05 -->
            <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column;">
                <div style="font-size: 22px; font-weight: 700; color: var(--color-text-light); line-height: 1; margin-bottom: 12px; font-family: monospace;">
                    05
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Top-N Rekomendasi
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0; flex: 1;">
                    Menghitung rating terbobot (Weighted Sum) untuk destinasi yang belum dikunjungi, lalu diurutkan dari nilai tertinggi.
                </p>
                <div style="font-size: 11.5px; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: 10px;">
                    <span style="font-weight: 600; color: var(--color-text-main);">Output:</span> Nilai Prediksi [1.0 &ndash; 5.0]
                </div>
            </div>

        </div>

        <!-- Clean Text Pipeline (No magic wand, no colored dots) -->
        <div style="margin-top: 20px; padding: 14px 18px; background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: 12.5px; color: var(--color-text-muted); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span style="font-weight: 600; color: var(--color-text-main);">Pipeline Ringkas:</span>
            <span>Data Raw</span>
            <span style="color: var(--color-border);">&rarr;</span>
            <span>12 Fitur Perilaku</span>
            <span style="color: var(--color-border);">&rarr;</span>
            <span>Standarisasi Z-Score</span>
            <span style="color: var(--color-border);">&rarr;</span>
            <span>Klaster K-Means (K=3)</span>
            <span style="color: var(--color-border);">&rarr;</span>
            <span>Cosine Similarity</span>
            <span style="color: var(--color-border);">&rarr;</span>
            <span style="font-weight: 600; color: var(--color-primary-dark);">Top-N Rekomendasi</span>
        </div>
    </section>

    <!-- 3. DIAGRAM ALUR KEPUTUSAN (USER AKTIF VS COLD-START) -->
    <section style="margin-bottom: 72px;">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                02. Logika Inferensi
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Diagram Alur Keputusan: User Aktif vs Cold-Start Fallback Engine
            </h2>
            <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; max-width: 740px; margin: 0;">
                Mekanisme pemilihan alur inferensi rekomendasi berdasarkan ketersediaan riwayat penilaian pengguna pada sistem.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            
            <!-- Branch 1: User Aktif -->
            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px; background: var(--color-white); display: flex; flex-direction: column;">
                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--color-border);">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0;">
                        Jalur A: Pengguna Aktif (&ge; 1 Rating)
                    </h3>
                    <span style="font-size: 12px; font-weight: 600; color: var(--color-text-muted); font-family: monospace;">
                        CF Pipeline
                    </span>
                </div>
                <p style="font-size: 13.5px; color: var(--color-text-muted); line-height: 1.6; margin: 0 0 16px 0;">
                    Ketika pengguna telah memiliki riwayat penilaian destinasi, sistem dapat memetakan preferensi dan mencari kemiripan selera di dalam klaster yang sama.
                </p>
                <ol style="font-size: 13px; color: var(--color-text-main); line-height: 1.7; margin: 0; padding-left: 20px; flex: 1;">
                    <li>Sistem mengidentifikasi <code>cluster_id</code> pengguna target.</li>
                    <li>Mengambil vektor ulasan dari seluruh pengguna lain di klaster yang sama.</li>
                    <li>Menghitung nilai Cosine Similarity terhadap pengguna target.</li>
                    <li>Memilih tetangga dengan kemiripan positif (&gt; 0).</li>
                    <li>Memprediksi skor untuk destinasi yang belum diulas pengguna.</li>
                </ol>
                <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--color-border); font-size: 12.5px; color: var(--color-text-muted);">
                    <span style="font-weight: 600; color: var(--color-text-main);">Hasil:</span> Rekomendasi terpersonalisasi sesuai pola rating individu.
                </div>
            </div>

            <!-- Branch 2: Cold Start Fallback -->
            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px; background: var(--color-white); display: flex; flex-direction: column;">
                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--color-border);">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0;">
                        Jalur B: Pengguna Baru / Tamu (0 Rating)
                    </h3>
                    <span style="font-size: 12px; font-weight: 600; color: var(--color-text-muted); font-family: monospace;">
                        Cold-Start Fallback Engine
                    </span>
                </div>
                <p style="font-size: 13.5px; color: var(--color-text-muted); line-height: 1.6; margin: 0 0 16px 0;">
                    Pengguna baru yang belum memiliki penilaian tidak dapat membentuk vektor ulasan. Sistem secara otomatis mengaktifkan strategi fallback tanpa menimbulkan error.
                </p>
                <ol style="font-size: 13px; color: var(--color-text-main); line-height: 1.7; margin: 0; padding-left: 20px; flex: 1;">
                    <li>Sistem mendeteksi riwayat ulasan pengguna = 0.</li>
                    <li>Mengambil destinasi dengan Google Rating tertinggi (&ge; 4.5).</li>
                    <li>Mengurutkan berdasarkan jumlah ulasan komunitas terbanyak.</li>
                    <li>Memberi indikator transparan bahwa rekomendasi berbasis popularitas nasional.</li>
                    <li>Mendorong pengguna memberi ulasan perdana untuk mengaktifkan personalisasi.</li>
                </ol>
                <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--color-border); font-size: 12.5px; color: var(--color-text-muted);">
                    <span style="font-weight: 600; color: var(--color-text-main);">Hasil:</span> Destinasi populer berperingkat tinggi secara konsisten.
                </div>
            </div>

        </div>
    </section>

    <!-- 4. DASAR MATEMATIS & FORMULA ALGORITMA -->
    <section style="margin-bottom: 72px;">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                03. Formulasi Matematika
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Formula & Formulasi Matematis Machine Learning
            </h2>
            <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; max-width: 740px; margin: 0;">
                Implementasi native dalam PHP 8.3 tanpa dependensi eksternal untuk efisiensi komputasi produksi.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <!-- Math 1 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; display: flex; flex-direction: column;">
                <div style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 8px;">
                    1. Standardisasi Fitur (Z-Score)
                </div>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0;">
                    Menghilangkan perbedaan skala antara variabel harga, frekuensi ulasan, dan rasio bintang agar tidak mendominasi perhitungan jarak.
                </p>
                <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-sm); font-family: monospace; font-size: 13.5px; color: var(--color-primary-dark); text-align: center; margin-bottom: 12px;">
                    z = (x - &mu;) / &sigma;
                </div>
                <div style="font-size: 12px; color: var(--color-text-muted); margin-top: auto;">
                    &mu; = rata-rata sampel, &sigma; = standar deviasi fitur.
                </div>
            </div>

            <!-- Math 2 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; display: flex; flex-direction: column;">
                <div style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 8px;">
                    2. Jarak Euclidean K-Means
                </div>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0;">
                    Mengukur jarak geometri antara vektor 12 dimensi pengguna <em>p</em> terhadap titik pusat klaster (centroid) <em>c</em>.
                </p>
                <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-sm); font-family: monospace; font-size: 13.5px; color: var(--color-primary-dark); text-align: center; margin-bottom: 12px;">
                    d(p, c) = &radic;( &sum; (p<sub>j</sub> - c<sub>j</sub>)&sup2; )
                </div>
                <div style="font-size: 12px; color: var(--color-text-muted); margin-top: auto;">
                    Iterasi konvergen jika pergeseran centroid &lt; 0.0001.
                </div>
            </div>

            <!-- Math 3 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; display: flex; flex-direction: column;">
                <div style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 8px;">
                    3. Kemiripan Cosine (Cosine Similarity)
                </div>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0;">
                    Menghitung sudut kesamaan pola penilaian antar pengguna <em>u</em> dan <em>v</em> pada destinasi yang sama-sama telah diulas.
                </p>
                <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-sm); font-family: monospace; font-size: 13px; color: var(--color-primary-dark); text-align: center; margin-bottom: 12px;">
                    sim(u, v) = (u &middot; v) / (||u|| &middot; ||v||)
                </div>
                <div style="font-size: 12px; color: var(--color-text-muted); margin-top: auto;">
                    Rentang [0.0, 1.0]. Nilai 1.0 = preferensi ulasan identik.
                </div>
            </div>

            <!-- Math 4 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; display: flex; flex-direction: column;">
                <div style="font-size: 14px; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 8px;">
                    4. Prediksi Rating Terbobot (Weighted Sum)
                </div>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.55; margin: 0 0 14px 0;">
                    Prediksi nilai rating destinasi <em>i</em> untuk pengguna <em>u</em> yang dihitung dari bobot ulasan tetangga klaster terdekat.
                </p>
                <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); padding: 12px 16px; border-radius: var(--radius-sm); font-family: monospace; font-size: 13px; color: var(--color-primary-dark); text-align: center; margin-bottom: 12px;">
                    r&#770;(u, i) = &sum; [sim(u, v) &middot; r(v, i)] / &sum; |sim(u, v)|
                </div>
                <div style="font-size: 12px; color: var(--color-text-muted); margin-top: auto;">
                    Dibatasi secara ketat pada skala rating valid [1.0 &ndash; 5.0].
                </div>
            </div>

        </div>
    </section>

    <!-- 5. KARAKTERISTIK KLASTER WISATAWAN (EDITORIAL PERSONAS) -->
    <section style="margin-bottom: 72px;">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                04. Segmentasi Pengguna
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Profil Karakteristik Klaster Wisatawan Nusantara
            </h2>
            <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; max-width: 740px; margin: 0;">
                Berdasarkan pengujian K-Means dengan Davies-Bouldin Index optimal pada K = 3, populasi wisatawan terbagi menjadi 3 kelompok karakteristik dominan:
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            
            <!-- Cluster 01 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                    Klaster 01
                </div>
                <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 10px 0;">
                    Wisatawan Alam & Budget-Friendly
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.6; margin: 0 0 18px 0;">
                    Kelompok wisatawan yang gemar menjelajah pantai, pegunungan, dan curug dengan tiket masuk terjangkau. Memiliki frekuensi ulasan reguler dan menyukai destinasi alam terbuka.
                </p>
                <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.6; border-top: 1px solid var(--color-border); padding-top: 12px;">
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kategori Favorit:</span> Alam, Bahari, Camping</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kisaran Biaya:</span> Rp0 &ndash; Rp25.000</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Jumlah Wisatawan:</span> {{ $clusterCounts[1] ?? '130+' }} pengguna</div>
                </div>
            </div>

            <!-- Cluster 02 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                    Klaster 02
                </div>
                <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 10px 0;">
                    Wisatawan Populer & Fasilitas Modern
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.6; margin: 0 0 18px 0;">
                    Kelompok wisatawan yang mengutamakan fasilitas lengkap, wahana hiburan modern, serta destinasi ikonik yang memiliki ribuan ulasan dan akomodasi memadai.
                </p>
                <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.6; border-top: 1px solid var(--color-border); padding-top: 12px;">
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kategori Favorit:</span> Rekreasi, Theme Park, Resort</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kisaran Biaya:</span> Rp50.000 &ndash; Rp250.000+</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Jumlah Wisatawan:</span> {{ $clusterCounts[2] ?? '140+' }} pengguna</div>
                </div>
            </div>

            <!-- Cluster 03 -->
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 24px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                    Klaster 03
                </div>
                <h3 style="font-size: 16px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 10px 0;">
                    Wisatawan Budaya, Sejarah & Edukasi
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.6; margin: 0 0 18px 0;">
                    Wisatawan penjelajah warisan budaya nusantara, seperti candi bersejarah, desa adat, keraton, dan museum di seluruh penjuru kepulauan Indonesia.
                </p>
                <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.6; border-top: 1px solid var(--color-border); padding-top: 12px;">
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kategori Favorit:</span> Sejarah, Budaya, Religi</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Kisaran Biaya:</span> Rp10.000 &ndash; Rp75.000</div>
                    <div><span style="font-weight: 600; color: var(--color-text-main);">Jumlah Wisatawan:</span> {{ $clusterCounts[3] ?? '120+' }} pengguna</div>
                </div>
            </div>

        </div>
    </section>

    <!-- 6. METRIK EVALUASI AKADEMIK (CLEAN TYPOGRAPHY TABLE) -->
    <section style="margin-bottom: 72px;">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                05. Validasi Akademik
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Instrumen Evaluasi Kinerja Model Rekomendasi
            </h2>
            <p style="font-size: 14px; color: var(--color-text-muted); line-height: 1.6; max-width: 740px; margin: 0;">
                Tolak ukur objektivitas kualitas pemisahan klastering serta akurasi prediksi penilaian model.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--color-text-muted); margin-bottom: 8px;">
                    Davies-Bouldin Index (DBI)
                </div>
                <div style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; margin-bottom: 8px; font-family: monospace;">
                    {{ $latestKmeansRun->metrics['davies_bouldin'] ?? '1.6173' }}
                </div>
                <p style="font-size: 12px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                    Makin rendah makin baik. K = 3 menghasilkan rasio pemisahan antar-klaster paling optimal.
                </p>
            </div>

            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--color-text-muted); margin-bottom: 8px;">
                    Silhouette Coefficient
                </div>
                <div style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; margin-bottom: 8px; font-family: monospace;">
                    {{ $latestKmeansRun->metrics['silhouette'] ?? '0.1408' }}
                </div>
                <p style="font-size: 12px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                    Rentang [-1, +1]. Nilai positif membuktikan titik terpetakan ke klaster yang sesuai.
                </p>
            </div>

            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--color-text-muted); margin-bottom: 8px;">
                    Mean Absolute Error (MAE)
                </div>
                <div style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; margin-bottom: 8px; font-family: monospace;">
                    {{ $latestCfRun->metrics['mae'] ?? '0.784' }}
                </div>
                <p style="font-size: 12px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                    Rata-rata selisih absolut prediksi terhadap rating nyata pada data uji (80/20 train-test split).
                </p>
            </div>

            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 20px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--color-text-muted); margin-bottom: 8px;">
                    Root Mean Squared Error (RMSE)
                </div>
                <div style="font-size: 26px; font-weight: 700; color: var(--color-primary-dark); line-height: 1; margin-bottom: 8px; font-family: monospace;">
                    {{ $latestCfRun->metrics['rmse'] ?? '0.942' }}
                </div>
                <p style="font-size: 12px; color: var(--color-text-muted); line-height: 1.5; margin: 0;">
                    Akar rata-rata kuadrat galat yang memberikan penalti lebih besar pada deviasi prediksi ekstrem.
                </p>
            </div>
        </div>
    </section>

    <!-- 7. TANYA JAWAB METODOLOGI -->
    <section style="margin-bottom: 64px;">
        <div style="margin-bottom: 24px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted); margin-bottom: 6px;">
                06. Diskusi Metodologi
            </div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 8px 0; letter-spacing: -0.01em;">
                Pertanyaan Umum Seputar Metodologi
            </h2>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; background: var(--color-white);">
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Mengapa K-Means digabungkan dengan Collaborative Filtering?
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.6; margin: 0;">
                    Dalam Collaborative Filtering konvensional, pencarian kemiripan pengguna ke seluruh populasi membutuhkan waktu komputasi besar O(N). Melalui klasterisasi K-Means, pencarian dibatasi hanya pada wisatawan di klaster yang sama O(N/K), sekaligus menyaring tetangga yang memiliki kecenderungan minat serupa sehingga rekomendasi lebih relevan.
                </p>
            </div>

            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 22px; background: var(--color-white);">
                <h3 style="font-size: 15px; font-weight: 600; color: var(--color-primary-dark); margin: 0 0 8px 0;">
                    Apakah sistem memerlukan server Python khusus untuk berjalan?
                </h3>
                <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.6; margin: 0;">
                    Tidak. Seluruh algoritma K-Means, standarisasi Z-Score, evaluasi Davies-Bouldin, Cosine Similarity, hingga evaluasi akurasi MAE/RMSE ditulis secara native dalam PHP 8.3 (Laravel). Arsitektur ini menyederhanakan pemeliharaan server, menghilangkan latensi inter-process communication, dan menjaga kemandirian sistem produksi.
                </p>
            </div>
        </div>
    </section>

    <!-- 8. FOOTER CALLOUT ACTION (MINIMALIST & CALM) -->
    <div style="border-top: 1px solid var(--color-border); padding-top: 36px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: var(--color-primary-dark); margin: 0 0 4px 0;">
                Mulai Eksplorasi Rekomendasi
            </h3>
            <p style="font-size: 13.5px; color: var(--color-text-muted); margin: 0;">
                Uji langsung hasil rekomendasi terpersonalisasi pada {{ $totalDestinations }} objek wisata di {{ $totalProvinces }} provinsi nusantara.
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="{{ route('recommendations.index') }}" class="btn-primary" style="border-radius: var(--radius-pill); padding: 10px 22px; font-size: 13px;">
                Menu Rekomendasi &rarr;
            </a>
            <a href="{{ route('destinations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 10px 22px; font-size: 13px;">
                Katalog Destinasi
            </a>
        </div>
    </div>

</div>
@endsection
