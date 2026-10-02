<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DocuQuest') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col bg-gray-50">
            <header class="w-full border-b border-gray-200 bg-white">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 py-4 sm:px-6">
                    <a href="/" class="group flex items-center gap-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 min-[360px]:gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl min-[360px]:h-11 min-[360px]:w-11 bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 shadow-md shadow-indigo-600/30 ring-1 ring-inset ring-white/20 transition group-hover:scale-105 group-hover:shadow-lg group-hover:shadow-indigo-600/40">
                            <x-application-logo class="h-6 w-6 fill-current text-white" />
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-lg font-bold tracking-tight text-gray-900 min-[360px]:text-xl">{{ config('app.name', 'DocuQuest') }}</span>
                            <span class="hidden whitespace-nowrap text-[11px] font-semibold uppercase tracking-widest text-indigo-600 min-[360px]:block">{{ __('Record Requests') }}</span>
                        </span>
                    </a>

                    <a href="{{ route('record-requests.status.create') }}" class="group inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full bg-indigo-50 px-3 py-2 text-sm sm:px-4 font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        {{ __('Check Status') }}
                    </a>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-gray-200 bg-white py-6">
                <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} {{ config('app.name', 'DocuQuest') }}. {{ __('All rights reserved.') }}</p>
            </footer>
        </div>
    @livewireScripts
    </body>
</html>
