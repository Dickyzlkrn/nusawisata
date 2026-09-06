@props(['type' => 'primary'])

<span {{ $attributes->merge(['class' => 'badge badge-' . $type]) }}>
    {{ $slot }}
</span>
