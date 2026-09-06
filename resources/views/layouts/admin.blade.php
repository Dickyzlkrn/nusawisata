<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel') — NusaWisata</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js 4.x -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="admin-body">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>

    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <x-navigation.admin-sidebar />

        <!-- Main Content Area -->
        <div class="admin-main">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Buka Menu Navigasi">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="admin-page-title-wrap">
                        <h2 class="admin-page-title">@yield('page-title', 'Dashboard')</h2>
                        <span class="admin-topbar-badge">Research Admin</span>
                    </div>
                </div>
                <div class="admin-topbar-right">
                    <a href="{{ route('home') }}" target="_blank" class="admin-topbar-btn" title="Lihat Website NusaWisata">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span class="admin-topbar-btn-text">Lihat Web</span>
                    </a>
                    <div class="admin-user-info">
                        <div class="admin-avatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="admin-user-details">
                            <span class="admin-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                            <span class="admin-role-badge">Admin</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Body Content -->
            <div class="admin-content">
                <!-- Flash Messages Bridge (SweetAlert2) -->
                @if(session('success'))
                    <div id="flashSuccessMessage" data-message="{{ session('success') }}" style="display: none;"></div>
                @endif

                @if(session('error'))
                    <div id="flashErrorMessage" data-message="{{ session('error') }}" style="display: none;"></div>
                @endif

                @if($errors->any())
                    <div id="flashValidationErrors" data-errors="{{ json_encode($errors->all()) }}" style="display: none;"></div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
