@php
    $badge = 'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-semibold text-white';
    $sectionHeading = 'mb-1 mt-5 flex items-center gap-3 px-3 text-xs font-semibold uppercase tracking-wide text-gray-600 after:h-px after:flex-1 after:bg-gray-200';
@endphp

{{-- Mobile backdrop --}}
<div x-cloak x-show="sidebarOpen" x-transition.opacity x-on:click="sidebarOpen = false" class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden" aria-hidden="true"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
    x-bind:class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen }"
    aria-label="{{ __('Main navigation') }}"
>
    {{-- Brand --}}
    <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <x-application-logo class="h-10 w-10 shrink-0 rounded-xl shadow-md transition group-hover:scale-105" />
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate text-lg font-bold tracking-tight text-gray-900">{{ config('app.name', 'DocuQuest') }}</span>
                <span class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">{{ __('Registrar workspace') }}</span>
            </span>
        </a>

        <button type="button" x-on:click="sidebarOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:hidden" aria-label="{{ __('Close menu') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Links --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z">
            {{ __('Dashboard') }}
        </x-sidebar-link>

        @can('viewAny', \App\Models\RecordRequest::class)
            <p class="{{ $sectionHeading }}">{{ __('Requests') }}</p>

            <div class="space-y-1">
                <x-sidebar-link :href="route('requests.index')" :active="request()->routeIs('requests.index') || request()->routeIs('requests.show')" icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z">
                    {{ __('Student Requests') }}
                    @if ($pendingRequestsCount > 0)
                        <span
                            x-data="{ count: {{ $pendingRequestsCount }} }"
                            x-on:request-badges-updated.window="count = $event.detail.pending"
                            x-show="count > 0"
                            x-text="count > 9 ? '9+' : count"
                            class="{{ $badge }}"
                        >{{ $pendingRequestsCount > 9 ? '9+' : $pendingRequestsCount }}</span>
                    @endif
                </x-sidebar-link>

                <x-sidebar-link :href="route('requests.cancellations')" :active="request()->routeIs('requests.cancellations')" icon="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z">
                    {{ __('Cancellation Requests') }}
                    @if ($pendingCancellationsCount > 0)
                        <span class="{{ $badge }}">{{ $pendingCancellationsCount > 9 ? '9+' : $pendingCancellationsCount }}</span>
                    @endif
                </x-sidebar-link>
            </div>

            <p class="{{ $sectionHeading }}">{{ __('Reports') }}</p>

            <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z">
                {{ __('Reports') }}
            </x-sidebar-link>
        @endcan

        @if (Gate::allows('viewAny', \App\Models\User::class) || Gate::allows('viewAny', \App\Models\DocumentType::class))
            <p class="{{ $sectionHeading }}">{{ __('Admin') }}</p>

            <div class="space-y-1">
                @can('viewAny', \App\Models\User::class)
                    <x-sidebar-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')" icon="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z">
                        {{ __('Staff accounts') }}
                    </x-sidebar-link>
                @endcan

                @can('viewAny', \App\Models\DocumentType::class)
                    <x-sidebar-link :href="route('admin.document-types.index')" :active="request()->routeIs('admin.document-types.*')" icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z">
                        {{ __('Documents') }}
                    </x-sidebar-link>
                @endcan
            </div>
        @endif
    </nav>

    {{-- Signed-in user --}}
    <div class="border-t border-gray-200 p-4">
        <div class="mb-3 flex items-center gap-3">
            <x-user-avatar :user="Auth::user()" class="h-9 w-9" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-gray-600">{{ Auth::user()->email }}</p>
                <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ Auth::user()->role->label() }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">{{ __('Log Out') }}</button>
        </form>
    </div>
</aside>
