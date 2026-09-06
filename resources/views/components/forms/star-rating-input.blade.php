@props(['name' => 'rating', 'value' => 5])

<div class="star-rating-input">
    @for($i = 5; $i >= 1; $i--)
        <input type="radio" id="star{{ $i }}" name="{{ $name }}" value="{{ $i }}" {{ old($name, $value) == $i ? 'checked' : '' }} required>
        <label for="star{{ $i }}" title="{{ $i }} Bintang">★</label>
    @endfor
</div>
