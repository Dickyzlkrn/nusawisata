<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'NusaWisata — Jelajahi Pesona Wisata Indonesia')</title>
    <meta name="description" content="@yield('meta_description', 'Platform rekomendasi wisata Indonesia cerdas berbasis Collaborative Filtering dan K-Means Clustering.')">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <x-navigation.navbar />

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

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <x-navigation.footer />

    @stack('scripts')
</body>
</html>
