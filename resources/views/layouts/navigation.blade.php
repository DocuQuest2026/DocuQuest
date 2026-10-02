@php
    $initials = \Illuminate\Support\Str::of(Auth::user()->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="group flex shrink-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 shadow-md shadow-indigo-600/30 ring-1 ring-inset ring-white/20 transition group-hover:scale-105">
                        <x-application-logo class="h-5 w-5 fill-current text-white" />
                    </span>
                    <span class="hidden flex-col leading-tight sm:flex">
                        <span class="text-lg font-bold tracking-tight text-gray-900">{{ config('app.name', 'DocuQuest') }}</span>
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">{{ __('Registrar workspace') }}</span>
                    </span>
                </a>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden md:flex md:items-center md:ms-6 md:gap-3">
                <x-dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white py-1.5 pe-3 ps-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition ease-in-out duration-150">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $initials }}</span>
                            <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>

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

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out" x-bind:aria-expanded="open" aria-label="{{ __('Menu') }}">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Links (second row, large screens) -->
    <div class="hidden border-t border-gray-100 md:block">
        <div class="max-w-7xl mx-auto flex items-center gap-1 px-4 py-2 sm:px-6 lg:px-8">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-nav-link>

            @can('viewAny', \App\Models\RecordRequest::class)
                <x-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.index') || request()->routeIs('requests.show')">
                    {{ __('Student Requests') }}
                    @if ($pendingRequestsCount > 0)
                        <span
                            x-data="{ count: {{ $pendingRequestsCount }} }"
                            x-on:request-badges-updated.window="count = $event.detail.pending"
                            x-show="count > 0"
                            x-text="count > 9 ? '9+' : count"
                            class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white"
                        >{{ $pendingRequestsCount > 9 ? '9+' : $pendingRequestsCount }}</span>
                    @endif
                </x-nav-link>

                <x-nav-link :href="route('requests.cancellations')" :active="request()->routeIs('requests.cancellations')">
                    {{ __('Cancellation Requests') }}
                    @if ($pendingCancellationsCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                            {{ $pendingCancellationsCount > 9 ? '9+' : $pendingCancellationsCount }}
                        </span>
                    @endif
                </x-nav-link>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <x-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')">
                    {{ __('Staff accounts') }}
                </x-nav-link>
            @endcan
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('viewAny', \App\Models\RecordRequest::class)
                <x-responsive-nav-link :href="route('requests.cancellations')" :active="request()->routeIs('requests.cancellations')">
                    {{ __('Cancellation Requests') }}
                    @if ($pendingCancellationsCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                            {{ $pendingCancellationsCount > 9 ? '9+' : $pendingCancellationsCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.index') || request()->routeIs('requests.show')">
                    {{ __('Student Requests') }}
                    @if ($pendingRequestsCount > 0)
                        <span
                            x-data="{ count: {{ $pendingRequestsCount }} }"
                            x-on:request-badges-updated.window="count = $event.detail.pending"
                            x-show="count > 0"
                            x-text="count > 9 ? '9+' : count"
                            class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white"
                        >{{ $pendingRequestsCount > 9 ? '9+' : $pendingRequestsCount }}</span>
                    @endif
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <x-responsive-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')">
                    {{ __('Staff accounts') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center gap-3 px-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ $initials }}</span>
                <div class="min-w-0">
                    <div class="truncate font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="truncate font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
