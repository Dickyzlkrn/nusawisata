@props(['province'])

@php
    $imageUrl = $province->image ?: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80';
    $destCount = $province->destinations_count ?? $province->destinations()->count();
@endphp

<a href="{{ route('provinces.show', $province) }}" class="province-card" style="text-decoration: none; display: block;">
    <img src="{{ $imageUrl }}" alt="{{ $province->name }}" loading="lazy">
    <div class="province-card-overlay">
        <h4>{{ $province->name }}</h4>
        <span>{{ $destCount }} Destinasi Wisata</span>
    </div>
</a>
