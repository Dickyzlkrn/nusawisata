@extends('layouts.admin')

@section('title', 'Edit Provinsi: ' . $province->name)
@section('page-title', 'Edit Provinsi')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Edit Provinsi: {{ $province->name }}</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Perbarui data provinsi dan deskripsi pariwisata daerah.</p>
    </div>
    <a href="{{ route('admin.provinces.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); text-decoration: none;">
        &larr; Kembali ke Daftar
    </a>
</div>

<div class="detail-card" style="max-width: 700px;">
    <form action="{{ route('admin.provinces.update', $province) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name" class="form-label">Nama Provinsi *</label>
            <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name', $province->name) }}" required>
            @error('name')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Pariwisata Provinsi</label>
            <textarea name="description" id="description" class="form-textarea">{{ old('description', $province->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">URL Foto / Gambar Landskap</label>
            <input type="url" name="image" id="image" class="form-input" value="{{ old('image', $province->image) }}">
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); padding: 12px 28px;">
                <i class="fa-solid fa-floppy-disk"></i> Perbarui Provinsi
            </button>
            <a href="{{ route('admin.provinces.index') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 12px 24px; text-decoration: none;">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
