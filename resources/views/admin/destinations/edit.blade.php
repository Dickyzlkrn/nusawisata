@extends('layouts.admin')

@section('title', 'Edit Destinasi: ' . $destination->name)
@section('page-title', 'Edit Destinasi')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Edit Destinasi: {{ $destination->name }}</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Perbarui data objek wisata dan informasi tiket.</p>
    </div>
    <a href="{{ route('admin.destinations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); text-decoration: none;">
        &larr; Kembali ke Daftar
    </a>
</div>

<div class="detail-card" style="max-width: 800px;">
    <form action="{{ route('admin.destinations.update', $destination) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name" class="form-label">Nama Destinasi *</label>
            <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name', $destination->name) }}" required>
            @error('name')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="province_id" class="form-label">Provinsi *</label>
                <select name="province_id" id="province_id" class="form-select @error('province_id') error @enderror" required>
                    @foreach($provinces as $p)
                        <option value="{{ $p->id }}" {{ old('province_id', $destination->province_id) == $p->id ? 'selected' : '' }}>
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
                <input type="text" name="category" id="category" class="form-input" value="{{ old('category', $destination->category) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Lengkap</label>
            <textarea name="description" id="description" class="form-textarea">{{ old('description', $destination->description) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="price" class="form-label">Harga Tiket Masuk (Rp)</label>
                <input type="number" name="price" id="price" class="form-input" value="{{ old('price', (int) $destination->price) }}" min="0" step="1000">
            </div>

            <div class="form-group">
                <label for="latitude" class="form-label">Latitude (GPS)</label>
                <input type="number" name="latitude" id="latitude" class="form-input" value="{{ old('latitude', $destination->latitude) }}" step="0.0000001">
            </div>

            <div class="form-group">
                <label for="longitude" class="form-label">Longitude (GPS)</label>
                <input type="number" name="longitude" id="longitude" class="form-input" value="{{ old('longitude', $destination->longitude) }}" step="0.0000001">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="google_rating" class="form-label">Rating (0 - 5)</label>
                <input type="number" name="google_rating" id="google_rating" class="form-input" value="{{ old('google_rating', $destination->google_rating) }}" min="0" max="5" step="0.1">
            </div>

            <div class="form-group">
                <label for="review_count" class="form-label">Jumlah Review</label>
                <input type="number" name="review_count" id="review_count" class="form-input" value="{{ old('review_count', $destination->review_count) }}" min="0">
            </div>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">URL Foto / Gambar</label>
            <input type="url" name="image" id="image" class="form-input" value="{{ old('image', $destination->image) }}">
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); padding: 12px 28px;">
                <i class="fa-solid fa-floppy-disk"></i> Perbarui Destinasi
            </button>
            <a href="{{ route('admin.destinations.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 12px 24px; text-decoration: none;">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
