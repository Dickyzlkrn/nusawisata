@props(['rating' => 0, 'showNumber' => true, 'reviewsCount' => null])

@php
    $rating = (float) $rating;
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.3 && ($rating - $fullStars) <= 0.7;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
@endphp

<div class="rating-stars" style="display: inline-flex; align-items: center; gap: 4px; color: #f59e0b;">
    @for($i = 0; $i < $fullStars; $i++)
        <i class="fa-solid fa-star" style="font-size: 13px;"></i>
    @endfor

    @if($hasHalfStar)
        <i class="fa-solid fa-star-half-stroke" style="font-size: 13px;"></i>
    @endif

    @for($i = 0; $i < $emptyStars; $i++)
        <i class="fa-regular fa-star" style="font-size: 13px; color: #cbd5e1;"></i>
    @endfor

    @if($showNumber)
        <span style="font-size: 13px; font-weight: 700; color: var(--color-primary-dark); margin-left: 4px;">
            {{ number_format($rating, 1) }}
        </span>
    @endif

    @if($reviewsCount !== null)
        <span style="font-size: 12px; color: var(--color-text-muted); margin-left: 2px;">
            ({{ number_format($reviewsCount) }} ulasan)
        </span>
    @endif
</div>
