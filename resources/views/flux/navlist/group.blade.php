@props([
'expandable' => false,
'expanded' => true,
'heading' => null,
])

<?php if ($expandable && $heading): ?>

<ui-disclosure
{{ $attributes->class('group/disclosure') }}
@if ($expanded === true) open @endif
data-flux-navlist-group

>


{{-- Expandable Heading --}}



<button
    type="button"
    class="group/disclosure-button mb-1 flex h-10 w-full items-center rounded-xl text-zinc-500 transition hover:bg-indigo-50 hover:text-indigo-600 lg:h-9 dark:text-white/70 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
>

    {{-- Chevron --}}
    <div class="ps-3 pe-4">

        <flux:icon.chevron-down
            class="hidden size-3! text-current group-data-open/disclosure-button:block"
        />

        <flux:icon.chevron-right
            class="block size-3! text-current group-data-open/disclosure-button:hidden"
        />

    </div>


    {{-- Heading --}}
    <span
        class="text-sm font-semibold leading-none"
    >
        {{ $heading }}
    </span>

</button>


{{-- Expandable Content --}}
<div
    class="relative hidden space-y-1 ps-7 data-open:block"
    @if ($expanded === true) data-open @endif
>

    {{-- Vertical Guide Line --}}
    <div
        class="absolute inset-y-1 start-0 ms-4 w-px bg-zinc-200 dark:bg-white/15"
    ></div>


    {{ $slot }}

</div>
```

</ui-disclosure>

<?php elseif ($heading): ?>

{{-- ================================================================ --}}
{{-- STATIC NAVIGATION GROUP --}}
{{-- ================================================================ --}}

<div
    {{ $attributes->class('block space-y-1') }}
>

```
{{-- Section Heading --}}
<div class="px-2 pb-2 pt-3">

    <div
        class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
    >
        {{ $heading }}
    </div>

</div>


{{-- Navigation Items --}}
<div>
    {{ $slot }}
</div>

</div>

<?php else: ?>

{{-- ================================================================ --}}
{{-- NAVIGATION WITHOUT HEADING --}}
{{-- ================================================================ --}}

<div
    {{ $attributes->class('block space-y-1') }}
>
    {{ $slot }}
</div>

<?php endif; ?>
