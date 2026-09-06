<nav class="navbar" id="mainNavbar">
    <div class="container">
        <div class="nav-inner">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="nav-logo" style="display: flex; align-items: center; text-decoration: none;">
                <img src="{{ asset('images/logo.png') }}" alt="NusaWisata" style="height: 58px; width: auto; object-fit: contain; display: block;">
            </a>

            <!-- Desktop Menu -->
            <ul class="nav-links">
                <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a></li>
                <li><a href="{{ route('destinations.index') }}" class="{{ request()->routeIs('destinations.*') ? 'active' : '' }}">Destinasi</a></li>
                <li><a href="{{ route('provinces.index') }}" class="{{ request()->routeIs('provinces.*') ? 'active' : '' }}">Provinsi</a></li>
                <li><a href="{{ route('recommendations.index') }}" class="{{ request()->routeIs('recommendations.*') ? 'active' : '' }}">Rekomendasi</a></li>
                <li><a href="{{ route('documentation') }}" class="{{ request()->routeIs('documentation') ? 'active' : '' }}">Dokumentasi</a></li>
            </ul>

            <!-- Actions (Auth / User) -->
            <div class="nav-actions">
                @auth
                    <div class="nav-user-dropdown" id="userDropdown">
                        <button type="button" class="nav-user-btn" id="userDropdownToggle" aria-haspopup="true">
                            <div class="nav-user-avatar">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span>{{ auth()->user()->name }}</span>
                            <i class="fa-solid fa-chevron-down" style="font-size: 11px;"></i>
                        </button>
                        <div class="nav-dropdown-menu" id="userDropdownMenu">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}">
                                    <i class="fa-solid fa-gauge-high"></i> Admin Panel
                                </a>
                                <div class="nav-dropdown-divider"></div>
                            @endif
                            <a href="{{ route('dashboard') }}">
                                <i class="fa-solid fa-columns"></i> Dashboard
                            </a>
                            <a href="{{ route('profile.index') }}">
                                <i class="fa-solid fa-user-gear"></i> Profil Saya
                            </a>
                            <div class="nav-dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="button" class="logout-trigger" style="color: var(--color-error); background: none; border: none; cursor: pointer; width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-size: 14px; font-weight: 500; border-radius: var(--radius-sm); text-align: left;">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-auth-link">Masuk</a>
                    <a href="{{ route('register') }}" class="nav-auth-btn">Daftar</a>
                @endauth

                <!-- Mobile Toggle -->
                <button type="button" class="nav-mobile-toggle" id="navMobileToggle" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Overlay -->
<div class="nav-mobile-menu" id="navMobileMenu">
    <div class="nav-mobile-header">
        <a href="{{ route('home') }}" class="nav-logo" style="display: flex; align-items: center; text-decoration: none;">
            <img src="{{ asset('images/logo.png') }}" alt="NusaWisata" style="height: 48px; width: auto; object-fit: contain; display: block;">
        </a>
        <button type="button" class="nav-mobile-toggle" id="navMobileClose" aria-label="Close Menu">
            <i class="fa-solid fa-xmark" style="font-size: 20px;"></i>
        </button>
    </div>

    <div class="nav-mobile-links">
        <a href="{{ route('home') }}">Beranda</a>
        <a href="{{ route('destinations.index') }}">Destinasi Wisata</a>
        <a href="{{ route('provinces.index') }}">38 Provinsi</a>
        <a href="{{ route('recommendations.index') }}">Sistem Rekomendasi</a>
        <a href="{{ route('documentation') }}">Dokumentasi Sistem</a>

        @auth
            <a href="{{ route('dashboard') }}">Dashboard Saya</a>
            <a href="{{ route('profile.index') }}">Profil</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" style="color: var(--color-accent);"><i class="fa-solid fa-shield-halved"></i> Admin Panel</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" style="margin-top: 16px;">
                @csrf
                <button type="button" class="btn-primary logout-trigger" style="width: 100%; justify-content: center; background: #ef4444; border-color: #ef4444;">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="margin-right: 8px;"></i> Keluar
                </button>
            </form>
        @else
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
                <a href="{{ route('login') }}" class="btn-outline" style="text-align: center; border-radius: var(--radius-pill);">Masuk</a>
                <a href="{{ route('register') }}" class="btn-primary" style="text-align: center; border-radius: var(--radius-pill);">Daftar Akun</a>
            </div>
        @endauth
    </div>
</div>
