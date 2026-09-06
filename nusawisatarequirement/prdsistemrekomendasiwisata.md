PRD \& Arsitektur Sistem

Sistem Rekomendasi Wisata Indonesia Menggunakan Collaborative Filtering dan K-Means Clustering



Versi: 1.0

Status: Final — Prototype Tugas Akhir

Platform: Web Application

Target: Prototype skripsi/TA dengan biaya operasional minimum



1\. Ringkasan Produk



Sistem merupakan aplikasi web yang menyediakan informasi dan rekomendasi destinasi wisata di Indonesia.



Sistem menggabungkan:



Collaborative Filtering untuk menghasilkan rekomendasi wisata berdasarkan pola rating pengguna.

K-Means Clustering untuk mengelompokkan pengguna berdasarkan karakteristik/pola preferensi sehingga proses pencarian pengguna yang memiliki kemiripan dapat lebih terarah.

Leaflet.js + OpenStreetMap untuk menampilkan lokasi destinasi wisata pada peta.

Data popularitas harian untuk memberikan informasi mengenai hari dengan tingkat keramaian relatif tertinggi pada suatu destinasi.



Sistem ditujukan sebagai prototype penelitian tugas akhir, sehingga tidak membutuhkan arsitektur enterprise, microservices, AI API, atau infrastruktur cloud yang mahal.



2\. Judul Penelitian



Sistem Rekomendasi Wisata Indonesia Menggunakan Collaborative Filtering dan K-Means Clustering



3\. Tujuan Sistem



Sistem bertujuan untuk:



menyediakan informasi destinasi wisata di Indonesia;

menampilkan destinasi berdasarkan provinsi;

menampilkan destinasi dengan rating tertinggi;

menampilkan lokasi wisata menggunakan peta;

menerima rating dari pengguna;

mengelompokkan pengguna menggunakan K-Means;

menghasilkan rekomendasi wisata menggunakan Collaborative Filtering;

memberikan informasi tingkat popularitas/keramaian berdasarkan hari;

menyediakan prototype yang dapat digunakan sebagai objek implementasi dan pengujian penelitian.

4\. Target Pengguna

4.1 User



Pengguna umum yang ingin:



mencari destinasi wisata;

melihat informasi destinasi;

melihat lokasi pada peta;

melihat rating;

memberikan rating;

mendapatkan rekomendasi wisata personal.

4.2 Admin



Admin bertugas:



mengelola data provinsi;

mengelola destinasi;

mengelola data pengguna;

mengelola rating;

melakukan import dataset;

menjalankan proses K-Means;

melihat hasil clustering;

memantau data sistem.

5\. Scope Sistem

5.1 Fitur User

A. Homepage



Menampilkan:



informasi singkat aplikasi;

pencarian wisata;

daftar provinsi;

peta Indonesia;

beberapa destinasi populer.

B. Daftar Provinsi



User dapat melihat daftar 38 provinsi Indonesia.



Aceh

Sumatera Utara

Sumatera Barat

...

Bali

...

Papua

Papua Barat

C. Destinasi per Provinsi



Setiap provinsi memiliki beberapa destinasi.



Dataset awal menyediakan:



38 provinsi

×

5 destinasi

=

190 destinasi



Sistem dapat menampilkan Top 3 destinasi berdasarkan rating pada masing-masing provinsi.



D. Detail Destinasi



Informasi yang ditampilkan:



nama destinasi;

provinsi;

kategori;

deskripsi;

harga;

rating;

jumlah review;

koordinat;

lokasi pada peta;

popularitas berdasarkan hari;

hari yang relatif paling ramai.

E. Rating



User yang login dapat memberikan rating:



1 ⭐

2 ⭐

3 ⭐

4 ⭐

5 ⭐



Data rating digunakan sebagai input Collaborative Filtering.



F. Rekomendasi Personal



User mendapatkan rekomendasi berdasarkan:



Rating User

&#x20;     ↓

K-Means

&#x20;     ↓

User Cluster

&#x20;     ↓

Collaborative Filtering

&#x20;     ↓

Similarity

&#x20;     ↓

Predicted Rating

&#x20;     ↓

Ranking

&#x20;     ↓

Rekomendasi

6\. Fitur Admin

Dashboard



Menampilkan statistik:



Total User

Total Provinsi

Total Destinasi

Total Rating

Jumlah Cluster

Manajemen Destinasi



CRUD:



Create

Read

Update

Delete

Manajemen Dataset



Admin dapat melakukan:



Upload CSV

&#x20;     ↓

Validasi

&#x20;     ↓

Import

&#x20;     ↓

Database

K-Means



Admin dapat menentukan jumlah cluster:



Jumlah Cluster (K)

\[ 3 ]



\[ Jalankan K-Means ]



Hasil:



Cluster 1 → xxx user

Cluster 2 → xxx user

Cluster 3 → xxx user

Hasil Clustering



Menampilkan:



user;

cluster;

centroid;

jumlah anggota cluster;

jumlah iterasi;

hasil akhir clustering.

7\. Dataset

7.1 Dataset Awal



Dataset awal berupa file Excel yang berisi data:



User\_Id

Place\_Id

Place\_Name

Category

Province

Price

Destination\_Rating

Place\_Ratings



Dataset yang tersedia:



2.000 record

398 user

190 destinasi

38 provinsi



Dataset tersebut menjadi dasar data user, destinasi, dan rating.



8\. Data Crawling



Data tambahan destinasi diperoleh melalui proses crawling secara terpisah menggunakan Python/Google Colab.



Flow

Dataset Awal

&#x20;    ↓

Ambil 190 destinasi

&#x20;    ↓

Python Crawler

&#x20;    ↓

Google Maps

&#x20;    ↓

Data Destinasi

&#x20;    ↓

CSV

&#x20;    ↓

Import Laravel



Data yang ditargetkan:



Google Rating

Review Count

Latitude

Longitude

Popular Times

Catatan metodologis



Data Popular Times diperlakukan sebagai indikator popularitas/keramaian relatif, bukan jumlah pengunjung aktual.



Crawling dilakukan secara berkala, bukan setiap kali user membuka halaman website.



9\. Pipeline Dataset

&#x20;                DATASET AWAL

&#x20;                      │

&#x20;                      ▼

&#x20;              Python / Colab

&#x20;                      │

&#x20;                      ▼

&#x20;               Google Maps

&#x20;                      │

&#x20;          ┌───────────┼───────────┐

&#x20;          ▼           ▼           ▼

&#x20;       Rating      Location    Popularity

&#x20;          │           │        Per Hari

&#x20;          └───────────┼───────────┘

&#x20;                      ▼

&#x20;                 CSV Dataset

&#x20;                      │

&#x20;                      ▼

&#x20;               Laravel Import

&#x20;                      │

&#x20;                      ▼

&#x20;                    MySQL

10\. Arsitektur Sistem

┌─────────────────────────────────────────────────────────────┐

│                       DATA COLLECTION                       │

│                                                             │

│  Dataset Excel                  Google Maps                 │

│       │                              │                      │

│       │                         Python Crawler              │

│       │                              │                      │

│       │                            CSV                      │

│       └───────────────┬──────────────┘                      │

└───────────────────────┼─────────────────────────────────────┘

&#x20;                       │

&#x20;                       ▼

┌─────────────────────────────────────────────────────────────┐

│                         LARAVEL                             │

│                                                             │

│  ┌──────────────┐    ┌─────────────────────────────────┐   │

│  │ Controllers  │───▶│           Services               │   │

│  └──────────────┘    │                                 │   │

│                      │ KMeansService                   │   │

│                      │ CollaborativeFilteringService  │   │

│                      │ RecommendationService          │   │

│                      └───────────────┬─────────────────┘   │

│                                      │                     │

│  ┌──────────────┐                    │                     │

│  │ Blade        │◀───────────────────┘                     │

│  │ Tailwind     │                                          │

│  │ Alpine.js    │                                          │

│  └──────┬───────┘                                          │

└─────────┼───────────────────────────────────────────────────┘

&#x20;         │

&#x20;         ▼

┌───────────────────────┐

│        MySQL          │

│                       │

│ Users                 │

│ Provinces             │

│ Destinations          │

│ Ratings               │

│ Popularities          │

│ Crawl Logs            │

└───────────┬───────────┘

&#x20;           │

&#x20;           ▼

┌─────────────────────────────────────────────────────────────┐

│                    VISUALIZATION                            │

│                                                             │

│              Leaflet.js + OpenStreetMap                     │

│                                                             │

│                 📍 Destination                              │

│                 📍 Destination                              │

│                 📍 Destination                              │

└─────────────────────────────────────────────────────────────┘

11\. Arsitektur Teknologi

Layer	Teknologi

Backend	Laravel 12

Programming Language	PHP 8.3+

Frontend	Blade

CSS	Tailwind CSS

JavaScript	Alpine.js

Map	Leaflet.js

Map Provider	OpenStreetMap

Database	MySQL

Clustering	K-Means

Recommendation	Collaborative Filtering

Crawling	Python

Crawling Environment	Google Colab

Dataset	Excel / CSV

Deployment	Shared Hosting

12\. Struktur Database

users

id

name

email

password

cluster\_id

created\_at

updated\_at

provinces

id

name

created\_at

updated\_at

destinations

id

province\_id

name

category

description

price

latitude

longitude

google\_rating

review\_count

created\_at

updated\_at

ratings

id

user\_id

destination\_id

rating

created\_at

updated\_at



Relasi:



User

&#x20; │

&#x20; └── hasMany Ratings



Destination

&#x20; │

&#x20; └── hasMany Ratings



Rating

&#x20; ├── belongsTo User

&#x20; └── belongsTo Destination

destination\_popularities

id

destination\_id

day\_of\_week

popularity\_score

created\_at

updated\_at



Contoh:



Destination: Pantai X



Monday      30

Tuesday     35

Wednesday   32

Thursday    45

Friday      65

Saturday    92

Sunday      100

crawl\_logs

id

destination\_id

source

scraped\_at

status

13\. K-Means Clustering



K-Means digunakan untuk mengelompokkan user berdasarkan feature yang ditentukan dalam penelitian.



Flow

User Data

&#x20;   ↓

Preprocessing

&#x20;   ↓

Feature Vector

&#x20;   ↓

Tentukan K

&#x20;   ↓

Centroid Awal

&#x20;   ↓

Hitung Euclidean Distance

&#x20;   ↓

Tentukan Cluster

&#x20;   ↓

Update Centroid

&#x20;   ↓

Iterasi

&#x20;   ↓

Konvergen

&#x20;   ↓

Cluster Final



Contoh:



Cluster 1

├── User 1

├── User 5

└── User 10



Cluster 2

├── User 2

├── User 4

└── User 8



Cluster 3

├── User 3

├── User 6

└── User 9



Hasil cluster disimpan pada:



users.cluster\_id

14\. Collaborative Filtering



Sistem menggunakan pola rating user untuk mencari pengguna dengan preferensi yang serupa.



Flow

User Target

&#x20;    ↓

Ambil Cluster User

&#x20;    ↓

Cari User dalam Cluster

&#x20;    ↓

Bentuk User-Item Matrix

&#x20;    ↓

Hitung Similarity

&#x20;    ↓

Cosine Similarity

&#x20;    ↓

Nearest Neighbors

&#x20;    ↓

Cari Destinasi yang Belum Dirating

&#x20;    ↓

Prediksi Rating

&#x20;    ↓

Ranking

&#x20;    ↓

Top N Recommendation



Contoh:



User A

Cluster 2

&#x20;  │

&#x20;  ├── User B → similarity 0.94

&#x20;  ├── User C → similarity 0.89

&#x20;  └── User D → similarity 0.85

&#x20;                │

&#x20;                ▼

&#x20;      Destinasi yang disukai

&#x20;      user B/C/D

&#x20;                │

&#x20;                ▼

&#x20;      Belum dirating User A

&#x20;                │

&#x20;                ▼

&#x20;         Predicted Rating

&#x20;                │

&#x20;                ▼

&#x20;         Recommendation

15\. Integrasi K-Means + Collaborative Filtering



Integrasi kedua metode:



&#x20;                   USER

&#x20;                     │

&#x20;                     ▼

&#x20;               USER FEATURES

&#x20;                     │

&#x20;                     ▼

&#x20;                 K-MEANS

&#x20;                     │

&#x20;                     ▼

&#x20;               USER CLUSTER

&#x20;                     │

&#x20;                     ▼

&#x20;          FILTER USER SECLUSTER

&#x20;                     │

&#x20;                     ▼

&#x20;        COLLABORATIVE FILTERING

&#x20;                     │

&#x20;                     ▼

&#x20;             SIMILARITY USER

&#x20;                     │

&#x20;                     ▼

&#x20;            PREDICTED RATING

&#x20;                     │

&#x20;                     ▼

&#x20;             RANKING WISATA

&#x20;                     │

&#x20;                     ▼

&#x20;              REKOMENDASI



Dengan pendekatan tersebut, K-Means digunakan untuk membantu menentukan kelompok pengguna yang menjadi kandidat dalam proses Collaborative Filtering.



16\. Halaman Website

Public

/

├── Home

├── Destinations

├── Provinces

├── Province Detail

├── Destination Detail

└── Map

User

/login

/register

/recommendations

/ratings

Admin

/admin

/admin/destinations

/admin/provinces

/admin/users

/admin/ratings

/admin/dataset

/admin/clustering

17\. Homepage



Konsep UI:



┌───────────────────────────────────────────────────────────┐

│ WISATA INDONESIA                         Login             │

├───────────────────────────────────────────────────────────┤

│                                                           │

│              Temukan Wisata Indonesia                     │

│                                                           │

│     \[ Cari destinasi... ]    \[ Provinsi ▼ ]              │

│                                                           │

├───────────────────────────────────────────────────────────┤

│                                                           │

│                    LEAFLET MAP                             │

│                                                           │

│          📍                         📍                     │

│                     📍                                    │

│    📍                                  📍                 │

│                                                           │

├───────────────────────────────────────────────────────────┤

│                                                           │

│               DESTINASI TERPOPULER                         │

│                                                           │

│   \[Card]       \[Card]       \[Card]                         │

│                                                           │

└───────────────────────────────────────────────────────────┘

18\. Halaman Provinsi



Contoh:



BALI

────────────────────────────────────────



Top Destinasi Bali



1\. Destinasi A

&#x20;  ⭐ 4.9



2\. Destinasi B

&#x20;  ⭐ 4.8



3\. Destinasi C

&#x20;  ⭐ 4.8



\[ Lihat Semua ]

\[ Lihat di Peta ]



Top 3 dihasilkan berdasarkan rating destinasi.



19\. Halaman Detail Destinasi

┌──────────────────────────────────────────┐

│              FOTO DESTINASI              │

├──────────────────────────────────────────┤

│ Pantai X                                 │

│ Bali                                     │

│                                          │

│ ⭐ 4.8                                   │

│ 8.234 reviews                            │

│                                          │

│ Kategori : Pantai                        │

│ Harga    : Rp50.000                      │

│                                          │

│ Popularitas                              │

│                                          │

│ Sen  ███                                 │

│ Sel  ███                                 │

│ Rab  ████                                │

│ Kam  █████                               │

│ Jum  ██████                              │

│ Sab  █████████                            │

│ Min  ██████████ 🔥                        │

│                                          │

│ Hari paling ramai: Minggu                │

│                                          │

│ \[ Beri Rating ]                          │

└──────────────────────────────────────────┘



&#x20;                MAP



&#x20;         Leaflet + OpenStreetMap

20\. Halaman Rekomendasi

REKOMENDASI UNTUK ANDA



Berdasarkan pola preferensi pengguna

yang memiliki kemiripan.



┌─────────────────────────────────┐

│ Raja Ampat                      │

│ Papua                           │

│ ⭐ Predicted Rating: 4.87       │

│                                 │

│ \[ Lihat Detail ]                │

└─────────────────────────────────┘



┌─────────────────────────────────┐

│ Kawah Ijen                      │

│ Jawa Timur                      │

│ ⭐ Predicted Rating: 4.81       │

│                                 │

│ \[ Lihat Detail ]                │

└─────────────────────────────────┘

21\. Leaflet + OpenStreetMap



Map digunakan untuk visualisasi lokasi destinasi.



Flow:



MySQL

&#x20;↓

latitude

longitude

&#x20;↓

Laravel

&#x20;↓

Blade

&#x20;↓

Leaflet.js

&#x20;↓

OpenStreetMap

&#x20;↓

Marker Destinasi



Contoh marker:



📍 Pantai X

&#x20;  ⭐ 4.8



📍 Destinasi Y

&#x20;  ⭐ 4.7



📍 Destinasi Z

&#x20;  ⭐ 4.6



Leaflet hanya bertanggung jawab terhadap visualisasi peta, bukan proses rekomendasi.



22\. Data Flow Keseluruhan

&#x20;                        ┌──────────────┐

&#x20;                        │ DATASET AWAL │

&#x20;                        │    EXCEL     │

&#x20;                        └──────┬───────┘

&#x20;                               │

&#x20;                               ▼

&#x20;                       ┌───────────────┐

&#x20;                       │ PYTHON COLAB  │

&#x20;                       │    CRAWLER    │

&#x20;                       └───────┬───────┘

&#x20;                               │

&#x20;                               ▼

&#x20;                        ┌─────────────┐

&#x20;                        │ GOOGLE MAPS │

&#x20;                        └──────┬──────┘

&#x20;                               │

&#x20;                               ▼

&#x20;                             CSV

&#x20;                               │

&#x20;                               ▼

&#x20;                        ┌─────────────┐

&#x20;                        │   LARAVEL   │

&#x20;                        └──────┬──────┘

&#x20;                               │

&#x20;                               ▼

&#x20;                           MYSQL

&#x20;                               │

&#x20;               ┌───────────────┼────────────────┐

&#x20;               │               │                │

&#x20;               ▼               ▼                ▼

&#x20;             USER          DESTINATION        RATING

&#x20;               │               │                │

&#x20;               └───────────────┼────────────────┘

&#x20;                               │

&#x20;                               ▼

&#x20;                          K-MEANS

&#x20;                               │

&#x20;                               ▼

&#x20;                         USER CLUSTER

&#x20;                               │

&#x20;                               ▼

&#x20;                    COLLABORATIVE FILTERING

&#x20;                               │

&#x20;                               ▼

&#x20;                        RECOMMENDATION

&#x20;                               │

&#x20;                               ▼

&#x20;                        WEB INTERFACE

&#x20;                               │

&#x20;                   ┌───────────┴───────────┐

&#x20;                   ▼                       ▼

&#x20;                LEAFLET              RECOMMENDATION

&#x20;              + OSM MAP

23\. Struktur Project Laravel

app/

│

├── Http/

│   ├── Controllers/

│   │   ├── HomeController.php

│   │   ├── ProvinceController.php

│   │   ├── DestinationController.php

│   │   ├── RatingController.php

│   │   ├── RecommendationController.php

│   │   │

│   │   └── Admin/

│   │       ├── DashboardController.php

│   │       ├── ProvinceController.php

│   │       ├── DestinationController.php

│   │       ├── DatasetController.php

│   │       └── ClusteringController.php

│   │

│   └── Requests/

│       ├── RatingRequest.php

│       └── DatasetImportRequest.php

│

├── Models/

│   ├── User.php

│   ├── Province.php

│   ├── Destination.php

│   ├── Rating.php

│   ├── DestinationPopularity.php

│   └── CrawlLog.php

│

├── Services/

│   ├── KMeansService.php

│   ├── CollaborativeFilteringService.php

│   └── RecommendationService.php

│

└── Imports/

&#x20;   ├── DestinationImport.php

&#x20;   └── RatingImport.php

24\. Prinsip Arsitektur



Untuk prototype ini digunakan modular service-oriented structure di dalam Laravel.



Controller tidak menangani perhitungan algoritma secara langsung.



Controller

&#x20;   ↓

Service

&#x20;   ↓

Repository/Model

&#x20;   ↓

MySQL



Contoh:



RecommendationController

&#x20;         ↓

RecommendationService

&#x20;         ↓

CollaborativeFilteringService

&#x20;         ↓

Rating Model

&#x20;         ↓

MySQL



K-Means:



ClusteringController

&#x20;         ↓

KMeansService

&#x20;         ↓

User Model

&#x20;         ↓

MySQL



Struktur tersebut menjaga agar implementasi algoritma tetap terpisah dari HTTP/UI layer.



25\. Proses Import Dataset

Admin Upload CSV

&#x20;      ↓

Validation

&#x20;      ↓

Parse CSV

&#x20;      ↓

Normalize Data

&#x20;      ↓

Mapping Province

&#x20;      ↓

Mapping Destination

&#x20;      ↓

Mapping User

&#x20;      ↓

Insert/Update

&#x20;      ↓

MySQL



Duplicate data harus ditangani berdasarkan identifier yang sesuai:



User\_Id

Place\_Id



sehingga import dataset tidak menghasilkan data duplikat.



26\. Proses Crawling



Crawling tidak dijalankan di server Laravel.



dataset.xlsx

&#x20;    ↓

Google Colab

&#x20;    ↓

Python

&#x20;    ↓

Ambil Place\_Name + Province

&#x20;    ↓

Cari destinasi

&#x20;    ↓

Ambil data yang tersedia

&#x20;    ↓

Cleaning

&#x20;    ↓

Validation

&#x20;    ↓

Export CSV



Hasil:



destinations\_external.csv



dan:



destination\_popularities.csv



Kemudian diimport ke Laravel.



27\. Deployment



Untuk prototype TA:



Developer

&#x20;   ↓

Local Laravel

&#x20;   ↓

MySQL

&#x20;   ↓

Testing

&#x20;   ↓

Shared Hosting



Tidak diperlukan:



❌ VPS

❌ Kubernetes

❌ Microservices

❌ Redis

❌ Elasticsearch

❌ AI API

❌ Cloud ML

28\. Evaluasi Sistem



Karena ini penelitian, evaluasi algoritma harus menjadi bagian penting.



K-Means



Dapat dievaluasi menggunakan:



jumlah cluster;

iterasi;

centroid;

SSE / Within-Cluster Sum of Squares;

Silhouette Score jika diperlukan.

Collaborative Filtering



Dapat menggunakan:



MAE;

RMSE;

Precision@K;

Recall@K.



Contoh:



K = 3



MAE  = 0.xx

RMSE = 0.xx

Precision@5 = xx%

Recall@5    = xx%



Nilai aktual harus berasal dari hasil eksperimen, bukan ditentukan di awal.



29\. Skenario Utama Sistem

Skenario 1 — Explore

User

&#x20;↓

Pilih Provinsi

&#x20;↓

Sistem mengambil destinasi

&#x20;↓

ORDER BY rating DESC

&#x20;↓

Top 3

&#x20;↓

Tampilkan

Skenario 2 — Melihat Lokasi

User

&#x20;↓

Klik Destinasi

&#x20;↓

Ambil latitude + longitude

&#x20;↓

Leaflet

&#x20;↓

OpenStreetMap

&#x20;↓

Marker

Skenario 3 — Memberikan Rating

User Login

&#x20;↓

Buka Destinasi

&#x20;↓

Rating 1–5

&#x20;↓

Simpan Rating

&#x20;↓

Dataset Rating bertambah

Skenario 4 — Rekomendasi

User

&#x20;↓

Rating History

&#x20;↓

K-Means

&#x20;↓

Cluster

&#x20;↓

Collaborative Filtering

&#x20;↓

Similarity

&#x20;↓

Prediction

&#x20;↓

Top N

&#x20;↓

Rekomendasi

30\. Batasan Sistem



Agar scope TA tetap realistis:



Sistem berfokus pada destinasi wisata Indonesia.

Dataset awal berisi 38 provinsi dan 190 destinasi.

Sistem menampilkan maksimal Top 3 destinasi utama pada tampilan ranking provinsi.

Rating digunakan sebagai dasar Collaborative Filtering.

K-Means digunakan untuk clustering user.

Data popularitas harian digunakan sebagai informasi pendukung.

Data popularitas tidak dianggap sebagai jumlah pengunjung aktual.

Crawling dilakukan secara eksternal menggunakan Python/Google Colab.

Hasil crawling disimpan dalam CSV sebelum diimport ke Laravel.

Peta menggunakan Leaflet.js dan OpenStreetMap.

Sistem ditujukan sebagai prototype penelitian, bukan platform pariwisata production-scale.

Tidak terdapat transaksi, pembayaran, booking hotel, atau fitur marketplace.

31\. Struktur Direktori Data

dataset/

│

├── dataset\_awal.xlsx

│

├── destinations\_external.csv

│

├── destination\_popularities.csv

│

└── README.md

32\. Final Architecture Decision



Keputusan arsitektur yang dikunci:



&#x20;                   ┌───────────────────┐

&#x20;                   │   EXCEL DATASET   │

&#x20;                   └─────────┬─────────┘

&#x20;                             │

&#x20;                             ▼

&#x20;                   ┌───────────────────┐

&#x20;                   │ GOOGLE COLAB      │

&#x20;                   │ Python Crawler    │

&#x20;                   └─────────┬─────────┘

&#x20;                             │

&#x20;                             ▼

&#x20;                        CSV DATA

&#x20;                             │

&#x20;                             ▼

┌─────────────────────────────────────────────────────────┐

│                      LARAVEL                            │

│                                                         │

│  Blade + Tailwind + Alpine.js                           │

│                                                         │

│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐   │

│  │ K-Means     │  │ Collaborative│  │Recommendation│   │

│  │ Service     │  │ Filtering    │  │ Service      │   │

│  └─────────────┘  └──────────────┘  └──────────────┘   │

│                                                         │

└────────────────────────┬────────────────────────────────┘

&#x20;                        │

&#x20;                        ▼

&#x20;                   ┌──────────┐

&#x20;                   │  MySQL   │

&#x20;                   └────┬─────┘

&#x20;                        │

&#x20;             ┌──────────┴──────────┐

&#x20;             ▼                     ▼

&#x20;       Leaflet.js              Web UI

&#x20;             │

&#x20;             ▼

&#x20;      OpenStreetMap

Prinsip final



Python/Google Colab hanya untuk crawling dan menghasilkan dataset CSV. Laravel menjadi aplikasi utama yang menangani database, K-Means, Collaborative Filtering, rekomendasi, autentikasi, dan UI. Leaflet.js + OpenStreetMap digunakan untuk visualisasi lokasi wisata.

