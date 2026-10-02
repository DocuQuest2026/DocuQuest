@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out'
            : 'inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out';
@endphp

<a wire:navigate.hover @if ($active ?? false) aria-current="page" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
