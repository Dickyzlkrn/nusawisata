@props(['label', 'value', 'icon' => 'fa-solid fa-chart-simple', 'trend' => null])

<div class="stat-card">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="stat-card-icon">
            <i class="{{ $icon }}" style="font-size: 20px;"></i>
        </div>
        @if($trend)
            <span class="badge badge-success" style="font-size: 11px;">
                <i class="fa-solid fa-arrow-trend-up"></i> {{ $trend }}
            </span>
        @endif
    </div>
    <div class="stat-card-value">{{ $value }}</div>
    <div class="stat-card-label">{{ $label }}</div>
</div>
