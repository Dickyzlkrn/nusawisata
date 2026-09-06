@props(['destination', 'size' => 'normal'])

@php
    $cardClass = match($size) {
        'top' => 'dest-card-top',
        'bottom' => 'dest-card-bottom',
        default => '',
    };
    $imageUrl = $destination->image ?: 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80';
@endphp

<a href="{{ route('destinations.show', $destination) }}" class="dest-card {{ $cardClass }}" style="display: block; text-decoration: none; position: relative;">
    <img src="{{ $imageUrl }}" alt="{{ $destination->name }}" loading="lazy">
    <div class="dest-card-overlay">
        @if($destination->category)
            <span class="badge badge-primary" style="margin-bottom: 8px; width: fit-content; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                {{ $destination->category }}
            </span>
        @endif
        <h4>{{ $destination->name }}</h4>
        <p style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
            <span style="display: flex; align-items: center; gap: 4px;">
                <i class="fa-solid fa-location-dot" style="font-size: 11px;"></i>
                {{ $destination->province->name ?? 'Indonesia' }}
            </span>
            <span style="display: flex; align-items: center; gap: 4px; font-weight: 700; color: #fbbf24;">
                <i class="fa-solid fa-star" style="font-size: 11px;"></i>
                {{ number_format($destination->google_rating ?? 4.5, 1) }}
            </span>
        </p>
    </div>
</a>
