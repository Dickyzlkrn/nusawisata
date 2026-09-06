@props(['title', 'subtitle' => null, 'actionText' => null, 'actionUrl' => null])

<div class="destinations-header" style="align-items: flex-end; margin-bottom: 32px;">
    <div>
        <h2 class="section-title">{{ $title }}</h2>
        @if($subtitle)
            <p class="section-desc">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn-outline" style="border-radius: var(--radius-pill); font-size: 13.5px;">
            <span>{{ $actionText }}</span>
            <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
        </a>
    @endif
</div>
