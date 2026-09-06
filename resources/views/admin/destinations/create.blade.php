@extends('layouts.admin')

@section('title', 'Tambah Destinasi Wisata Baru')
@section('page-title', 'Tambah Destinasi')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Tambah Destinasi Wisata</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Masukkan informasi objek wisata nusantara baru ke dalam database.</p>
    </div>
    <a href="{{ route('admin.destinations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); text-decoration: none;">
        &larr; Kembali ke Daftar
    </a>
</div>

<div class="detail-card" style="max-width: 800px;">
    <form action="{{ route('admin.destinations.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name" class="form-label">Nama Destinasi *</label>
            <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name') }}" placeholder="Contoh: Pantai Kelingking" required>
            @error('name')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="province_id" class="form-label">Provinsi *</label>
                <select name="province_id" id="province_id" class="form-select @error('province_id') error @enderror" required>
                    <option value="">Pilih Provinsi...</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p->id }}" {{ old('province_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                @error('province_id')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="category" class="form-label">Kategori Wisata</label>
                <input type="text" name="category" id="category" class="form-input" value="{{ old('category') }}" placeholder="Contoh: Alam, Budaya, Bahari, Sejarah">
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Lengkap</label>
            <textarea name="description" id="description" class="form-textarea" placeholder="Tuliskan gambaran daya tarik, keindahan, dan informasi tempat...">{{ old('description') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="price" class="form-label">Harga Tiket Masuk (Rp)</label>
                <input type="number" name="price" id="price" class="form-input" value="{{ old('price', 0) }}" min="0" step="1000">
            </div>

            <div class="form-group">
                <label for="latitude" class="form-label">Latitude (GPS)</label>
                <input type="number" name="latitude" id="latitude" class="form-input" value="{{ old('latitude') }}" step="0.0000001" placeholder="-8.750849">
            </div>

            <div class="form-group">
                <label for="longitude" class="form-label">Longitude (GPS)</label>
                <input type="number" name="longitude" id="longitude" class="form-input" value="{{ old('longitude') }}" step="0.0000001" placeholder="115.474678">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="google_rating" class="form-label">Rating Awal (0 - 5)</label>
                <input type="number" name="google_rating" id="google_rating" class="form-input" value="{{ old('google_rating', 4.5) }}" min="0" max="5" step="0.1">
            </div>

            <div class="form-group">
                <label for="review_count" class="form-label">Jumlah Review</label>
                <input type="number" name="review_count" id="review_count" class="form-input" value="{{ old('review_count', 100) }}" min="0">
            </div>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">URL Foto / Gambar</label>
            <input type="url" name="image" id="image" class="form-input" value="{{ old('image') }}" placeholder="https://images.unsplash.com/...">
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); padding: 12px 28px;">
                <i class="fa-solid fa-plus"></i> Simpan Destinasi
            </button>
            <a href="{{ route('admin.destinations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 12px 24px; text-decoration: none;">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
