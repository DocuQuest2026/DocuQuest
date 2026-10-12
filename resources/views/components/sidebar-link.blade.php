@props(['active' => false, 'icon'])

@php
$classes = $active
            ? 'flex items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out'
            : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out';
@endphp

<a wire:navigate.hover @if ($active) aria-current="page" @endif {{ $attributes->merge(['class' => $classes]) }}>
    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
    <span class="flex min-w-0 flex-1 items-center justify-between gap-2">{{ $slot }}</span>
</a>
