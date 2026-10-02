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
        <div class="min-h-screen flex bg-gray-50">
            <!-- Brand panel -->
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-12 text-white lg:flex">
                <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 15% 20%, white 0, transparent 35%), radial-gradient(circle at 85% 75%, white 0, transparent 30%);"></div>

                <a href="/" class="relative flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-700">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-inset ring-white/25 backdrop-blur">
                        <x-application-logo class="h-6 w-6 fill-current text-white" />
                    </span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xl font-bold tracking-tight">{{ config('app.name', 'DocuQuest') }}</span>
                        <span class="text-[11px] font-semibold uppercase tracking-widest text-indigo-200">{{ __('Registrar workspace') }}</span>
                    </span>
                </a>

                <div class="relative max-w-md space-y-8">
                    <div class="space-y-4">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight">{{ __('Every record request, in one place.') }}</h1>
                        <p class="text-base leading-relaxed text-indigo-100">{{ __('Sign in to review student requests, release documents and keep each one moving.') }}</p>
                    </div>

                    <ul class="space-y-4">
                        @foreach ([
                            ['Review and approve requests', 'See what needs action, oldest first.', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            ['Release documents', 'Record who claimed each document and when.', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                            ['Handle cancellations', 'Confirm or decline what students ask to cancel.', 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
                        ] as [$title, $description, $icon])
                            <li class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-inset ring-white/20">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold">{{ __($title) }}</p>
                                    <p class="text-sm text-indigo-200">{{ __($description) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-sm text-indigo-200">&copy; {{ date('Y') }} {{ config('app.name', 'DocuQuest') }}. {{ __('All rights reserved.') }}</p>
            </div>

            <!-- Form panel -->
            <div class="flex w-full flex-1 flex-col items-center justify-center px-6 py-12 sm:px-10 lg:w-1/2">
                <a href="/" class="group mb-8 flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 shadow-md shadow-indigo-600/30 ring-1 ring-inset ring-white/20">
                        <x-application-logo class="h-6 w-6 fill-current text-white" />
                    </span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xl font-bold tracking-tight text-gray-900">{{ config('app.name', 'DocuQuest') }}</span>
                        <span class="text-[11px] font-semibold uppercase tracking-widest text-indigo-600">{{ __('Registrar workspace') }}</span>
                    </span>
                </a>

                <div class="w-full sm:max-w-md">
                    <div class="rounded-2xl bg-white px-6 py-8 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:px-10 sm:py-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    @livewireScripts
    </body>
</html>
