<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-header">
        <a href="{{ route('admin.dashboard') }}" style="text-decoration: none;">
            <div class="admin-sidebar-logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="{{ asset('images/logo.png') }}" alt="NusaWisata" style="height: 36px; width: 36px; object-fit: cover; border-radius: 8px; background: #fff; padding: 1px; flex-shrink: 0;">
                <div>
                    <div style="font-weight: 800; font-size: 18px; color: #fff; line-height: 1.2;">NusaWisata</div>
                    <small style="color: var(--color-white-50); font-size: 11px;">Admin Panel & Riset</small>
                </div>
            </div>
        </a>
        <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="admin-sidebar-nav">
        <!-- Section: Ringkasan -->
        <div class="admin-nav-section">
            <div class="admin-nav-label">Menu Utama</div>
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Section: Data Master -->
        <div class="admin-nav-section">
            <div class="admin-nav-label">Data Wisata</div>
            <a href="{{ route('admin.destinations.index') }}" class="admin-nav-link {{ request()->routeIs('admin.destinations.*') ? 'active' : '' }}">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Destinasi Wisata</span>
            </a>
            <a href="{{ route('admin.provinces.index') }}" class="admin-nav-link {{ request()->routeIs('admin.provinces.*') ? 'active' : '' }}">
                <i class="fa-solid fa-archway"></i>
                <span>38 Provinsi</span>
            </a>
            <a href="{{ route('admin.ratings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
                <i class="fa-solid fa-star"></i>
                <span>Rating & Ulasan</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Pengguna</span>
            </a>
        </div>

        <!-- Section: Riset & Algoritma -->
        <div class="admin-nav-section">
            <div class="admin-nav-label">Sistem Rekomendasi</div>
            <a href="{{ route('admin.ml_runs.index') }}" class="admin-nav-link {{ request()->routeIs('admin.ml_runs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-robot"></i>
                <span>Automated ML Run</span>
            </a>
            <a href="{{ route('admin.clustering.index') }}" class="admin-nav-link {{ request()->routeIs('admin.clustering.*') ? 'active' : '' }}">
                <i class="fa-solid fa-diagram-project"></i>
                <span>K-Means Clustering</span>
            </a>
            <a href="{{ route('admin.collaborative_filtering.index') }}" class="admin-nav-link {{ request()->routeIs('admin.collaborative_filtering.*') ? 'active' : '' }}">
                <i class="fa-solid fa-network-wired"></i>
                <span>Collaborative Filtering</span>
            </a>
            <a href="{{ route('admin.dataset.index') }}" class="admin-nav-link {{ request()->routeIs('admin.dataset.*') ? 'active' : '' }}">
                <i class="fa-solid fa-database"></i>
                <span>Dataset & Crawler</span>
            </a>
            <a href="{{ route('admin.documentation.index') }}" class="admin-nav-link {{ request()->routeIs('admin.documentation.*') ? 'active' : '' }}">
                <i class="fa-solid fa-book-open"></i>
                <span>Panduan & Riset</span>
            </a>
        </div>
    </nav>

    <div style="padding: 16px 20px; border-top: 1px solid var(--color-white-08); display: flex; flex-direction: column; gap: 10px;">
        <a href="{{ route('home') }}" class="admin-nav-link" style="padding: 8px 0; color: var(--color-white-65);">
            <i class="fa-solid fa-house"></i>
            <span>Kembali ke Website</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="admin-nav-link logout-trigger" style="background: none; border: none; width: 100%; cursor: pointer; color: #f87171; padding: 8px 0;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
