@extends('layouts.guest')

@section('title', 'Masuk ke Akun — NusaWisata')

@section('content')
<div class="auth-card-header">
    <h1 class="auth-card-title">Selamat Datang Kembali</h1>
    <p class="auth-card-subtitle">Masuk untuk melihat rekomendasi personal dan mengelola ulasan wisata Anda.</p>
</div>

<form action="{{ route('login') }}" method="POST" class="auth-form">
    @csrf

    <div class="form-group">
        <label for="email" class="form-label">Alamat Email</label>
        <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="email">
        @error('email')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-input @error('password') error @enderror" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="password-toggle-btn" data-target="password" aria-label="Lihat password">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>
        @error('password')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-check">
        <input type="checkbox" name="remember" id="remember" value="1">
        <label for="remember">Ingat saya di perangkat ini</label>
    </div>

    <button type="submit" class="btn-auth-submit" data-loading-text="Masuk...">
        <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right: 8px;"></i> Masuk
    </button>
</form>

<div class="auth-switch">
    Belum memiliki akun? <a href="{{ route('register') }}">Daftar Sekarang</a>
</div>

<div class="auth-demo-badge">
    <div class="demo-header">
        <i class="fa-solid fa-circle-info" style="color: #0284c7;"></i>
        <span>Demo Akun Pengujian</span>
    </div>
    <div class="demo-grid">
        <div class="demo-item">
            <span class="demo-role">Admin:</span>
            <code>admin@nusawisata.id</code>
            <span class="demo-pass">/ password</span>
        </div>
        <div class="demo-item">
            <span class="demo-role">User:</span>
            <code>budi@example.com</code>
            <span class="demo-pass">/ password</span>
        </div>
    </div>
</div>
@endsection
