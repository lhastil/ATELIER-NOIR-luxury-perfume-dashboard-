{{--
    Disclosure dropdown. The trigger slot is rendered inside the toggle button;
    the default slot is the panel. Behaviour lives in resources/js/ui/dropdown.js.
--}}
@props(['id', 'label', 'align' => 'end', 'triggerClass' => 'icon-button'])

<div {{ $attributes->class('dropdown') }} data-dropdown>
    <button type="button" class="{{ $triggerClass }}" aria-label="{{ $label }}" aria-expanded="false" aria-controls="{{ $id }}" data-dropdown-trigger>
        {{ $trigger }}
    </button>

    <div id="{{ $id }}" class="dropdown__panel dropdown__panel--{{ $align }}" data-dropdown-panel hidden>
        {{ $slot }}
    </div>
</div>
