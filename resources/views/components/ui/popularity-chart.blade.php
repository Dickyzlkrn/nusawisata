@props(['popularities' => null])

@php
    $defaultDays = [
        'Senin' => 45,
        'Selasa' => 40,
        'Rabu' => 50,
        'Kamis' => 55,
        'Jumat' => 75,
        'Sabtu' => 95,
        'Minggu' => 90,
    ];

    $chartData = [];
    if ($popularities && count($popularities) > 0) {
        foreach ($popularities as $item) {
            $chartData[$item->day_of_week] = $item->popularity_score;
        }
    } else {
        $chartData = $defaultDays;
    }

    $peakDay = array_search(max($chartData), $chartData);
@endphp

<div class="popularity-chart">
    @foreach($chartData as $day => $score)
        @php
            $fillClass = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : '');
        @endphp
        <div class="popularity-bar-row">
            <span class="popularity-day">{{ $day }}</span>
            <div class="popularity-bar-track">
                <div class="popularity-bar-fill {{ $fillClass }}" style="width: {{ $score }}%;"></div>
            </div>
            <span style="font-size: 12px; font-weight: 600; color: var(--color-text-muted); width: 35px; text-align: right;">
                {{ $score }}%
            </span>
        </div>
    @endforeach

    @if($peakDay)
        <div class="popularity-peak">
            <i class="fa-solid fa-fire" style="color: #ef4444; margin-right: 6px;"></i>
            Waktu teramai biasanya terjadi pada hari <strong>{{ $peakDay }}</strong>.
        </div>
    @endif
</div>
