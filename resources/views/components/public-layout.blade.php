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
                <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <a href="/" class="flex items-center gap-2">
                        <x-application-logo class="h-8 w-8 fill-current text-indigo-600" />
                        <span class="text-lg font-semibold text-gray-900">{{ config('app.name', 'DocuQuest') }}</span>
                    </a>

                    <a href="{{ route('record-requests.status.create') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
