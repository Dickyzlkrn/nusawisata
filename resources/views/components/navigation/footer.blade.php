<footer class="footer-wrapper">
    <div class="container">
        <!-- Call to Action Banner (Optional, can be toggled) -->
        @if(!isset($hideCta) || !$hideCta)
            <div class="cta-center">
                <h2 class="cta-title">Siap Menemukan Destinasi Impian Anda?</h2>
                <p class="cta-subtext">Dapatkan rekomendasi wisata Indonesia terbaik yang dipersonalisasi sesuai selera Anda dengan kecerdasan Collaborative Filtering & K-Means Clustering.</p>
                <a href="{{ route('recommendations.index') }}" class="cta-btn">
                    <span>Mulai Rekomendasi</span>
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            </div>
            <div class="footer-divider"></div>
        @endif

        <!-- Footer Grid -->
        <div class="footer-grid">
            <!-- Brand & Bio -->
            <div class="footer-col">
                <div class="footer-logo">
                    <a href="{{ route('home') }}" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                        <img src="{{ asset('images/logo.png') }}" alt="NusaWisata" style="height: 36px; width: 36px; object-fit: cover; border-radius: 8px; background: #fff; padding: 1px;">
                        <span style="font-weight: 800; font-size: 20px; color: #fff;">NusaWisata</span>
                    </a>
                </div>
                <p class="footer-bio">
                    Sistem Rekomendasi Wisata Indonesia Menggunakan Collaborative Filtering dan K-Means Clustering. Membantu wisatawan menemukan pesona nusantara dari 38 provinsi.
                </p>
                <div class="footer-contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Indonesia (38 Provinsi)</span>
                </div>
                <div class="footer-contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <span>kontak@nusawisata.id</span>
                </div>
            </div>

            <!-- Jelajahi -->
            <div class="footer-col">
                <h5>Jelajahi</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">Beranda</a></li>
                    <li><a href="{{ route('destinations.index') }}">Semua Destinasi</a></li>
                    <li><a href="{{ route('provinces.index') }}">38 Provinsi</a></li>
                    <li><a href="{{ route('recommendations.index') }}">Sistem Rekomendasi</a></li>
                    <li><a href="{{ route('documentation') }}">Dokumentasi & Metodologi</a></li>
                </ul>
            </div>

            <!-- Akun & Layanan -->
            <div class="footer-col">
                <h5>Layanan</h5>
                <ul class="footer-links">
                    @auth
                        <li><a href="{{ route('dashboard') }}">Dashboard Saya</a></li>
                        <li><a href="{{ route('profile.index') }}">Pengaturan Profil</a></li>
                        @if(auth()->user()->isAdmin())
                            <li><a href="{{ route('admin.dashboard') }}">Admin Panel</a></li>
                        @endif
                    @else
                        <li><a href="{{ route('login') }}">Masuk Akun</a></li>
                        <li><a href="{{ route('register') }}">Daftar Pengguna</a></li>
                    @endauth
                    <li><a href="{{ route('destinations.index', ['category' => 'Alam']) }}">Wisata Alam</a></li>
                    <li><a href="{{ route('destinations.index', ['category' => 'Budaya']) }}">Wisata Budaya & Sejarah</a></li>
                </ul>
            </div>

            <!-- Penelitian & Sosial -->
            <div class="footer-col">
                <h5>Metodologi Riset</h5>
                <p class="footer-bio" style="margin-bottom: 12px;">
                    Integrasi <strong>User-Based Collaborative Filtering</strong> untuk preferensi ulasan dan <strong>K-Means Clustering</strong> untuk segmentasi profil wisatawan nusantara.
                </p>
                <div style="margin-bottom: 16px;">
                    <a href="{{ route('documentation') }}" style="color: var(--color-accent); font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <span>Pelajari Alur Algoritma</span>
                        <i class="fa-solid fa-arrow-right" style="font-size: 11px;"></i>
                    </a>
                </div>
                <div class="social-icons">
                    <a href="#" class="social-icon-btn" aria-label="Github"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div>
                &copy; {{ date('Y') }} <strong>NusaWisata</strong>. Seluruh hak cipta dilindungi undang-undang.
            </div>
            <div class="footer-bottom-links">
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Syarat & Ketentuan</a>
                <a href="{{ route('documentation') }}">Dokumentasi Riset</a>
            </div>
        </div>
    </div>
</footer>
