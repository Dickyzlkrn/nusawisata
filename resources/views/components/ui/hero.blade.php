@props([
    'title' => 'Temukan Keindahan Nusantara Indonesia',
    'subtitle' => 'Jelajahi ribuan destinasi menakjubkan di 38 provinsi dengan rekomendasi personalisasi berbasis Machine Learning.',
    'buttonText' => 'Mulai Jelajah',
    'buttonUrl' => '#explore'
])

<section class="hero-section">
    <div class="container">
        <div class="hero-card">
            <div class="hero-content">
                <h1 class="hero-title">{{ $title }}</h1>
                <div class="hero-right">
                    <p class="hero-subtitle">{{ $subtitle }}</p>
                    <a href="{{ $buttonUrl }}" class="btn-white">
                        <span>{{ $buttonText }}</span>
                        <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
