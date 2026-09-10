# 🏝️ NusaWisata — Streamlit Prototype Edition

Prototype interaktif sistem rekomendasi pariwisata Indonesia berbasis **K-Means Clustering** (segmentasi pengguna) dan **User-Based Collaborative Filtering (UBCF)** dengan **Cosine Similarity**, dibuat sebagai pembanding fungsional 1:1 terhadap sistem produksi berbasis Laravel.

Folder ini dirancang mandiri (*self-contained*), modular, dan **siap dideploy langsung ke Streamlit Community Cloud, Hugging Face Spaces, atau server hosting Python lainnya.**

---

## 📁 Arsitektur & Struktur Folder

```text
nusawisata-streamlit/
├── .streamlit/
│   └── config.toml             # Konfigurasi Dark Theme & Server Port
├── components/
│   └── maps.py                 # Peta interaktif Leaflet / OpenStreetMap (Folium)
├── data/
│   ├── destinations.csv        # 195 destinasi (koordinat, harga, kategori, provinsi)
│   ├── ratings.csv             # 4.033+ data rating interaksi wisatawan
│   └── users.csv               # 397 profil wisatawan
├── services/
│   ├── kmeans_service.py       # Ekstraksi 12 fitur perilaku & segmentasi K-Means
│   └── cf_service.py           # Cosine Similarity, UBCF Ranking & Evaluasi (MAE/RMSE)
├── .gitignore                  # Mengabaikan cache & virtual environment
├── app.py                      # Aplikasi multi-halaman Streamlit (Full Pipeline)
├── README.md                   # Dokumentasi lengkap & panduan deployment
└── requirements.txt            # Dependensi Python lengkap
```

---

## 🌟 Fitur Utama & Alur Fungsional

1. **🏠 Beranda & Statistik Dataset:**
   - Ringkasan metrik total user, destinasi, rating, serta tingkat *sparsity matrix* (~94.8%).
   - Visualisasi distribusi rating dan sebaran kategori destinasi (Plotly).
   - Eksplorasi data mentah interaktif (Destinasi, Rating, User).

2. **📍 Eksplorasi & Filter Destinasi:**
   - Pencarian berbasis teks / kata kunci nama destinasi.
   - Filter multi-kriteria: Kategori, Provinsi, Rentang Harga Tiket (slider), dan Rating Minimum.
   - Pilihan pengurutan (*sorting*): Rating Tertinggi, Harga Termurah/Termahal, Nama (A-Z).
   - **Peta Interaktif Leaflet / OpenStreetMap** menampilkan sebaran destinasi hasil filter dengan sistem klaster marker dan popup detail.

3. **ℹ️ Detail Destinasi & Input Rating Pengguna:**
   - Informasi lengkap destinasi (kategori, provinsi, harga tiket, koordinat geografis, deskripsi naratif).
   - Mini Map Leaflet fokus pada titik koordinat destinasi.
   - Statistik ulasan dan rata-rata rating wisatawan lain.
   - **Form Input Rating Interaktif**: User aktif dapat memberikan rating 1-5 bintang yang **langsung tersimpan ke session state**, memperbarui profil klaster, dan mempengaruhi rekomendasi personal secara real-time!

4. **⭐ Rekomendasi Personal (K-Means + UBCF):**
   - Mengambil profil dan riwayat rating pengguna aktif.
   - **Hanya merekomendasikan destinasi yang BELUM pernah dinilai oleh pengguna.**
   - **Cold-Start Fallback**: Jika pengguna memiliki < 3 rating, sistem mendeteksi kondisi *cold-start* dan otomatis menyajikan destinasi rekomendasi terpopuler / rating tertinggi secara fallback.
   - **Konfigurasi Fleksibel**: Pilihan pembatasan ke peer dalam klaster yang sama (*within-cluster*), jumlah tetangga ($k$-NN), jumlah rekomendasi ($N$), serta filter kategori wisata.
   - **Peta Rekomendasi Leaflet**: Menandai lokasi destinasi rekomendasi teratas lengkap dengan pin peringkat (#1, #2, dst.) dan skor prediksi rating.
   - **Transparansi Algoritma**: Tab transparan yang menampilkan status klaster pengguna, tabel daftar tetangga terdekat (*nearest neighbors*) beserta nilai *Cosine Similarity*, dan formula matematis UBCF.

5. **👤 Simulasi Pengguna (User Switcher & Profil):**
   - Dropdown untuk berganti ke pengguna mana pun dari 397 pengguna yang ada.
   - Opsi membuat **Pengguna Baru (Simulasi Cold-Start)** tanpa riwayat rating.
   - Riwayat lengkap seluruh destinasi yang pernah dinilai oleh pengguna aktif saat ini.
   - Tombol reset dataset kembali ke kondisi awal CSV.

6. **📈 Evaluasi Model & Validasi Ilmiah:**
   - **Validasi K-Means**: Silhouette Score, Davies-Bouldin Index (DBI), Calinski-Harabasz Index (CHI), Inertia, serta visualisasi Proyeksi 2D PCA.
   - **Evaluasi UBCF**: Pengujian train/test split untuk menghitung MAE (*Mean Absolute Error*), RMSE (*Root Mean Squared Error*), Precision@5, Recall@5, dan grafik histogram sebaran galat prediksi (*absolute error*).
   - Dilengkapi interpretasi akademis untuk kebutuhan penulisan karya ilmiah / jurnal / skripsi.

---

## 💻 Cara Menjalankan Secara Lokal

### 1. Masuk ke folder streamlit
```bash
cd nusawisata-streamlit
```

### 2. Buat & aktifkan virtual environment (Opsional)
```bash
python -m venv .venv
# Di Windows:
.venv\Scripts\activate
# Di Linux/macOS:
source .venv/bin/activate
```

### 3. Install dependensi
```bash
pip install -r requirements.txt
```

### 4. Jalankan aplikasi Streamlit
```bash
streamlit run app.py
```
Buka browser di alamat: `http://localhost:8501`.

---

## ☁️ Panduan Deploy ke Streamlit Community Cloud (Gratis)

1. Push folder `nusawisata-streamlit/` ke repositori GitHub Anda (baik sebagai subfolder atau repositori mandiri).
2. Kunjungi **[share.streamlit.io](https://share.streamlit.io)** dan login dengan akun GitHub Anda.
3. Klik tombol **"New app"**.
4. Isi parameter:
   - **Repository**: Repositori GitHub Anda.
   - **Branch**: `main` atau `master`.
   - **Main file path**: `nusawisata-streamlit/app.py` (jika subfolder) atau `app.py` (jika repo terpisah).
5. Klik **"Deploy!"**. Aplikasi Anda akan aktif dan memperoleh URL publik instan!
