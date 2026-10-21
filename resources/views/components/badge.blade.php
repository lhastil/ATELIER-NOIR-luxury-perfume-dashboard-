{{-- Small status label. Tones: neutral, success, danger. --}}
@props(['tone' => 'neutral'])

<span {{ $attributes->class(['badge', "badge--{$tone}"]) }}>{{ $slot }}</span>
