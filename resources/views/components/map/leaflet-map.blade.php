@props([
    'elementId' => 'nusaMap',
    'lat' => -2.548926,
    'lng' => 118.0148634,
    'zoom' => 5,
    'height' => '400px',
    'title' => null,
    'description' => null,
    'markers' => []
])

<div id="{{ $elementId }}" class="map-container" style="height: {{ $height }}; border-radius: var(--radius-xl); z-index: 10;"></div>

@push('styles')
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @endonce
@endpush

@push('scripts')
    @once
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var lat = {{ (float) $lat }};
            var lng = {{ (float) $lng }};
            var zoom = {{ (int) $zoom }};
            var elementId = "{{ $elementId }}";

            if (typeof L !== 'undefined' && window.NusaMap) {
                var map = NusaMap.init(elementId, lat, lng, zoom);

                @if($title)
                    NusaMap.addMarker(map, lat, lng, {!! json_encode($title) !!}, {!! json_encode($description ?? '') !!});
                @endif

                @if(!empty($markers))
                    var markersData = {!! json_encode($markers) !!};
                    NusaMap.addMarkers(map, markersData);
                @endif
            }
        });
    </script>
@endpush
