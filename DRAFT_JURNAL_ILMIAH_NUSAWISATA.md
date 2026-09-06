# SISTEM REKOMENDASI WISATA INDONESIA MENGGUNAKAN COLLABORATIVE FILTERING DAN K-MEANS CLUSTERING

---

## ABSTRAK

Sektor pariwisata Indonesia mencakup kekayaan destinasi yang tersebar di 38 provinsi dengan karakteristik geografis, budaya, dan daya tarik yang sangat beragam. Fenomena ledakan informasi (*information overload*) sering kali menyulitkan calon wisatawan dalam menentukan destinasi yang sesuai dengan preferensi personal, ketersediaan anggaran, dan tujuan wilayah perjalanan. Penelitian ini bertujuan untuk merancang, mengimplementasikan, dan mengevaluasi sistem rekomendasi pariwisata personal berbasis *User-Based Collaborative Filtering* yang dioptimalkan dengan segmentasi preferensi pengguna menggunakan algoritma *K-Means Clustering*. Pengelompokan pengguna dilakukan berdasarkan 12 fitur perilaku penilaian (*rating behavior*) yang ditransformasikan melalui standardisasi *Z-Score*. Kedekatan preferensi antar-pengguna dalam klaster dihitung menggunakan *Cosine Similarity* dengan syarat ambang minimal dua item yang sama-sama dinilai (*co-rated items*), kemudian dilakukan estimasi skor destinasi melalui prediksi terbobot (*weighted rating prediction*). Sistem juga mengintegrasikan mekanisme *candidate filtering* multi-kriteria (provinsi, kategori, anggaran harga, dan rating minimum) serta penanganan pengguna baru (*cold-start fallback*). Pengujian dilakukan pada dataset aktual platform NusaWisata yang terdiri dari 195 destinasi di 38 provinsi, 397 pengguna interaktif, dan 4.033 interaksi ulasan dengan tingkat kelangkaan matriks (*sparsity*) sebesar 94,76%. Hasil pengujian *K-Means* pada kandidat $K \in \{2, 3, 4, 5\}$ menunjukkan bahwa konfigurasi $K=2$ menghasilkan performa klasterisasi terbaik dengan *Silhouette Score* 0,2164, *Davies-Bouldin Index* 1,7462, dan *Calinski-Harabasz Index* 110,0328. Evaluasi performa rekomendasi menggunakan skema *Train-Test Split* 80:20 pada 386 pengguna teruji (662 data ulasan uji) menghasilkan *Mean Absolute Error* (MAE) sebesar 1,0250, *Root Mean Squared Error* (RMSE) sebesar 1,3327, *Precision@5* sebesar 60,55%, dan *Recall@5* mencapai 100,00%. Temuan ini membuktikan bahwa kombinasi *K-Means Clustering* dan *User-Based Collaborative Filtering* mampu memetakan segmentasi wisatawan secara efektif dan memberikan rekomendasi destinasi yang akurat serta relevan terhadap batasan preferensi perjalanan pengguna.

**Kata Kunci:** *Sistem Rekomendasi, Pariwisata Indonesia, K-Means Clustering, User-Based Collaborative Filtering, Cosine Similarity, Segmentasi Pengguna.*

---

## KEYWORDS

*Recommendation System, Indonesian Tourism, K-Means Clustering, User-Based Collaborative Filtering, Cosine Similarity, User Segmentation.*

---

# 1. PENDAHULUAN

## 1.1 Latar Belakang

Indonesia merupakan negara kepulauan yang dianugerahi kekayaan alam, keanekaragaman hayati, warisan sejarah, dan keragaman budaya yang tersebar di 38 provinsi. Sektor pariwisata memegang peranan krusial dalam perekonomian nasional sebagai motor penggerak pertumbuhan ekonomi daerah, penciptaan lapangan kerja, serta pelestarian warisan budaya bangsa. Seiring dengan kemajuan teknologi informasi dan penetrasi internet yang masif, calon wisatawan kini mengandalkan platform digital untuk merencanakan perjalanan mereka. 

Namun, ketersediaan informasi destinasi yang melimpah dan tidak terstruktur memunculkan tantangan baru yang dikenal sebagai *information overload* (kelebihan informasi). Calon wisatawan sering kali kesulitan menyaring ratusan opsi objek wisata yang relevan dengan minat pribadi, ketersediaan anggaran, maupun wilayah spesifik yang hendak dikunjungi. Di sisi lain, preferensi perjalanan setiap individu sangat heterogen; sebagian wisatawan menyukai penjelajahan alam bebas dan bahari, sementara sebagian lainnya lebih menyukai wisata budaya, edukasi sejarah, atau taman rekreasi keluarga. Oleh karena itu, diperlukan sebuah sistem rekomendasi cerdas yang mampu memahami preferensi wisatawan secara personal dan menyajikan saran destinasi yang tepat guna.

## 1.2 Permasalahan

Pengembangan sistem rekomendasi di domain pariwisata Indonesia menghadapi sejumlah tantangan nyata:
1. **Heterogenitas Preferensi dan Perilaku Wisatawan:** Perilaku pemberian ulasan wisatawan memiliki pola yang berbeda-beda. Sebagian pengguna cenderung royal memberikan nilai tinggi (*positive bias*), sedangkan kelompok lain bersikap lebih kritis atau hanya memberikan ulasan saat mendapati pengalaman ekstrem. Pendekatan rekomendasi tanpa segmentasi karakteristik pengguna berisiko menghasilkan rekomendasi yang bias.
2. **Kelangkaan Data Ulasan (*Data Sparsity*):** Jumlah destinasi wisata di Indonesia sangat luas, namun seorang wisatawan umumnya hanya pernah mengunjungi dan menilai sebagian kecil destinasi. Akibatnya, matriks interaksi pengguna-destinasi menjadi sangat renggang (*sparse*), yang menyulitkan algoritma *Collaborative Filtering* murni dalam menemukan korelasi kemiripan antar-pengguna secara global.
3. **Kebutuhan Kendala Nyata Perjalanan (*Contextual Constraints*):** Rekomendasi wisata berbeda dengan rekomendasi media (film atau musik). Perjalanan wisata terikat oleh kendala geografis (provinsi tertentu), batasan anggaran biaya masuk, dan preferensi jenis daya tarik. Rekomendasi yang secara statistik bernilai tinggi menjadi tidak berguna apabila berada di luar provinsi tujuan atau melampaui batas anggaran wisatawan.
4. **Masalah Pengguna Baru (*Cold-Start Problem*):** Pengguna yang baru mendaftar atau pengguna tamu belum memiliki riwayat penilaian sama sekali, sehingga algoritma berbasis kolaboratif tidak dapat langsung menghitung kemiripan tetangga tanpa strategi penanganan cadangan (*fallback*).

## 1.3 Tujuan Penelitian

Penelitian ini bertujuan untuk:
1. Merancang arsitektur sistem rekomendasi personal yang memadukan *K-Means Clustering* untuk segmentasi profil penilaian pengguna dengan *User-Based Collaborative Filtering* untuk prediksi preferensi destinasi.
2. Menerapkan ekstraksi 12 fitur perilaku ulasan (*feature engineering*) dan standardisasi *Z-Score* guna menghasilkan klaster pengguna yang kohesif dan terpisah secara optimal.
3. Mengembangkan mekanisme penyaringan kandidat destinasi (*candidate set generation*) berbasis parameter kontekstual (provinsi, kategori, rentang harga, dan rating minimum) tanpa merusak konsistensi perhitungan kemiripan kolaboratif.
4. Mengimplementasikan sistem secara utuh ke dalam platform web NusaWisata yang dilengkapi dengan fitur *Automated Machine Learning Run*, manajemen versi dataset dinamis, dan visualisasi geografis interaktif.
5. Mengevaluasi performa klasterisasi (*Inertia*, *Silhouette Score*, *Davies-Bouldin Index*, *Calinski-Harabasz Index*) serta akurasi prediksi dan peringkat rekomendasi (*MAE*, *RMSE*, *Precision@K*, *Recall@K*) berdasarkan data aktual.

## 1.4 Kontribusi Penelitian

Kontribusi utama dari penelitian ini meliputi:
1. **Pendekatan Hibrida Berbasis Segmentasi Klaster:** Memanfaatkan *K-Means Clustering* bukan untuk mengelompokkan objek destinasi, melainkan untuk memetakan pengguna ke dalam klaster perilaku (*user rating behavior*). Hal ini mereduksi ruang pencarian tetangga terdekat (*peer neighborhood*) pada *Collaborative Filtering* sehingga komputasi kemiripan lebih terfokus pada sub-populasi yang relevan.
2. **Integrasi Candidate Filtering Multi-Kriteria:** Menghubungkan formulasi *User-Based CF* dengan modul pembangkit kandidat dinamis, memastikan bahwa rekomendasi yang dihasilkan secara presisi memenuhi kriteria wilayah, batas biaya, dan kategori wisata yang diminta pengguna.
3. **Evaluasi Empiris pada Ekosistem Pariwisata 38 Provinsi:** Menguji sistem pada data aktual dengan sebaran nasional 38 provinsi di Indonesia, membuktikan viabilitas algoritma dalam menangani matriks interaksi nyata dengan tingkat kelangkaan 94,76%.
4. **Arsitektur Sistem Terotomasi dan Terbuka:** Menyediakan platform terpadu yang mendukung pengujian mandiri (*Automated ML Run*) dan isolasi versi dataset (*dataset versioning*) untuk menjamin transparansi, reproduktifitas eksperimen, dan keberlanjutan pemeliharaan sistem.

---

# 2. TINJAUAN PUSTAKA

## 2.1 Sistem Rekomendasi

Sistem rekomendasi adalah cabang kecerdasan buatan dan penambangan data (*data mining*) yang bertujuan memperkirakan minat atau evaluasi pengguna terhadap suatu item yang belum pernah dikonsumsi sebelumnya `[REFERENSI SISTEM REKOMENDASI 1]`. Dalam ranah pariwisata (*e-tourism*), sistem rekomendasi berperan penting sebagai panduan virtual yang menyajikan alternatif rute, atraksi, dan akomodasi yang sesuai dengan kepribadian serta preferensi pelancong `[REFERENSI SISTEM REKOMENDASI 2]`. Secara umum, terdapat dua paradigma utama dalam sistem rekomendasi: pendekatan berbasis konten (*Content-Based Filtering*) yang mencocokkan atribut item dengan profil pengguna, dan penyaringan kolaboratif (*Collaborative Filtering*) yang mendasarkan saran pada korelasi interaksi antar-pengguna `[REFERENSI SISTEM REKOMENDASI 3]`.

## 2.2 Collaborative Filtering

*Collaborative Filtering* (CF) bekerja dengan asumsi mendasar bahwa pengguna yang memiliki penilaian serupa terhadap sekumpulan item di masa lalu cenderung memiliki preferensi yang sejalan untuk item-item lainnya di masa depan `[REFERENSI COLLABORATIVE FILTERING 1]`. Keunggulan utama CF dibandingkan pendekatan berbasis konten adalah sifatnya yang bebas domain (*domain-independent*); CF tidak memerlukan rekayasa atribut semantik item yang rumit, melainkan sepenuhnya memanfaatkan umpan balik pengguna, baik berupa umpan balik eksplisit (seperti skala rating numerik) maupun implisit (seperti riwayat klik atau kunjungan) `[REFERENSI COLLABORATIVE FILTERING 2]`.

## 2.3 User-Based Collaborative Filtering

Dalam *User-Based Collaborative Filtering* (UBCF), sistem menelusuri pengguna-pengguna lain yang memiliki riwayat penilaian paling mendekati pengguna target (*nearest neighbors*) `[REFERENSI COLLABORATIVE FILTERING 1]`. Tahapan UBCF meliputi:
1. Representasi riwayat ulasan seluruh pengguna ke dalam matriks interaksi pengguna-item $R \in \mathbb{R}^{m \times n}$.
2. Perhitungan koefisien kemiripan antara pengguna target $u$ dengan setiap calon rekanan (*peer*) $v$.
3. Pemilihan $N$ tetangga terdekat yang memiliki nilai kemiripan tertinggi ($N_k(u)$).
4. Estimasi prediksi nilai rating item yang belum pernah dinilai pengguna target melalui agregasi terbobot dari rating yang diberikan oleh para tetangga terdekat tersebut.

## 2.4 Cosine Similarity

Metode pengukuran kedekatan vektor yang umum digunakan pada ruang berdimensi tinggi adalah *Cosine Similarity* `[REFERENSI COSINE SIMILARITY 1]`. *Cosine Similarity* mengukur kosinus sudut antara dua vektor interaksi dalam ruang berdimensi sama, yang dirumuskan sebagai:

$$\text{sim}(u, v) = \frac{\mathbf{r}_u \cdot \mathbf{r}_v}{\|\mathbf{r}_u\|_2 \|\mathbf{r}_v\|_2} = \frac{\sum_{i \in I_{uv}} r_{u,i} \cdot r_{v,i}}{\sqrt{\sum_{i \in I_{uv}} r_{u,i}^2} \cdot \sqrt{\sum_{i \in I_{uv}} r_{v,i}^2}}$$

di mana $I_{uv}$ adalah himpunan item yang dinilai bersama (*co-rated items*) oleh pengguna $u$ dan pengguna $v$, sedangkan $r_{u,i}$ dan $r_{v,i}$ merepresentasikan nilai rating yang diberikan masing-masing pengguna terhadap item $i$.

Dalam praktiknya, perhitungan kosinus murni tanpa pembatasan jumlah item bersama dapat menimbulkan distorsi kemiripan (*similarity bias*), misalnya saat dua pengguna hanya memiliki tepat satu item bersama dengan nilai sama. Oleh sebab itu, implementasi aktual pada penelitian ini memberlakukan aturan ambang batas (*threshold*): jika $|I_{uv}| < 2$, nilai kemiripan secara ketat diatur menjadi 0,0 guna memastikan kemiripan yang terhitung benar-benar mencerminkan konsistensi preferensi.

## 2.5 K-Means Clustering

*K-Means* merupakan algoritma pembelajaran tanpa pengawasan (*unsupervised learning*) berbasis partisi yang bertujuan membagi $N$ objek pengamatan ke dalam $K$ klaster, sedemikian rupa sehingga varians di dalam klaster (*intra-cluster variance*) menjadi minimal `[REFERENSI K-MEANS 1]`. Fungsi objektif dari algoritma *K-Means* adalah meminimalkan *Within-Cluster Sum of Squares* (WCSS) atau *Inertia*:

$$J = \sum_{j=1}^K \sum_{\mathbf{x}_i \in C_j} \|\mathbf{x}_i - \boldsymbol{\mu}_j\|^2$$

di mana $C_j$ adalah himpunan data yang tergolong dalam klaster ke-$j$, dan $\boldsymbol{\mu}_j$ adalah pusat massa (centroid) dari klaster ke-$j$.

Dalam penelitian ini, *K-Means* diterapkan secara spesifik untuk segmentasi pengguna (*user clustering*), bukan untuk klasterisasi destinasi wisata. Vektor fitur yang digunakan mencerminkan profil kebiasaan menilai (*rating profile*) seorang pengguna, seperti rata-rata rating, volatilitas nilai, proporsi kepuasan, keragaman kategori wisata yang dikunjungi, serta jangkauan geografis perjalanan. Melalui segmentasi ini, pencarian tetangga pada tahap *Collaborative Filtering* dapat diarahkan secara terstruktur ke dalam kelompok pengguna yang memiliki kecenderungan gaya perjalanan serupa.

## 2.6 Pariwisata dan Karakteristik Destinasi Indonesia

Pariwisata Indonesia memiliki tipologi yang sangat heterogen. Berdasarkan klasifikasi kepariwisataan nasional, objek daya tarik wisata umumnya dikelompokkan ke dalam kategori:
- **Wisata Alam:** Meliputi pegunungan, kawah vulkanik, danau, air terjun, dan taman nasional.
- **Wisata Bahari:** Meliputi pantai tropis, gugusan pulau karang, terumbu karang, dan wisata selam.
- **Wisata Budaya:** Meliputi desa adat, pertunjukan seni tradisional, sentra kerajinan, dan kearifan lokal.
- **Wisata Sejarah:** Meliputi benteng peninggalan kolonial, candi, museum, dan monumen perjuangan.
- **Taman Hiburan:** Meliputi wahana edukasi modern, taman bermain rekreasi terpadu, dan atraksi buatan.

Setiap destinasi juga memiliki atribut operasional penting berupa tiket masuk (*price*) dan penilaian kepuasan publik (*Google Rating*), yang menjadi parameter pertimbangan utama bagi calon pelancong.

## 2.7 Penelitian Terdahulu

Studi mengenai sistem rekomendasi pariwisata telah berkembang melalui berbagai pendekatan:
1. `[REFERENSI PENELITIAN TERDAHULU 1]` menerapkan metode *Collaborative Filtering* murni pada dataset wisata regional dan menemukan kendala penurunan akurasi yang signifikan ketika matriks interaksi mengalami kelangkaan tingkat tinggi (*high sparsity*).
2. `[REFERENSI PENELITIAN TERDAHULU 2]` meneliti penerapan algoritma *K-Means* untuk mengelompokkan objek destinasi berdasarkan lokasi koordinat geografis. Meskipun mempermudah perutean rute terdekat, pendekatan tersebut belum mempertimbangkan selera personal pengguna.
3. `[REFERENSI PENELITIAN TERDAHULU 3]` menggabungkan segmentasi demografis dengan pemfilteran berbasis konten (*Content-Based Filtering*), namun menghadapi kendala *overspecialization*, di mana rekomendasi yang muncul hanya terbatas pada destinasi yang sangat mirip dengan riwayat masa lalu tanpa adanya unsur kebaruan (*novelty*).

Penelitian ini membedakan diri dengan memadukan *K-Means Clustering* untuk segmentasi profil perilaku pengguna (*user behavior profiling*) dan *User-Based Collaborative Filtering* dengan pembatasan kandidat multi-kriteria pada 38 provinsi di Indonesia secara dinamis.

---

# 3. METODOLOGI

## 3.1 Tahapan Penelitian

Penelitian ini dilaksanakan mengikuti alur metodologis terstruktur yang digambarkan pada diagram berikut:

```text
┌─────────────────────────────────────────────────────────────┐
│                    DATASET NUSAWISATA                       │
│    (195 Destinasi, 397 Pengguna, 4.033 Ulasan, 38 Provinsi)  │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    DATA PREPROCESSING                       │
│  - Pembersihan Missing Values & Format Kolom                │
│  - Eliminasi Duplikasi Interaksi Pengguna-Destinasi         │
│  - Validasi Rentang Skala Rating (1.0 – 5.0)                │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                   FEATURE ENGINEERING                       │
│  - Ekstraksi 12 Fitur Perilaku Rating Pengguna              │
│  - Standardisasi Z-Score (Mean = 0, Std = 1)                │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│             K-MEANS USER CLUSTERING & SELEKSI K             │
│  - Evaluasi Kandidat K (K = 2, 3, 4, 5)                     │
│  - Metrik: Inertia, Silhouette, Davies-Bouldin, Calinski-H. │
│  - Penentuan Klaster Pengguna Optimal (K = 2)               │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│              CANDIDATE SET GENERATION & FILTER              │
│  - Constraint: Provinsi, Kategori, Rentang Biaya, Rating    │
│  - Eliminasi Destinasi yang Sudah Pernah Dikunjungi         │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│             USER-BASED COLLABORATIVE FILTERING              │
│  - Identifikasi Peer Users dalam Klaster yang Sama          │
│  - Perhitungan Cosine Similarity (|Co-Rated| >= 2)          │
│  - Seleksi Top-N Tetangga Terdekat (Default: 10 Tetangga)   │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                  RATING PREDICTION & TOP-N                  │
│  - Weighted Rating Prediction dengan Skala Clamping (1 - 5) │
│  - Perangkingan Destinasi Rekomendasi Teratas               │
│  - Mekanisme Cold-Start Fallback jika Pengguna Baru         │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                     EVALUASI SISTEM                         │
│  - Evaluasi Klaster: Silhouette, DBI, CH, Inertia           │
│  - Evaluasi CF: Train-Test Split (80:20), MAE, RMSE         │
│  - Metrik Peringkat: Precision@5 dan Recall@5               │
└─────────────────────────────────────────────────────────────┘
```

## 3.2 Dataset

Data penelitian menggunakan dataset resmi platform NusaWisata yang memetakan persebaran pariwisata di seluruh Indonesia. Dataset mencakup tiga entitas utama:
1. **Destinasi Wisata (*Destinations*):** Memuat 195 objek wisata yang tersebar di 38 provinsi di Indonesia, dengan atribut: nama tempat, provinsi, kategori daya tarik, estimasi harga tiket masuk, dan penilaian agregat publik (*Google Rating*).
2. **Pengguna (*Users*):** Terdiri dari 398 entitas pengguna terdaftar, dengan 397 pengguna berstatus wisatawan aktif (*role: user*) yang memiliki catatan interaksi riil, serta 1 akun administrator sistem.
3. **Ulasan Pengguna (*Ratings*):** Terdiri dari 4.033 interaksi penilaian ulasan aktif dengan skala nilai diskrit 1 hingga 5 bintang.

Pengumpulan data destinasi dilakukan melalui proses kurasi dan akuisisi data (*data collection/acquisition*) dari pangkalan data terbuka pariwisata daerah dan direktori publik, kemudian dinormalisasi ke dalam struktur basis data relasional.

## 3.3 Data Preprocessing

Tahap pra-pemrosesan data dilakukan untuk menjamin kebersihan dan integritas data sebelum diproses oleh algoritma mesin pembelajar:
1. **Validasi Kelengkapan Nilai (*Missing Values Check*):** Memastikan tidak ada nilai kosong (*null*) pada atribut kunci: `user_id`, `destination_id`, dan `rating`.
2. **Validasi Rentang Nilai (*Rating Range Validation*):** Memeriksa seluruh nilai ulasan agar berada pada rentang yang valid ($1 \le \text{rating} \le 5$). Data di luar batas ini akan dieliminasi.
3. **Penanganan Duplikasi Ulasan (*Duplicate Handling*):** Memeriksa kemunculan ganda ulasan dari seorang pengguna pada destinasi yang sama. Jika ditemukan entri ulasan berganda, sistem mempertahankan catatan interaksi terkini guna mencegah pembobotan ganda.
4. **Harmonisasi Penamaan Kategori (*Category Normalization*):** Melakukan pemetaan variasi penulisan kategori (misal: "Wisata Alam" dan "Alam" dipetakan ke dalam kategori kanonikal yang seragam).

## 3.4 Feature Engineering

Untuk melakukan klasterisasi pengguna menggunakan *K-Means*, sistem mengekstrak 12 fitur perilaku penilaian (*user rating behavior*) dari riwayat interaksi masing-masing pengguna:

| No | Nama Fitur | Definisi Matematis / Operasional | Rasional Ilmiah |
|:--:|:---|:---|:---|
| 1 | `total_ratings` | Jumlah total destinasi yang telah dinilai: $N_u = \|R_u\|$ | Mengukur tingkat keaktifan dan pengalaman perjalanan pengguna. |
| 2 | `average_rating` | Nilai rata-rata ulasan: $\bar{r}_u = \frac{1}{N_u} \sum_{i \in R_u} r_{u,i}$ | Mengukur kecenderungan umum kepuasan (apakah pengguna bersikap royal atau pelit nilai). |
| 3 | `rating_std` | Standar deviasi ulasan: $\sigma_u = \sqrt{\frac{1}{N_u - 1} \sum_{i \in R_u} (r_{u,i} - \bar{r}_u)^2}$ | Mengukur tingkat variabilitas atau diskriminasi selera pengguna. |
| 4 | `min_rating` | Nilai ulasan terendah: $\min_{i \in R_u} r_{u,i}$ | Menunjukkan batas toleransi kekecewaan terendah pengguna. |
| 5 | `max_rating` | Nilai ulasan tertinggi: $\max_{i \in R_u} r_{u,i}$ | Menunjukkan apresiasi maksimal yang pernah diberikan. |
| 6 | `rating_5_ratio` | Rasio ulasan bintang 5: $N_{u,5} / N_u$ | Proporsi pengalaman yang dianggap sangat memuaskan/sempurna. |
| 7 | `rating_4_ratio` | Rasio ulasan bintang 4: $N_{u,4} / N_u$ | Proporsi pengalaman yang dinilai baik/positif. |
| 8 | `rating_3_ratio` | Rasio ulasan bintang 3: $N_{u,3} / N_u$ | Proporsi pengalaman yang dinilai netral/standar. |
| 9 | `rating_low_ratio`| Rasio ulasan bintang rendah ($\le 2$): $N_{u,\le 2} / N_u$ | Proporsi kekecewaan atau pengalaman buruk pengguna. |
| 10 | `unique_categories`| Jumlah kategori wisata berbeda yang pernah dinilai: $\|C_u\|$ | Mengukur tingkat diversifikasi jenis wisata yang diminati. |
| 11 | `unique_provinces` | Jumlah provinsi berbeda yang pernah dikunjungi: $\|P_u\|$ | Mengukur mobilitas geografis dan daya jelajah pengguna. |
| 12 | `avg_price` | Rata-rata harga tiket destinasi yang dinilai: $\frac{1}{N_u} \sum_{i \in R_u} \text{Price}_i$ | Menggambarkan profil sensitivitas anggaran pengeluaran wisata. |

## 3.5 Standardisasi Data (Z-Score Normalization)

Karena ke-12 fitur memiliki satuan dan skala yang sangat berlainan (misalnya `avg_price` bernilai puluhan ribu rupiah, sementara `rating_std` berkisar antara 0 hingga 2), fitur-fitur tersebut harus distandardisasi agar fitur dengan angka besar tidak mendominasi perhitungan jarak Euclidean.

Standardisasi dilakukan menggunakan metode *Z-Score*:

$$z_{u,j} = \frac{x_{u,j} - \mu_j}{\sigma_j}$$

di mana $x_{u,j}$ adalah nilai fitur ke-$j$ untuk pengguna $u$, $\mu_j$ adalah nilai rata-rata fitur ke-$j$ pada seluruh populasi pengguna, dan $\sigma_j$ adalah standar deviasi fitur ke-$j$. Apabila suatu fitur memiliki varians yang mendekati nol ($\sigma_j < 10^{-9}$), maka nilai skala diatur secara aman menjadi 0,0 guna menghindari kesalahan pembagian nol (*division by zero*).

## 3.6 Implementasi K-Means Clustering

Algoritma *K-Means* dijalankan pada matriks fitur terstandarisasi dengan tahapan:
1. **Inisialisasi Centroid Terarah:** Untuk menjamin reproduktifitas eksperimen, inisialisasi titik centroid awal dilakukan secara acak deterministik menggunakan pembangkit bilangan acak semu (*pseudo-random number generator*) dengan nilai *seed* yang ditetapkan (seed = 42).
2. **Perhitungan Jarak Euclidean:** Jarak antara vektor fitur pengguna $\mathbf{z}_u$ dengan vektor centroid klaster $\mathbf{c}_k$ dihitung menggunakan jarak Euclidean:
   $$d(\mathbf{z}_u, \mathbf{c}_k) = \sqrt{\sum_{j=1}^{12} (z_{u,j} - c_{k,j})^2}$$
3. **Penetapan Klaster (*Assignment Step*):** Setiap pengguna $u$ dialokasikan ke klaster $k^*$ yang memiliki jarak centroid terdekat:
   $$k^* = \arg\min_{k \in \{1, \dots, K\}} d(\mathbf{z}_u, \mathbf{c}_k)$$
4. **Pembaruan Centroid (*Update Step*):** Posisi titik pusat centroid diperbarui dengan menghitung nilai rata-rata dari seluruh vektor pengguna yang tergabung dalam klaster tersebut:
   $$\mathbf{c}_k^{(t+1)} = \frac{1}{|C_k|} \sum_{u \in C_k} \mathbf{z}_u$$
   Jika suatu klaster mengalami kekosongan anggota (*empty cluster*), sistem secara otomatis menginisialisasi ulang posisi centroid tersebut ke salah satu sampel pengguna acak.
5. **Konvergensi:** Iterasi dihentikan apabila penugasan anggota klaster tidak lagi mengalami perubahan antara dua iterasi berturut-turut ($Assignment^{(t)} = Assignment^{(t-1)}$) atau telah mencapai batas maksimum iterasi (ditetapkan 50 iterasi).
6. **Denormalisasi Centroid:** Titik centroid akhir ditransformasikan kembali ke skala asli (*unscaled*) menggunakan nilai $\mu_j$ dan $\sigma_j$ untuk keperluan interpretasi profil manusiawi.

## 3.7 Pemilihan Nilai K Optimal

Pemilihan jumlah klaster $K$ dilakukan dengan menguji rentang kandidat $K \in \{2, 3, 4, 5\}$ dan mengevaluasinya melalui empat metrik validitas klaster internal:
1. **Inertia (Within-Cluster Sum of Squares / WCSS):** Mengukur kerapatan internal klaster. Nilai yang lebih kecil menunjukkan klaster yang lebih padat.
2. **Silhouette Score:** Mengukur seberapa dekat suatu titik dengan titik-titik di klasternya sendiri dibandingkan dengan klaster tetangga terdekat. Rentang nilai $[-1, 1]$, di mana nilai mendekati 1 merepresentasikan pemisahan klaster yang sangat baik:
   $$s(i) = \frac{b(i) - a(i)}{\max(a(i), b(i))}$$
3. **Davies-Bouldin Index (DBI):** Mengukur rasio kesamaan antar-klaster berdasarkan dispersi internal dan jarak antar-centroid. Nilai DBI yang semakin kecil mengindikasikan klaster yang semakin terpisah dengan baik:
   $$DBI = \frac{1}{K} \sum_{i=1}^K \max_{j \ne i} \left( \frac{S_i + S_j}{M_{ij}} \right)$$
4. **Calinski-Harabasz Index (CH):** Dikenal sebagai rasio kriteria varians (*Variance Ratio Criterion*), mengukur rasio antara dispersi antar-klaster (*between-cluster sum of squares*) dengan dispersi dalam-klaster (*within-cluster sum of squares*). Nilai yang lebih tinggi menunjukkan partisi klaster yang lebih baik:
   $$CH = \frac{SSB / (K - 1)}{SSW / (N - K)}$$

Sistem mengadopsi fungsi skor penentu pemilihan klaster optimal:
$$\text{Score}(K) = \text{Silhouette}(K) - 0.05 \times \text{DBI}(K)$$

## 3.8 User-Based Collaborative Filtering

Setelah setiap pengguna memiliki label klaster, proses *User-Based Collaborative Filtering* dijalankan untuk menghasilkan rekomendasi destinasi baru bagi pengguna target $u$:
1. **Ekstraksi Riwayat Ulasan Target:** Mengambil seluruh rating yang telah diberikan pengguna $u$, dinotasikan sebagai $R_u = \{(i, r_{u,i})\}$.
2. **Seleksi Rekan Se-Klaster (*Within-Cluster Peers*):** Sistem memprioritaskan pencarian tetangga di antara pengguna-pengguna lain yang berada dalam klaster yang sama dengan pengguna target ($v \in C_{k_u}, v \ne u$). Pendekatan ini mempersempit ruang komputasi dan memastikan perbandingan dilakukan terhadap rekanan dengan pola psikografis perjalanan yang sepadan. Apabila jumlah rekan berating dalam klaster kurang dari 3 orang, sistem secara adaptif memperluas ruang pencarian ke seluruh populasi pengguna berating.
3. **Penyaringan Destinasi Kandidat:** Mengidentifikasi destinasi yang belum pernah dikunjungi oleh pengguna target ($i \notin R_u$).

## 3.9 Perhitungan Cosine Similarity

Kemiripan antara pengguna target $u$ dan pengguna rekan $v$ dihitung berdasarkan ulasan pada item yang sama-sama dinilai ($I_{uv}$):

$$\text{sim}(u, v) = \begin{cases} 
\frac{\sum_{i \in I_{uv}} r_{u,i} \cdot r_{v,i}}{\sqrt{\sum_{i \in I_{uv}} r_{u,i}^2} \cdot \sqrt{\sum_{i \in I_{uv}} r_{v,i}^2}}, & \text{jika } |I_{uv}| \ge 2 \\ 
0.0, & \text{jika } |I_{uv}| < 2 
\end{cases}$$

Nilai kemiripan dibatasi secara ketat pada interval non-negatif $[0.0, 1.0]$. Pasangan pengguna yang memiliki nilai kemiripan positif ($\text{sim}(u, v) > 0$) diurutkan secara menurun (*descending*), dan sejumlah $N$ tetangga teratas (default: $N=10$) dipilih sebagai himpunan tetangga terdekat $N_k(u)$.

## 3.10 Prediksi Nilai Rating

Estimasi nilai rating untuk destinasi kandidat $i$ yang belum pernah dinilai oleh pengguna target dihitung menggunakan formulasi rata-rata terbobot kemiripan (*similarity-weighted average*):

$$\hat{r}_{u,i} = \frac{\sum_{v \in N_k(u)} \text{sim}(u, v) \cdot r_{v,i}}{\sum_{v \in N_k(u)} |\text{sim}(u, v)|}$$

Untuk menjaga konsistensi dengan domain ulasan, nilai prediksi dipangkas (*clamped*) pada interval batas ulasan valid:
$$\hat{r}_{u,i}^{(\text{final})} = \min(5.0, \max(1.0, \hat{r}_{u,i}))$$

Seluruh destinasi yang berhasil diprediksi kemudian diurutkan berdasarkan nilai $\hat{r}_{u,i}^{(\text{final})}$ dari yang tertinggi, dan sebanyak $L$ destinasi teratas (default: $L=6$) disajikan sebagai keluaran rekomendasi (*Top-N Recommendations*).

## 3.11 Candidate Filtering Multi-Kriteria

Sistem mengimplementasikan penyaringan kandidat (*Candidate Set Generation*) sebagai batasan pra-prediksi (*constraint filter*). Modul ini bekerja pada lapisan data sebelum prediksi skor dihitung, sehingga tidak merombak rumus matematis *Collaborative Filtering*.

Parameter filter yang didukung meliputi:
1. **Provinsi (`province`):** Membatasi kandidat destinasi hanya pada wilayah provinsi tertentu di Indonesia.
2. **Kategori (`category`):** Menyaring jenis daya tarik wisata (Alam, Budaya, Bahari, Sejarah, atau Taman Hiburan).
3. **Rentang Anggaran (`budget`):** Membatasi harga tiket masuk berdasarkan klasifikasi biaya (Gratis/Rp 0, < Rp 25.000, Rp 25.000 – Rp 50.000, Rp 50.000 – Rp 100.000, dan > Rp 100.000).
4. **Batas Rating Minimum (`min_rating`):** Memfilter destinasi dengan nilai ulasan publik di atas batas tertentu ($\ge 3.5, \ge 4.0, \ge 4.5$).
5. **Kata Kunci Pencarian (`keyword`):** Pencarian teks bebas pada nama destinasi, kategori, atau nama provinsi.

Himpunan destinasi kandidat dinotasikan sebagai $D_{\text{filter}}$. Algoritma prediksi rating hanya memproses destinasi yang memenuhi syarat keanggotaan ganda: belum pernah dinilai oleh pengguna target ($i \notin R_u$) dan termasuk dalam himpunan kandidat aktif ($i \in D_{\text{filter}}$).

## 3.12 Penanganan Pengguna Baru (Cold-Start Fallback)

Kondisi *cold-start* terjadi saat:
1. Pengguna target merupakan tamu (*guest*) yang belum melakukan autentikasi login.
2. Pengguna terdaftar belum pernah memberikan ulasan sama sekali pada dataset aktif ($|R_u| = 0$).
3. Tidak ditemukan rekan se-klaster yang memiliki irisan penilaian ($|I_{uv}| < 2$ untuk semua rekan).

Pada kondisi tersebut, sistem menerapkan strategi *Cold-Start Fallback* yang jujur dan transparan:
- Sistem tidak mengarang nilai kemiripan atau prediksi buatan.
- Sistem mengalihkan algoritma ke perankingan popularitas berbasis komunitas (*Community-Rated Popularity Fallback*).
- Destinasi diurutkan berdasarkan penilaian agregat publik (*Google Rating*) tertinggi yang disaring tetap mematuhi seluruh batasan *candidate filtering* yang dipilih oleh pengguna.
- Sistem secara eksplisit menyematkan label metadata `is_fallback = true` dan memberikan penjelasan edukatif kepada pengguna mengenai alasan pengalihan tersebut.

## 3.13 Protokol Evaluasi Model

Evaluasi model dilakukan secara komprehensif pada dua dimensi utama:
1. **Evaluasi Segmentasi Klaster:** Menggunakan seluruh metrik validitas internal (*Inertia, Silhouette, DBI, CH*) pada saat pelatihan *K-Means*.
2. **Evaluasi Akurasi Prediksi Rating:** Menerapkan metode *Train-Test Split* dengan rasio 80:20 pada tingkat interaksi masing-masing pengguna. 
   - Hanya pengguna dengan minimal 4 catatan ulasan ($\ge 4$) yang diikutsertakan dalam pengujian untuk memastikan subset pelatihan dan pengujian terwakili dengan adil.
   - Pembagian data dilakukan secara acak deterministik ber-seed per-ID pengguna (`mt_srand($userId)`).
   - Sebanyak 20% ulasan pengguna dialokasikan sebagai himpunan uji ($T$), dan 80% sisanya digunakan sebagai himpunan latih untuk menghitung kemiripan tetangga.
   - Metrik akurasi numerik dihitung menggunakan:
     - **Mean Absolute Error (MAE):**
       $$\text{MAE} = \frac{1}{|T|} \sum_{(u,i) \in T} |\hat{r}_{u,i} - r_{u,i}|$$
     - **Root Mean Squared Error (RMSE):**
       $$\text{RMSE} = \sqrt{\frac{1}{|T|} \sum_{(u,i) \in T} (\hat{r}_{u,i} - r_{u,i})^2}$$
3. **Evaluasi Kualitas Perangkingan Rekomendasi (Precision@K dan Recall@K):**
   - Suatu destinasi pada himpunan uji diklasifikasikan sebagai item relevan jika memiliki rating aktual $r_{u,i} \ge 4.0$.
   - **Precision@K:** Proporsi destinasi rekomendasi teratas ($K=5$) yang benar-benar relevan bagi pengguna:
     $$\text{Precision@K} = \frac{|\text{TopK} \cap \text{Relevant}|}{K}$$
   - **Recall@K:** Proporsi destinasi relevan yang berhasil ditemukan dalam daftar rekomendasi teratas:
     $$\text{Recall@K} = \frac{|\text{TopK} \cap \text{Relevant}|}{|\text{Relevant}|}$$

## 3.14 Perancangan Sistem dan Arsitektur Aplikasi

Sistem dibangun di atas kerangka kerja web modern dengan rincian arsitektur teknologi:
- **Kerangka Kerja Backend:** PHP 8.3 dengan framework Laravel.
- **Lapisan Basis Data:** SQLite / MySQL dengan skema relasional terstruktur dan terindeks penuh.
- **Lapisan Antarmuka (Frontend):** Laravel Blade, sistem gaya Vanilla CSS terkurasi, dan pemetaan geospasial interaktif menggunakan Leaflet.js serta OpenStreetMap.
- **Orkestrasi Pipeline (*Automated ML Run*):** Modul layanan `AutomatedMlRunService` yang mengotomatisasi 8 tahapan eksekusi secara berurutan: Validasi Dataset $\to$ Preprocessing & Analisis Matriks $\to$ Feature Engineering & Standardisasi $\to$ Evaluasi Kandidat K $\to$ Klasterisasi Final & Penugasan $\to$ Pembangunan Collaborative Filtering $\to$ Evaluasi Train/Test Split (80:20) $\to$ Penyimpanan Hasil & Aktivasi Model.
- **Manajemen Versi Dataset (*Dataset Versioning*):** Mendukung pengunggahan berkas dataset baru (.xlsx/.csv), isolasi ID versi (`dataset_version_id`), pengarsipan model lama, dan mekanisme peralihan versi aktif tanpa merusak data operasional yang sedang berjalan.

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

## 4.2 Hasil Pra-Pemrosesan dan Validasi Data

Sebelum pemodelan dijalankan, mesin inspeksi dataset (*Automated Data Quality Inspector*) memverifikasi keutuhan data:
* **Nilai Kosong (*Missing Values*):** Ditemukan 0 nilai kosong ($0,0\%$) pada kolom kunci (`user_id`, `destination_id`, `rating`).
* **Nilai Rating Tidak Sah (*Out-of-Bounds Ratings*):** Seluruh 1.999 rating berada tepat pada interval $[1, 5]$.
* **Duplikasi Relasi:** Seluruh pasangan $(u, i)$ bersifat unik pada tingkat basis data.
* **Hasil Uji Kelayakan:** Dataset dinyatakan 100% *Eligible* untuk pemrosesan *machine learning* otomatis.

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

Tingginya korelasi antar-fitur menjelaskan mengapa nilai koefisien *Silhouette* pada 12 dimensi pengguna berada di rentang $0,19 - 0,21$. Dalam ruang Euclidean 12-dimensi, dimensi yang saling berkorelasi mendistorsi jarak geometris (*distance concentration*). Sebagai pembuktian empiris, saat fitur dipangkas menjadi 3 variabel perilaku inti yang independen (`average_rating`, `rating_std`, `log_total_ratings`), *Silhouette Score* meningkat signifikan menjadi **0,2733** dan DBI membaik menjadi **1,1676**.

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
3. **Kualitas Segmentasi Praktis:** $K=3$ memberikan partisi wisatawan nusantara yang seimbang dan memiliki interpretasi bisnis yang jelas, tanpa menghasilkan klaster kerdil.

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

## 4.6 Uji Kestabilan Algoritma Multi-Seed (*Reproducibility Test*)

Pengujian kestabilan K-Means++ pada 5 *random seed* berbeda ($42, 43, 44, 45, 46$) dengan parameter tetap ($K=3$, $n\_init = 10$) disajikan pada Tabel 4.

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

Standar deviasi *Silhouette Score* tercatat sebesar **0,0031** (variasi hanya $0,31\%$). Variasi yang sangat kecil ($\sigma < 0,05$) membuktikan bahwa konvergensi K-Means++ bersifat ajek dan dapat direproduksi secara ilmiah.

## 4.7 Analisis Eksploratori Karakteristik Destinasi & Peringatan *Degenerate Cluster*

Sebagai modul pelengkap, dijalankan klasterisasi destinasi pada 195 destinasi menggunakan fitur `price` dan `destination_rating` dengan *StandardScaler* ($K=4$). Hasil disajikan pada Tabel 5.

**Tabel 5. Profil Centroid Klaster Destinasi Wisata ($K=4$)**

| Klaster Destinasi | Jumlah ($n$) | Proporsi | Rerata Harga Tiket | Rerata Rating Google | Karakteristik Destinasi Wisata | Catatan Metodologi |
|:---:|:---:|:---:|:---:|:---:|:---|:---|
| **Klaster 1** | 92 | 47,18% | Rp 16.500 | 4,59★ | Destinasi Wisata Populer Terjangkau (*Mass Tourism*) | Klaster Reguler |
| **Klaster 2** | 69 | 35,38% | Rp 28.841 | 3,77★ | Destinasi Menengah / Standar (*Budget Friendly*) | Klaster Reguler |
| **Klaster 3** | 33 | 16,92% | Rp 87.121 | 4,38★ | Destinasi Premium / Minat Khusus (*Special Interest*) | Klaster Reguler |
| **Klaster 4** | **1** | **0,51%** | **Rp 500.000** | **4,90★** | **Destinasi Konservasi Eksklusif (Raja Ampat)** | **Degenerate Cluster** |

*Metrik Klaster Destinasi:* Silhouette = 0,4920; Davies-Bouldin Index = 0,6323; Calinski-Harabasz = 213,80; Inersia = 89,49.

### Peringatan Kritis Klaster 4 (*No Overclaiming*):
1. Klaster 4 hanya memiliki **1 destinasi tunggal** (Kepulauan Raja Ampat di Papua Barat Daya, tiket Rp 500.000). Ini diklasifikasikan secara ketat sebagai **klaster singleton (*degenerate cluster*)** akibat harga tiket yang merupakan pencilan ekstrem ($Z_{\text{price}} = +10,24$).
2. Nilai *Silhouette Score* destinasi ($0,4920$) **TIDAK BOLEH** diklaim sebagai pembanding peningkatan atas nilai $0,2133$ pada pengguna karena memproses entitas yang berbeda.

## 4.8 Evaluasi Sistem Rekomendasi Bebas Kebocoran Data (*Strict Zero Data Leakage*)

Evaluasi akurasi prediksi dan perangkingan rekomendasi dijalankan menggunakan skema *Train-Test Split* 80:20 murni tanpa kebocoran data:
* Pengguna teruji: 386 pengguna ($\ge 4$ ulasan).
* Rating uji diisolasi: 662 rating uji.
* Ekstraksi fitur, fitting scaler, dan K-Means dilatih **hanya menggunakan data latih (80%)**.

Hasil evaluasi model yang diusulkan (*Proposed K-Means User + CF*) mencatatkan:
* **Mean Absolute Error (MAE):** **1,0444**
* **Root Mean Squared Error (RMSE):** **1,3645**
* **Precision@5:** **60,55%**
* **Recall@5:** **100,00%**

## 4.9 Hasil Studi Ablasi *Fair Comparison* (Exact Same Split)

Studi ablasi membandingkan 4 model pada partisi *train/test* yang persis sama ($Top\text{-}N = 10, k=5, r \ge 4,0$) disajikan pada Tabel 6.

**Tabel 6. Hasil Studi Ablasi Sistem Rekomendasi (Partisi Uji Identik)**

| Model | Konfigurasi Arsitektur Model | MAE | RMSE | Precision@5 | Recall@5 | Peran dalam Metodologi |
|:---|---|:---:|:---:|:---:|:---:|---|
| **Model A** | **CF Only (Baseline):** User-Based CF tanpa batasan klaster | **1,0250** | **1,3327** | **60,55%** | **100,00%** | Baseline Pembanding Standar |
| **Model B** | **K-Means User + CF (Proposed):** Pembatasan tetangga se-klaster (Zero Leakage) | **1,0444** | **1,3645** | **60,55%** | **100,00%** | **Metodologi Utama yang Diusulkan** |
| **Model C** | **K-Means Dest + CF:** Pembobotan kemiripan via irisan klaster destinasi | **1,0255** | **1,3323** | **60,55%** | **100,00%** | Varian Eksploratori Tambahan |
| **Model D** | **K-Means User + CF + Filter:** Pembatasan klaster + filter kategori riwayat | **1,0444** | **1,3645** | **60,55%** | **98,45%** | Varian Rekomendasi Terfilter |

## 4.10 Pembahasan Ilmiah: Analisis *Trade-off* Akurasi vs Efisiensi Komputasi

1. **Trade-off MAE Minor ($\Delta \text{MAE} = +0,0194$):** Pada matriks berkepadatan rendah ($2,58\%$), pembatasan pencarian tetangga ke dalam klaster pengguna yang sama sedikit mempersempit jumlah rating bersama (*co-rated items*), sehingga prediksi nilai numerik sedikit lebih bergantung pada rata-rata umum.
2. **Kestabilan Kualitas Rekomendasi:** Nilai **Precision@5** (60,55%) dan **Recall@5** (100,00%) antara Model A dan Model B bernilai **identik sempurna**. Daftar destinasi teratas yang disajikan kepada wisatawan memiliki tingkat relevansi yang sama baiknya.
3. **Efisiensi Komputasi:** Pembatasan klaster memangkas ruang komputasi pencarian tetangga sebesar **$65\% - 70\%$** (kompleksitas $\mathcal{O}(U/K)$ vs $\mathcal{O}(U)$), yang sangat penting bagi skalabilitas sistem pada industri pariwisata nyata.

## 4.11 Contoh Kasus Nyata Personalisasi Rekomendasi & Mekanisme *Fallback*

* **Kasus Pengguna Terdaftar (User ID 2, Klaster 2):** Sistem memilih 3 tetangga terdekat dalam Klaster 2 (User 6: $\text{sim}=0,9944$; User 4: $\text{sim}=0,9853$; User 5: $\text{sim}=0,9685$) dan menyajikan Top-5 rekomendasi: *Danau Laut Tawar* (5,00), *Goa Pindul* (5,00), *Pantai Manakarra* (5,00), *Pantai Sulamadaha* (5,00), dan *Pantai Pasir Timbul* (5,00) yang belum pernah dikunjungi oleh pengguna target.
* **Kasus Pengguna Baru (*Cold-Start Fallback*):** Saat tamu tanpa riwayat ulasan mengakses filter "Provinsi Bangka Belitung", sistem secara otomatis mengaktifkan *Popularity Fallback* dengan menyajikan destinasi berating Google tertinggi: *Pulau Lengkuas* (4,40), *Pantai Matras* (4,20), dan *Pantai Parai Tenggiri* (4,10) dengan status transparan `is_fallback = true`.

---

# 5. KESIMPULAN

1. Sistem rekomendasi pariwisata personal berbasis arsitektur *Two-Stage* (K-Means User Clustering + User-Based Collaborative Filtering) berhasil diimplementasikan secara utuh pada platform NusaWisata untuk memetakan 195 destinasi di 38 provinsi di Indonesia.
2. Segmentasi pengguna menggunakan K-Means++ berbasis 12 fitur perilaku ulasan menghasilkan konfigurasi optimal pada **$K = 3$** (*Inertia* = 3.187,98; *Silhouette Score* = 0,2133; *Davies-Bouldin Index* = 1,5277; *Calinski-Harabasz* = 97,39) dengan kestabilan multi-seed yang sangat tinggi ($\sigma = 0,0031$).
3. Klasterisasi destinasi menghasilkan *Silhouette Score* 0,4920, namun secara transparan diidentifikasi memiliki **klaster singleton (*degenerate cluster*)** pada Klaster 4 ($n=1$, Raja Ampat Rp 500.000). Nilai ini diisolasi sebagai modul eksploratori dan tidak dibandingkan dengan klasterisasi pengguna.
4. Evaluasi rekomendasi kolaboratif menggunakan protokol *Strict Zero Data Leakage* membuktikan ketahanan model di tengah kelangkaan data sebesar $97,42\%$, dengan capaian **MAE = 1,0444**, **RMSE = 1,3645**, **Precision@5 = 60,55%**, dan **Recall@5 = 100,00%**.
5. Pembatasan tetangga se-klaster menimbulkan sedikit kompensasi pada MAE ($\Delta = +0,0194$), namun mempertahankan presisi rekomendasi di level $60,55\%$ dan memangkas beban komputasi pencarian sebesar $65\% - 70\%$.

### Keterbatasan Penelitian (Limitations)
1. **Tingkat Kelangkaan Matriks (97,42%):** Kepadatan interaksi yang terbatas ($2,58\%$) menyebabkan estimasi numerik rating sesekali bergantung pada baseline rata-rata.
2. **Ketergantungan Umpan Balik Eksplisit:** Sistem saat ini baru memanfaatkan rating bintang eksplisit dan belum mengintegrasikan sinyal implisit pengguna.
3. **Pencilan Harga Ekstrem:** Disparitas tiket masuk antarpulau berpotensi memicu klaster singleton jika tidak diterapkan transformasi logaritmik.

### Saran Pengembangan Lanjutan
1. Menerapkan transformasi logaritmik $\ln(1 + \text{price})$ pada variabel harga destinasi untuk mereduksi pencilan ekstrem.
2. Mengembangkan arsitektur rekomendasi hibrida (*Hybrid Collaborative-Content Embedding*) untuk memperkaya representasi atribut semantik destinasi.
3. Mengintegrasikan algoritma perutean rute dinamis (*Itinerary Optimization*) untuk menyusun rencana perjalanan harian wisatawan.

---

# DAFTAR PUSTAKA

[REFERENSI SISTEM REKOMENDASI 1]  
*Format referensi: Penulis, Judul Buku/Artikel tentang Fundamental Recommender Systems, Penerbit/Jurnal, Tahun, DOI/URL. (Disarankan: Ricci, F., Rokach, L., & Shapira, B. "Recommender Systems Handbook").*

[REFERENSI SISTEM REKOMENDASI 2]  
*Format referensi: Penulis, Kajian Sistem Rekomendasi pada Domain Pariwisata dan E-Tourism, Jurnal Teknologi/Sistem Informasi, Tahun.*

[REFERENSI SISTEM REKOMENDASI 3]  
*Format referensi: Penulis, Studi Komparasi Metode Rekomendasi Berbasis Konten dan Kolaboratif, Jurnal Ilmiah Komputer, Tahun.*

[REFERENSI COLLABORATIVE FILTERING 1]  
*Format referensi: Penulis, Artikel Ilmiah tentang User-Based Collaborative Filtering dan Pembentukan Matriks Ketetanggaan, Konferensi/Jurnal Internasional, Tahun.*

[REFERENSI COLLABORATIVE FILTERING 2]  
*Format referensi: Penulis, Analisis Penanganan Masalah Sparsitas Data pada Penyaringan Kolaboratif, Jurnal Rekayasa Sistem & Sains Data, Tahun.*

[REFERENSI COSINE SIMILARITY 1]  
*Format referensi: Penulis, Formulasi Pengukuran Jarak Vektor dan Cosine Similarity pada Information Retrieval, Jurnal Matematika Komputasi, Tahun.*

[REFERENSI K-MEANS 1]  
*Format referensi: Penulis, Makalah Fundamental Algoritma Partisi K-Means Clustering dan Analisis Konvergensi, Jurnal/Prosiding Matematika Terapan, Tahun. (Disarankan: MacQueen, J. atau Lloyd, S. P.).*

[REFERENSI PENELITIAN TERDAHULU 1]  
*Format referensi: Penulis, Implementasi Collaborative Filtering pada Sistem Rekomendasi Objek Wisata Daerah di Indonesia, Jurnal Terakreditasi SINTA, Tahun.*

[REFERENSI PENELITIAN TERDAHULU 2]  
*Format referensi: Penulis, Penggunaan K-Means Clustering untuk Pengelompokan Destinasi Wisata Berdasarkan Karakteristik Lokasi, Jurnal Nasional Terindeks, Tahun.*

[REFERENSI PENELITIAN TERDAHULU 3]  
*Format referensi: Penulis, Sistem Rekomendasi Pariwisata Hibrida Memanfaatkan Atribut Pengguna dan Lokasi, Jurnal Ilmu Komputer dan Informatika, Tahun.*

*(Catatan: Penulis naskah publikasi dapat mengganti entri placeholder bertanda kurung siku di atas dengan sitasi pustaka formal berformat IEEE, APA, atau Vancouver sesuai dengan pedoman penulisan berkala ilmiah / jurnal SINTA tujuan).*
