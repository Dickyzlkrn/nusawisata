@props(['placeholder' => 'Cari nama destinasi atau lokasi...', 'action' => null, 'value' => ''])

<form action="{{ $action ?? route('destinations.index') }}" method="GET" class="search-box">
    <span class="search-box-icon">
        <i class="fa-solid fa-magnifying-glass"></i>
    </span>
    <input type="text" name="search" class="form-input" placeholder="{{ $placeholder }}" value="{{ $value ?: request('search') }}">
    @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
    @endif
    @if(request('province'))
        <input type="hidden" name="province" value="{{ request('province') }}">
    @endif
</form>
