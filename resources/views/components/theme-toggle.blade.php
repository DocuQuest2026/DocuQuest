<div
    x-data="{ theme: window.getTheme ? window.getTheme() : 'system' }"
    {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border border-gray-300 bg-white p-0.5']) }}
    role="group"
    aria-label="{{ __('Color theme') }}"
>
    @foreach ([
        'light' => ['Light', 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z'],
        'system' => ['Use device setting', 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25'],
        'dark' => ['Dark', 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z'],
    ] as $value => [$label, $icon])
        <button
            type="button"
            x-on:click="theme = '{{ $value }}'; window.setTheme('{{ $value }}')"
            x-bind:class="theme === '{{ $value }}' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900'"
            class="inline-flex h-7 w-7 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500"
            x-bind:aria-pressed="theme === '{{ $value }}'"
            title="{{ __($label) }}"
            aria-label="{{ __($label) }}"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
        </button>
    @endforeach
</div>
