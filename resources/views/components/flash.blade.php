{{-- Confirmation after an action (e.g. "Product updated"), announced politely to screen readers. --}}
@if (session('status'))
    <div {{ $attributes->class('flash') }} role="status">
        <x-icon name="check" :size="18" />
        <p>{{ session('status') }}</p>
    </div>
@endif
