<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/docuquest-app-icon.svg') }}">

        @include('partials.theme-init')


        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false" class="min-h-screen bg-gray-50">
            @include('layouts.navigation')

            <div class="flex min-h-screen flex-col lg:pl-64">
                <!-- Top bar -->
                <div class="flex min-h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 sm:px-6 lg:px-8">
                    <button type="button" x-on:click="sidebarOpen = true" class="-ms-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:hidden" x-bind:aria-expanded="sidebarOpen" aria-label="{{ __('Menu') }}">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    <x-theme-toggle />


                    <x-dropdown align="right" width="w-64">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white py-1.5 pe-3 ps-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition ease-in-out duration-150">
                                <x-user-avatar :user="Auth::user()" class="h-7 w-7" />
                                <span class="hidden max-w-[10rem] truncate sm:inline">{{ Auth::user()->name }}</span>

                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                <span class="mt-2 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ Auth::user()->role->label() }}</span>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <footer class="border-t border-gray-200 bg-white py-6">
                    <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} {{ config('app.name', 'DocuQuest') }}. {{ __('All rights reserved.') }}</p>
                </footer>
            </div>
        </div>
    @livewireScripts
    </body>
</html>
