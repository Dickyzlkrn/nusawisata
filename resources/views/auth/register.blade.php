@extends('layouts.guest')

@section('title', 'Daftar Akun Baru — NusaWisata')

@section('content')
<div class="auth-card-header">
    <h1 class="auth-card-title">Daftar Akun NusaWisata</h1>
    <p class="auth-card-subtitle">Mulai jelajahi pesona Indonesia dan nikmati rekomendasi wisata cerdas.</p>
</div>

<form action="{{ route('register') }}" method="POST" class="auth-form">
    @csrf

    <div class="form-group">
        <label for="name" class="form-label">Nama Lengkap</label>
        <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name') }}" placeholder="Contoh: Budi Pratama" required autofocus autocomplete="name">
        @error('name')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Alamat Email</label>
        <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email">
        @error('email')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-input @error('password') error @enderror" placeholder="Minimal 8 karakter" required autocomplete="new-password">
            <button type="button" class="password-toggle-btn" data-target="password" aria-label="Lihat password">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>
        @error('password')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <div class="password-wrapper">
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" placeholder="Ulangi password Anda" required autocomplete="new-password">
            <button type="button" class="password-toggle-btn" data-target="password_confirmation" aria-label="Lihat konfirmasi password">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn-auth-submit" data-loading-text="Mendaftarkan..." style="margin-top: 8px;">
        <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Buat Akun
    </button>
</form>

<div class="auth-switch">
    Sudah memiliki akun? <a href="{{ route('login') }}">Masuk di Sini</a>
</div>
@endsection
