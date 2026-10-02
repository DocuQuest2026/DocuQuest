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

                <a href="/" class="relative flex items-center gap-3">
                    <x-application-logo class="h-9 w-9 fill-current text-white" />
                    <span class="text-xl font-semibold tracking-tight">{{ config('app.name', 'DocuQuest') }}</span>
                </a>

                <div class="relative max-w-md space-y-4">
                    <h1 class="text-3xl font-bold leading-tight">Your documents, organized and always within reach.</h1>
                    <p class="text-base leading-relaxed text-indigo-100">Sign in to manage your student records, track your enrolment, and pick up right where you left off.</p>
                </div>

                <p class="relative text-sm text-indigo-200">&copy; {{ date('Y') }} {{ config('app.name', 'DocuQuest') }}. All rights reserved.</p>
            </div>

            <!-- Form panel -->
            <div class="flex w-full flex-1 flex-col items-center justify-center px-6 py-12 sm:px-10 lg:w-1/2">
                <a href="/" class="mb-8 flex items-center gap-2 lg:hidden">
                    <x-application-logo class="h-9 w-9 fill-current text-indigo-600" />
                    <span class="text-lg font-semibold text-gray-900">{{ config('app.name', 'DocuQuest') }}</span>
                </a>

                <div class="w-full sm:max-w-md">
                    <div class="rounded-2xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-900/5 sm:px-10 sm:py-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    @livewireScripts
    </body>
</html>
