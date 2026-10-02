<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @can('viewAny', \App\Models\RecordRequest::class)
                        <x-nav-link :href="route('requests.cancellations')" :active="request()->routeIs('requests.cancellations')">
                            {{ __('Cancellation requests') }}
                            @if ($pendingCancellationsCount > 0)
                                <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                                    {{ $pendingCancellationsCount > 9 ? '9+' : $pendingCancellationsCount }}
                                </span>
                            @endif
                        </x-nav-link>

                        <x-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.index') || request()->routeIs('requests.show')">
                            {{ __('Student requests') }}
                            @if ($pendingRequestsCount > 0)
                                <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                                    {{ $pendingRequestsCount > 9 ? '9+' : $pendingRequestsCount }}
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

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                @if (Auth::user()->isOfficeUser())
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button class="relative inline-flex items-center p-2 rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>

                                @if ($unreadNotifications > 0)
                                    <span class="absolute top-0 right-0 inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-600 text-[10px] font-semibold text-white">
                                        {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                                <span class="text-sm font-semibold text-gray-700">{{ __('Notifications') }}</span>

                                @if ($unreadNotifications > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 underline">{{ __('Mark all read') }}</button>
                                    </form>
                                @endif
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                @forelse ($recentNotifications as $notification)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm hover:bg-gray-100 {{ $notification->read_at ? 'text-gray-500' : 'text-gray-900 font-medium bg-indigo-50' }}">
                                            {{ __(':name asked to cancel :reference', ['name' => $notification->data['student_name'], 'reference' => $notification->data['reference_no']]) }}
                                            <span class="block text-xs font-normal text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                                        </button>
                                    </form>
                                @empty
                                    <p class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('No notifications yet.') }}</p>
                                @endforelse
                            </div>
                        </x-slot>
                    </x-dropdown>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
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
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('viewAny', \App\Models\RecordRequest::class)
                <x-responsive-nav-link :href="route('requests.cancellations')" :active="request()->routeIs('requests.cancellations')">
                    {{ __('Cancellation requests') }}
                    @if ($pendingCancellationsCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                            {{ $pendingCancellationsCount > 9 ? '9+' : $pendingCancellationsCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.index') || request()->routeIs('requests.show')">
                    {{ __('Student requests') }}
                    @if ($pendingRequestsCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white">
                            {{ $pendingRequestsCount > 9 ? '9+' : $pendingRequestsCount }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <x-responsive-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')">
                    {{ __('Staff accounts') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        @if (Auth::user()->isOfficeUser())
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="flex items-center justify-between px-4">
                    <span class="font-medium text-base text-gray-800">
                        {{ __('Notifications') }}
                        @if ($unreadNotifications > 0)
                            <span class="ms-1 inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-600 text-[10px] font-semibold text-white">
                                {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                            </span>
                        @endif
                    </span>

                    @if ($unreadNotifications > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 underline">{{ __('Mark all read') }}</button>
                        </form>
                    @endif
                </div>

                <div class="mt-3 space-y-1">
                    @forelse ($recentNotifications as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50 {{ $notification->read_at ? 'text-gray-500' : 'text-gray-900 font-medium' }}">
                                {{ __(':name asked to cancel :reference', ['name' => $notification->data['student_name'], 'reference' => $notification->data['reference_no']]) }}
                            </button>
                        </form>
                    @empty
                        <p class="px-4 py-2 text-sm text-gray-500">{{ __('No notifications yet.') }}</p>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
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
