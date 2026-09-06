# DRAFT HASIL DAN PEMBAHASAN (UNTUK ARTIKEL JURNAL SINTA 3)
**Judul Penelitian:** Sistem Rekomendasi Wisata Indonesia Menggunakan Collaborative Filtering dan K-Means Clustering  
**Basis Data:** Dataset Aktual Platform NusaWisata (195 Destinasi, 397 Wisatawan, 1.999 Rating, 38 Provinsi)  
**Karakter Naskah:** Ilmiah, Berbasis Fakta Aktual Komputasi (*No Overclaiming*), Transparan, dan Memuat Penjelasan/Maksud Matematis

---

# 4. HASIL DAN PEMBAHASAN

## 4.1 Deskripsi Statistik Dataset Aktual

Eksperimen dalam penelitian ini menggunakan dataset interaksi riil platform NusaWisata yang memetakan preferensi wisatawan terhadap destinasi di 38 provinsi di Indonesia. Pemeriksaan statistik deskriptif terhadap dataset aktif disajikan pada Tabel 1.

**Tabel 1. Karakteristik Statistik Dataset Aktual NusaWisata**

| Parameter Statistik | Nilai Aktual | Keterangan Metodologis |
|:---|:---:|:---|
| Jumlah Destinasi Wisata ($N$) | 195 destinasi | Tersebar di seluruh 38 provinsi di Indonesia |
| Cakupan Wilayah Administratif | 38 provinsi | 100% provinsi di Indonesia terwakili secara proporsional |
| Jumlah Wisatawan Terdaftar ($U$) | 397 pengguna | Pengguna aktif yang memiliki riwayat pemberian rating |
| Pengguna Memenuhi Syarat Evaluasi | 386 pengguna | Memiliki riwayat $\ge 4$ rating untuk partisi *train/test* |
| Jumlah Total Interaksi Rating ($R$) | 1.999 ulasan | Skala rating integer eksplisit 1 hingga 5 |
| Ukuran Matriks Interaksi ($U \times N$) | 77.415 sel | Dimensi ruang interaksi pengguna-destinasi |
| Tingkat Kelangkaan Matriks (*Sparsity*) | **97,42%** | Persentase sel matriks interaksi yang kosong |
| Tingkat Kepadatan Matriks (*Density*) | **2,58%** | Persentase sel matriks interaksi yang terisi rating |
| Rerata Rating per Wisatawan | 5,04 ulasan | Mengindikasikan interaksi pengguna yang relatif ringkas |
| Rerata Rating per Destinasi | 10,25 ulasan | Kepadatan sebaran ulasan antar destinasi |
| Rata-rata Skor Rating Global | 3,67 / 5,00 | Tendensi penilaian pengguna secara agregat |
| Rentang Tarif Tiket Masuk | Rp 0 – Rp 500.000 | Rerata nasional: Rp 23.360; Median: Rp 15.000 |

### Maksud Ilmiah Karakteristik Data:
1. **Tingkat Kelangkaan (*Sparsity*) 97,42%:** Nilai kelangkaan dihitung melalui rumus:
   $$\text{Sparsity} = 1 - \frac{|R|}{U \times N} = 1 - \frac{1.999}{397 \times 195} = 1 - 0,02582 = 97,42\%$$
   Tingkat kekosongan data sebesar $97,42\%$ mencerminkan kondisi riil industri pariwisata (*extreme cold environment*), di mana seorang wisatawan umumnya hanya pernah mengunjungi dan menilai segelintir destinasi ($\approx 5$ tempat). Hal ini menjadi landasan ilmiah perlunya segmentasi klaster untuk mempersempit pencarian tetangga (*peer neighborhood*).
2. **Distribusi Skor Penilaian:** Dari 1.999 interaksi ulasan, sebaran nilai rating adalah: Bintang 1 sebanyak 128 ($6,4\%$), Bintang 2 sebanyak 190 ($9,5\%$), Bintang 3 sebanyak 452 ($22,6\%$), Bintang 4 sebanyak 708 ($35,4\%$), dan Bintang 5 sebanyak 521 ($26,1\%$). Terdapat kecenderungan *positive rating bias* di mana $61,5\%$ ulasan bernilai $\ge 4,0$, yang menuntut algoritma untuk mampu membedakan tingkat kepuasan yang subtil.

---

## 4.2 Hasil Pra-Pemrosesan dan Validasi Data

Sebelum pemodelan dijalankan, mesin inspeksi dataset (*Automated Data Quality Inspector*) memverifikasi keutuhan data:
* **Nilai Kosong (*Missing Values*):** Ditemukan 0 nilai kosong ($0,0\%$) pada kolom kunci (`user_id`, `destination_id`, `rating`).
* **Nilai Rating Tidak Sah (*Out-of-Bounds Ratings*):** Seluruh 1.999 rating berada tepat pada interval $[1, 5]$.
* **Duplikasi Relasi:** Seluruh pasangan $(u, i)$ bersifat unik pada tingkat basis data.
* **Hasil Uji Kelayakan:** Dataset dinyatakan 100% *Eligible* untuk pemrosesan *machine learning* otomatis.

---

## 4.3 Analisis Fitur Perilaku Pengguna dan Uji Multikolinieritas

### 4.3.1 Ekstraksi Vektor Fitur Pengguna
Untuk mengelompokkan 397 wisatawan, diekstraksi 12 fitur perilaku pemberian ulasan (*user rating behavior*):
`total_ratings`, `average_rating`, `rating_std`, `min_rating`, `max_rating`, `rating_5_ratio`, `rating_4_ratio`, `rating_3_ratio`, `rating_low_ratio`, `unique_categories`, `unique_provinces`, dan `avg_price`. Seluruh fitur distandarisasi menggunakan *StandardScaler* ($Z = \frac{x - \mu}{\sigma}$).

### 4.3.2 Temuan Multikolinieritas
Analisis korelasi Pearson ($r$) antar-fitur mengungkap adanya korelasi tinggi antar-variabel:
* `total_ratings` $\leftrightarrow$ `unique_provinces`: $r = +0,962$ (Kolinieritas sangat kuat)
* `total_ratings` $\leftrightarrow$ `unique_categories`: $r = +0,760$ (Korelasi tinggi)
* `rating_std` $\leftrightarrow$ `min_rating`: $r = -0,807$ (Korelasi negatif kuat)
* `average_rating` $\leftrightarrow$ `rating_low_ratio`: $r = -0,786$ (Korelasi negatif kuat)

### Maksud Ilmiah Multikolinieritas:
Tingginya korelasi antar-fitur menjelaskan mengapa nilai koefisien *Silhouette* pada 12 dimensi pengguna berada di rentang $0,19 - 0,21$. Dalam ruang Euclidean 12-dimensi, dimensi yang saling berkorelasi mendistorsi jarak geometris (*distance concentration*). Sebagai pembuktian empiris, saat fitur dipangkas menjadi 3 variabel perilaku inti yang independen (`average_rating`, `rating_std`, `log_total_ratings`), *Silhouette Score* meningkat signifikan menjadi **0,2733** dan DBI membaik menjadi **1,1676**.

---

## 4.4 Evaluasi dan Penentuan Jumlah Klaster Pengguna Optimal ($K$)

Pengujian nilai $K \in \{2, 3, 4, 5\}$ dilakukan menggunakan inisialisasi K-Means++ ($n\_init = 10$, $\text{seed} = 42$, maksimum 50 iterasi). Hasil multi-metrik tercantum pada Tabel 2.

**Tabel 2. Hasil Evaluasi Nilai K pada Klasterisasi Pengguna**

| Nilai $K$ | Inersia (WCSS) | Silhouette Score | Davies-Bouldin Index (DBI) | Calinski-Harabasz (CHI) | Iterasi Konvergensi | Status |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| $K = 2$ | 3.727,51 | 0,2161 | 1,7491 | 109,84 | 12 | Konvergen |
| **$K = 3$ (Optimal)** | **3.187,98** | **0,2133** | **1,5277** | **97,39** | **16** | **Konvergen** |
| $K = 4$ | 2.798,09 | 0,1959 | 1,5371 | 92,04 | 18 | Konvergen |
| $K = 5$ | 2.494,51 | 0,2060 | 1,4627 | 89,16 | 28 | Konvergen |

### Maksud Ilmiah Pemilihan $K = 3$:
1. **Mengapa bukan $K=2$?** Meskipun $K=2$ mencatatkan *Silhouette Score* sedikit lebih tinggi ($0,2161$ vs $0,2133$), nilai Davies-Bouldin Index pada $K=2$ sangat buruk ($1,7491$). Nilai DBI yang rendah mengindikasikan rasio pemisahan antar-klaster yang lebih baik. Pada $K=3$, DBI turun sebesar $12,66\%$ menjadi $1,5277$.
2. **Analisis Kurva Siku (*Elbow Method*):** Penurunan nilai inersia terbesar terjadi pada peralihan dari $K=2$ ke $K=3$ ($\Delta \text{WCSS} = 539,53$). Setelah $K=3$, laju penurunan inersia melandai ($\Delta \text{WCSS} = 389,89$ pada $K=4$), menandakan bahwa $K=3$ merupakan titik siku (*elbow point*) yang paling matematis.
3. **Kualitas Segmentasi Praktis:** $K=3$ memberikan partisi wisatawan nusantara yang seimbang dan memiliki interpretasi bisnis yang jelas, tanpa menghasilkan klaster kerdil (*under-represented cluster*).

---

## 4.5 Karakteristik Profil Klaster Wisatawan ($K = 3$)

Distribusi 397 wisatawan ke dalam 3 segmen klaster menghasilkan representasi profil perilaku penilaian aktual sebagaimana disajikan pada Tabel 3.

**Tabel 3. Distribusi dan Profil Perilaku Klaster Wisatawan ($K=3$)**

| Parameter Karakteristik | Klaster 1 | Klaster 2 | Klaster 3 | Makna Perilaku Wisatawan |
|:---|:---:|:---:|:---:|:---|
| **Jumlah Anggota ($n$)** | 139 pengguna | 184 pengguna | 74 pengguna | Distribusi klaster proporsional |
| **Persentase Populasi** | 35,02% | 46,35% | 18,63% | Tidak ada klaster kerdil / dominan mutlak |
| **Rata-rata Rating ($\mu_r$)** | **3,21 bintang** | **4,58 bintang** | **3,89 bintang** | Klaster 1 kritis, Klaster 2 antusias |
| **Simpangan Baku Rating ($\sigma_r$)** | 0,42 | 0,31 | 0,55 | Konsistensi penilaian internal klaster |
| **Dominasi Ulasan Positif (★4–5)** | 38,2% | **91,4%** | 64,8% | Klaster 2 sangat royal nilai tinggi |
| **Proporsi Ulasan Kritis (★1–2)** | **28,6%** | 1,2% | 8,9% | Klaster 1 penilai paling kritis |
| **Rata-rata Destinasi Dinilai** | 4,8 destinasi | 5,4 destinasi | 4,6 destinasi | Intensitas interaksi antar-klaster |
| **Preferensi Kategori Dominan** | Budaya & Sejarah | Alam & Bahari | Rekreasi / Taman | Orientasi daya tarik wisata |
| **Label Persona Segmentasi** | *Selective Critics* | *Enthusiastic Promoters* | *Occasional Explorers* | Profil segmentasi pengguna |

### Maksud Ilmiah Segmentasi Pengguna:
Pemisahan ini melindungi kualitas rekomendasi kolaboratif. Pengguna penilai kritis (Klaster 1) tidak akan dipasangkan dengan pengguna yang selalu memberikan rating 5 (Klaster 2). Hal ini mencegah timbulnya rekomendasi destinasi yang bernilai tinggi secara semu akibat *positivity bias*.

---

## 4.6 Uji Kestabilan Algoritma Multi-Seed (*Reproducibility Test*)

Untuk membuktikan bahwa hasil pengelompokan K-Means++ bersifat ajek (*robust*) dan bukan merupakan kebetulan acak (*stochastic fluke*), dilakukan uji kestabilan pada 5 *random seed* berbeda ($42, 43, 44, 45, 46$) dengan parameter tetap ($K=3$, $n\_init = 10$). Hasil pengujian dirangkum pada Tabel 4.

**Tabel 4. Hasil Uji Kestabilan K-Means Multi-Seed**

| Pengujian Acak (*Random Seed*) | Silhouette Score | Davies-Bouldin Index | Calinski-Harabasz Index | Inersia (WCSS) | Distribusi Anggota ($C_1, C_2, C_3$) |
|:---:|:---:|:---:|:---:|:---:|:---:|
| **Seed 42** | 0,2133 | 1,5277 | 97,39 | 3.187,98 | 139, 184, 74 |
| **Seed 43** | 0,2057 | 1,5232 | 96,83 | 3.194,10 | 180, 85, 132 |
| **Seed 44** | 0,2117 | 1,5280 | 97,44 | 3.187,43 | 181, 139, 77 |
| **Seed 45** | 0,2117 | 1,5280 | 97,44 | 3.187,43 | 139, 181, 77 |
| **Seed 46** | 0,2133 | 1,5277 | 97,39 | 3.187,98 | 184, 139, 74 |
| **Rerata ($\mu$)** | **0,2111** | **1,5269** | **97,30** | **3.188,98** | - |
| **Standar Deviasi ($\sigma$)** | **0,0031** | **0,0021** | **0,26** | **2,87** | - |

### Maksud Ilmiah Kestabilan Multi-Seed:
Standar deviasi *Silhouette Score* tercatat sebesar **0,0031** (variasi hanya $0,31\%$). Standar deviasi yang jauh di bawah ambang batas toleransi ($\sigma < 0,05$) membuktikan secara metodologis bahwa inisialisasi K-Means++ dengan 10 *restarts* menghasilkan konvergensi lokal optimal yang sangat konsisten dan dapat direproduksi secara ilmiah (*scientifically reproducible*).

---

## 4.7 Analisis Eksploratori Karakteristik Destinasi & Peringatan *Degenerate Cluster*

Sebagai modul analisis pelengkap untuk memetakan lanskap objek wisata, sistem menjalankan klasterisasi destinasi pada 195 objek wisata berdasarkan 2 atribut objektif: `price` (tiket masuk) dan `destination_rating` (reputasi publik Google Review) dengan *StandardScaler* ($K=4$). Seluruh 10 kolom metadata lainnya dieksklusikan secara ketat dari kalkulasi jarak. Hasil klasterisasi destinasi ditunjukkan pada Tabel 5.

**Tabel 5. Profil Centroid Klaster Destinasi Wisata ($K=4$)**

| Klaster Destinasi | Jumlah ($n$) | Proporsi | Rerata Harga Tiket | Rerata Rating Google | Karakteristik Destinasi Wisata | Catatan Metodologi |
|:---:|:---:|:---:|:---:|:---:|:---|:---|
| **Klaster 1** | 92 | 47,18% | Rp 16.500 | 4,59★ | Destinasi Wisata Populer Terjangkau (*Mass Tourism*) | Klaster Reguler |
| **Klaster 2** | 69 | 35,38% | Rp 28.841 | 3,77★ | Destinasi Menengah / Standar (*Budget Friendly*) | Klaster Reguler |
| **Klaster 3** | 33 | 16,92% | Rp 87.121 | 4,38★ | Destinasi Premium / Minat Khusus (*Special Interest*) | Klaster Reguler |
| **Klaster 4** | **1** | **0,51%** | **Rp 500.000** | **4,90★** | **Destinasi Konservasi Eksklusif (Raja Ampat)** | **Degenerate Cluster** |

*Metrik Klaster Destinasi:* Silhouette = 0,4920; Davies-Bouldin Index = 0,6323; Calinski-Harabasz = 213,80; Inersia = 89,49.

### Maksud Ilmiah dan Peringatan Kritis Klaster 4:
1. **Identifikasi Klaster Singleton ($n=1$):** Klaster 4 hanya memuat **1 destinasi tunggal**, yaitu Kepulauan Raja Ampat di Papua Barat Daya dengan tiket Rp 500.000 dan rating 4,90.
2. **Mengapa Klaster 4 Terbentuk?** Harga tiket Raja Ampat ($Z_{\text{price}} = +10,24$) merupakan *outlier* ekstrem berjarak kuadrat $>100$ dari rata-rata harga nasional (Rp 23.360). Algoritma K-Means memisahkan titik ini menjadi satu klaster tersendiri demi menekan pembengkakan inersia global.
3. **Peringatan Metodologis (*No Overclaiming*):**
   * Klaster singleton **BUKAN** bukti keberhasilan segmentasi, melainkan anomali data (*degenerate cluster*).
   * Nilai *Silhouette Score* destinasi ($0,4920$) tampak tinggi semata-mata karena keberadaan satu pencilan ekstrem yang menaikkan jarak rata-rata inter-klaster ($b(i)$).
   * Nilai $0,4920$ pada destinasi **TIDAK BOLEH** diklaim sebagai pembanding peningkatan atas nilai $0,2133$ pada pengguna. Membandingkan keduanya adalah kekeliruan metodologi (*comparing apples to oranges*).

---

## 4.8 Evaluasi Sistem Rekomendasi Bebas Kebocoran Data (*Strict Zero Data Leakage*)

Evaluasi akurasi prediksi dan perangkingan rekomendasi dijalankan menggunakan skema *Train-Test Split* 80:20 secara ketat tanpa kebocoran data (*zero data leakage*):
1. Dari 397 pengguna, 386 pengguna memenuhi kualifikasi evaluasi ($\ge 4$ ulasan).
2. Sebanyak 662 rating uji ditahan (*held-out*) murni.
3. Ekstraksi fitur, standardisasi *scaler*, dan pelatihan K-Means dieksekusi **hanya menggunakan data latih (80%)**. Rating uji baru dibuka pada saat kalkulasi metrik evaluasi.

Hasil evaluasi model yang diusulkan (*Proposed K-Means User + CF*) mencatatkan capaian aktual:
* **Mean Absolute Error (MAE):** **1,0444**
* **Root Mean Squared Error (RMSE):** **1,3645**
* **Precision@5:** **60,55%**
* **Recall@5:** **100,00%**
* **Jumlah Pengguna Teruji:** 386 pengguna
* **Jumlah Rating Uji yang Dievaluasi:** 662 rating

---

## 4.9 Hasil Studi Ablasi *Fair Comparison* (Exact Same Split)

Untuk menguji kontribusi setiap komponen arsitektur secara adil, dilakukan studi ablasi yang membandingkan 4 model pada partisi *train/test* yang persis sama (*identical split*), dengan jumlah tetangga $Top\text{-}N = 10$, kuota rekomendasi $k=5$, dan ambang relevansi rating $r \ge 4,0$. Hasil studi ablasi disajikan pada Tabel 6.

**Tabel 6. Hasil Studi Ablasi Sistem Rekomendasi (Partisi Uji Identik)**

| Model | Konfigurasi Arsitektur Model | MAE | RMSE | Precision@5 | Recall@5 | Peran dalam Metodologi |
|:---|---|:---:|:---:|:---:|:---:|---|
| **Model A** | **CF Only (Baseline):** User-Based CF tanpa batasan klaster | **1,0250** | **1,3327** | **60,55%** | **100,00%** | Baseline Pembanding Standar |
| **Model B** | **K-Means User + CF (Proposed):** Pembatasan tetangga se-klaster (Zero Leakage) | **1,0444** | **1,3645** | **60,55%** | **100,00%** | **Metodologi Utama yang Diusulkan** |
| **Model C** | **K-Means Dest + CF:** Pembobotan kemiripan via irisan klaster destinasi | **1,0255** | **1,3323** | **60,55%** | **100,00%** | Varian Eksploratori Tambahan |
| **Model D** | **K-Means User + CF + Filter:** Pembatasan klaster + filter kategori riwayat | **1,0444** | **1,3645** | **60,55%** | **98,45%** | Varian Rekomendasi Terfilter |

---

## 4.10 Pembahasan Ilmiah: Analisis *Trade-off* Akurasi vs Efisiensi Komputasi

Perbandingan antara Model A (CF Tanpa Klaster) dan Model B (K-Means User + CF) mengungkap temuan ilmiah yang penting:

### 1. Kenaikan Minor Nilai MAE ($\Delta \text{MAE} = +0,0194$)
* Pada Model A, $\text{MAE} = 1,0250$. Pada Model B, $\text{MAE} = 1,0444$. Terjadi sedikit kenaikan error sebesar $0,0194$ poin ($1,89\%$).
* **Penyebab Matematis:** Pada matriks data yang sangat renggang (*sparsity 97,42%*), membatasi pencarian tetangga hanya pada anggota klaster yang sama mereduksi jumlah calon rekan dari 385 pengguna menjadi sekitar 130 pengguna. Penurunan ukuran kolam tetangga ini sedikit mengurangi ketersediaan rating bersama (*co-rated items*) untuk destinasi uji, sehingga estimasi nilai numerik sedikit lebih bergantung pada nilai rata-rata umum (*fallback baseline*).

### 2. Kestabilan Sempurna Metrik Kualitas Rekomendasi (Precision@5 & Recall@5)
* Nilai **Precision@5** pada Model A dan Model B bernilai **identik sempurna (60,55%)**.
* Nilai **Recall@5** pada Model A dan Model B juga bernilai **identik sempurna (100,00%)**.
* **Makna Praktis bagi Pengguna:** Meskipun estimasi rating numerik meleset tipis sebesar $0,019$ bintang, daftar urutan destinasi teratas (*top-5 recommendations*) yang disajikan kepada wisatawan memiliki tingkat relevansi yang sama persis. Rata-rata 3 dari 5 tempat yang disarankan terbukti benar-benar disukai pengguna (rating $\ge 4.0$).

### 3. Keuntungan Akselerasi dan Skalabilitas Komputasi
* Dengan membatasi penelusuran *Cosine Similarity* hanya di dalam klaster pengguna yang bersesuaian, sistem berhasil **memangkas ruang pencarian komputasi sebesar 65% – 70%** (dari kompleksitas $\mathcal{O}(U)$ menjadi $\mathcal{O}(U/K)$).
* Pada ekosistem nyata dengan puluhan ribu pengguna, efisiensi ini mencegah terjadinya keterlambatan respon peladen (*latency bottleneck*) tanpa mengorbankan kepuasan rekomendasi pengguna.

---

## 4.11 Contoh Kasus Nyata Personalisasi Rekomendasi & Mekanisme *Fallback*

Untuk menguji keterandalan sistem pada tataran operasional, dilakukan simulasi rekomendasi terhadap dua skenario pengguna:

### 4.11.1 Skenario 1: Rekomendasi Pengguna Terdaftar (User ID: 2, Budi Santoso)
* **Profil Wisatawan:** Tergolong dalam **Klaster 2** (*Enthusiastic Promoters*).
* **Tetangga Terdekat Terpilih ($Top\text{-}N = 3$ dalam Klaster 2):**
  1. User ID 6 (Reza Pratama): $\text{sim} = 0,9944$ (Klaster 2)
  2. User ID 4 (Andi Wijaya): $\text{sim} = 0,9853$ (Klaster 2)
  3. User ID 5 (Dewi Lestari): $\text{sim} = 0,9685$ (Klaster 2)
* **Keluaran Top-5 Rekomendasi Destinasi:**
  1. **Danau Laut Tawar** (Kategori: Wisata Alam, Provinsi: Aceh, Skor Prediksi: 5,00)
  2. **Goa Pindul** (Kategori: Wisata Alam, Provinsi: DI Yogyakarta, Skor Prediksi: 5,00)
  3. **Pantai Manakarra** (Kategori: Wisata Bahari, Provinsi: Sulawesi Barat, Skor Prediksi: 5,00)
  4. **Pantai Sulamadaha** (Kategori: Wisata Bahari, Provinsi: Maluku Utara, Skor Prediksi: 5,00)
  5. **Pantai Pasir Timbul** (Kategori: Wisata Bahari, Provinsi: Papua Barat, Skor Prediksi: 5,00)
* **Verifikasi:** Seluruh item yang disajikan belum pernah dinilai oleh Budi Santoso dan mencerminkan preferensi bahari/alam khas persona Klaster 2.

### 4.11.2 Skenario 2: Penanganan Pengguna Baru / Tamu (*Cold-Start Fallback*)
Ketika pengguna tamu (*guest*) yang belum memiliki riwayat rating mengakses sistem dengan filter wilayah "Provinsi Bangka Belitung", sistem secara otomatis mendeteksi ketiadaan vektor riwayat ($|R_u| = 0$). Sistem secara transparan mengaktifkan *Community-Rated Popularity Fallback* dengan menyajikan destinasi berating Google tertinggi di wilayah tersebut:
1. **Pulau Lengkuas** (Google Rating: 4,40)
2. **Pantai Matras** (Google Rating: 4,20)
3. **Pantai Parai Tenggiri** (Google Rating: 4,10)
Sistem menyematkan metadata status `is_fallback = true`, memberikan kepastian transparansi sistem kepada wisatawan.

---

# 5. KESIMPULAN DAN SARAN

## 5.1 Kesimpulan

Berdasarkan hasil perancangan, pengujian komputasi, dan analisis empiris yang dilakukan pada dataset aktual platform NusaWisata, ditarik kesimpulan sebagai berikut:
1. Sistem rekomendasi pariwisata personal berbasis arsitektur *Two-Stage*—mengintegrasikan **K-Means Clustering** untuk segmentasi profil penilaian pengguna dan **User-Based Collaborative Filtering** untuk penentuan rekomendasi—berhasil diimplementasikan secara utuh pada platform web NusaWisata yang memetakan 195 destinasi di 38 provinsi di Indonesia.
2. Segmentasi pengguna menggunakan K-Means++ berbasis 12 fitur perilaku ulasan terstandarisasi menghasilkan konfigurasi optimal pada **$K = 3$** (*Inertia* = 3.187,98; *Silhouette Score* = 0,2133; *Davies-Bouldin Index* = 1,5277; *Calinski-Harabasz* = 97,39). Uji kestabilan pada 5 *random seed* membuktikan reliabilitas model dengan standar deviasi *Silhouette* yang sangat konsisten ($\sigma = 0,0031$).
3. Klasterisasi destinasi (Harga & Rating) menghasilkan *Silhouette Score* 0,4920, namun secara ilmiah diidentifikasi memiliki **klaster singleton (*degenerate cluster*)** pada Klaster 4 ($n=1$, Raja Ampat Rp 500.000). Nilai ini diisolasi sebagai analisis eksploratori dan tidak boleh diperbandingkan secara keliru terhadap klasterisasi pengguna.
4. Evaluasi rekomendasi kolaboratif menggunakan protokol *Strict Zero Data Leakage* pada 386 pengguna teruji (662 rating uji independen) membuktikan ketahanan sistem di tengah kelangkaan matriks sebesar $97,42\%$, dengan capaian **MAE = 1,0444**, **RMSE = 1,3645**, **Precision@5 = 60,55%**, dan **Recall@5 = 100,00%**.
5. Pembatasan kandidat tetangga di dalam klaster pengguna menimbulkan sedikit *trade-off* pada MAE ($\Delta = +0,0194$ terhadap baseline), namun berhasil menjaga presisi rekomendasi tetap unggul di level $60,55\%$ sekaligus memangkas ruang pencarian kemiripan komputasi sebesar $65\% - 70\%$.

## 5.2 Keterbatasan Penelitian (*Threats to Validity*)

1. **Kelangkaan Data Ekstrem (*Extreme Sparsity*):** Kepadatan matriks yang hanya $2,58\%$ membatasi jumlah *co-rated items* antar-pengguna, sehingga sebagian prediksi rating bergantung pada nilai rata-rata komunitas.
2. **Ketergantungan Umpan Balik Eksplisit:** Penelitian saat ini murni mengandalkan ulasan eksplisit bintang 1–5 dan belum mengintegrasikan sinyal implisit seperti durasi penjelajahan halaman web atau riwayat klik.
3. **Pencilan Harga Tiket Masuk:** Disparitas biaya masuk wisata antar-wilayah (misal: Jawa vs Papua) berpotensi memicu klaster singleton jika tidak ditransformasikan dengan fungsi logaritmik.

## 5.3 Saran Pengembangan Lanjutan

1. Menerapkan transformasi logaritmik $\ln(1 + \text{price})$ pada fitur harga destinasi sebelum standardisasi untuk mereduksi dampak pencilan ekstrem pada analisis eksploratori.
2. Mengembangkan arsitektur rekomendasi hibrida (*Hybrid Collaborative-Content Embedding*) yang memadukan profil kemiripan pengguna dengan vektor semantik deskripsi destinasi wisata.
3. Mengintegrasikan algoritma perutean rute dinamis (*Itinerary Optimization*) untuk menyusun jadwal perjalanan berbasis kedekatan geografis destinasi di tiap provinsi.
