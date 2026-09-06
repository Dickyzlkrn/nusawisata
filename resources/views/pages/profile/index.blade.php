@extends('layouts.app')

@section('title', 'Pengaturan Profil — NusaWisata')

@section('content')
<section class="dashboard-section">
    <div class="container" style="max-width: 700px;">
        <div class="dashboard-greeting" style="margin-bottom: 32px;">
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">
                Pengaturan Akun & Profil
            </h1>
            <p style="color: var(--color-text-muted); font-size: 14px;">
                Perbarui informasi identitas profil dan kata sandi Anda.
            </p>
        </div>

        <div class="detail-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Klaster Pengguna (K-Means AI)</label>
                    <input type="text" class="form-input" value="Klaster {{ $user->cluster_id ?? 1 }} (Ditentukan otomatis oleh model K-Means)" disabled style="background: var(--color-bg-hover); cursor: not-allowed;">
                </div>

                <div style="margin: 32px 0 20px 0; border-top: 1px solid var(--color-border); padding-top: 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 4px;">Ganti Password</h3>
                    <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">Biarkan kosong jika tidak ingin mengubah password.</p>
                </div>

                <div class="form-group">
                    <label for="current_password" class="form-label">Password Saat Ini</label>
                    <input type="password" name="current_password" id="current_password" class="form-input @error('current_password') error @enderror" placeholder="••••••••">
                    @error('current_password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" name="password" id="password" class="form-input @error('password') error @enderror" placeholder="Minimal 8 karakter">
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" placeholder="Ulangi password baru">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 28px;">
                    <button type="submit" class="btn-primary" style="border-radius: var(--radius-pill); padding: 12px 28px;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn-outline" style="border-radius: var(--radius-pill); padding: 12px 24px; text-decoration: none;">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
