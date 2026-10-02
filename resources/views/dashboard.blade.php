<x-app-layout>
    @php
        $firstName = \Illuminate\Support\Str::of(Auth::user()->name)->explode(' ')->filter()->first() ?? Auth::user()->name;
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Welcome --}}
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">{{ __('Welcome back, :name', ['name' => $firstName]) }}</h1>
                        <p class="mt-2 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ Auth::user()->role->label() }}</p>
                    </div>
                </div>
            </section>

            @isset($actionableRequests)
                {{-- Request counts --}}
                <section aria-label="{{ __('Request counts') }}">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['Pending', 'Waiting for approval', $counts['pending'] ?? 0, route('requests.index', ['status' => 'pending']), 'amber', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            ['Approved', 'Ready to release', $counts['approved'] ?? 0, route('requests.index', ['status' => 'approved']), 'blue', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            ['Cancellations', 'Students asking to cancel', $counts['cancellation_requested'] ?? 0, route('requests.cancellations'), 'orange', 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
                            ['Released', 'Ready for pickup', $counts['released'] ?? 0, route('requests.index', ['status' => 'released']), 'green', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                        ] as [$label, $hint, $count, $url, $tone, $icon])
                            @php
                                $tones = [
                                    'amber' => 'bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white',
                                    'blue' => 'bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white',
                                    'orange' => 'bg-orange-50 text-orange-600 group-hover:bg-orange-500 group-hover:text-white',
                                    'green' => 'bg-green-50 text-green-600 group-hover:bg-green-600 group-hover:text-white',
                                ];
                            @endphp

                            <a wire:navigate.hover href="{{ $url }}" class="group flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 transition hover:shadow-md hover:ring-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition {{ $tones[$tone] }}">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                </span>

                                <span class="min-w-0">
                                    <span class="block text-3xl font-bold leading-none tracking-tight {{ $count > 0 ? 'text-gray-900' : 'text-gray-600' }}">{{ $count }}</span>
                                    <span class="mt-1 block text-sm font-semibold text-gray-900">{{ __($label) }}</span>
                                    <span class="block text-sm text-gray-600">{{ __($hint) }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Requests needing action --}}
                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-900/5" aria-labelledby="needs-action">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5 sm:p-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 id="needs-action" class="text-base font-semibold text-gray-900">{{ __('Requests needing action') }}</h2>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">{{ __('First come, first served.') }}</p>
                        </div>

                        <a wire:navigate.hover href="{{ route('requests.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('View all requests') }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>

                    @if ($actionableRequests->isEmpty())
                        <div class="flex flex-col items-center gap-3 px-6 py-12 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <p class="text-sm font-medium text-gray-600">{{ __('Nothing needs your attention right now.') }}</p>
                        </div>
                    @else
                        @php
                            $actions = [
                                \App\Enums\RequestStatus::Pending->value => ['label' => __('Needs approval'), 'classes' => 'bg-amber-100 text-amber-800'],
                                \App\Enums\RequestStatus::Approved->value => ['label' => __('Ready to release'), 'classes' => 'bg-blue-100 text-blue-800'],
                                \App\Enums\RequestStatus::CancellationRequested->value => ['label' => __('Cancellation requested'), 'classes' => 'bg-orange-100 text-orange-800'],
                            ];
                            $columns = 'lg:grid lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)_minmax(0,1.6fr)_minmax(0,1.1fr)_minmax(0,1.3fr)_auto] lg:items-center lg:gap-x-6';
                        @endphp

                        {{-- Column titles (large screens only) --}}
                        <div class="hidden border-b border-gray-100 bg-gray-50 px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600 {{ $columns }}" aria-hidden="true">
                            <span>{{ __('Reference') }}</span>
                            <span>{{ __('Student') }}</span>
                            <span>{{ __('Document') }}</span>
                            <span>{{ __('Submitted') }}</span>
                            <span>{{ __('Action needed') }}</span>
                            <span class="w-36"></span>
                        </div>

                        <ul class="divide-y divide-gray-100">
                            @foreach ($actionableRequests as $recordRequest)
                                <li class="flex flex-col gap-3 p-5 transition hover:bg-gray-50 sm:p-6 {{ $columns }}">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="font-mono text-sm font-semibold tracking-wide text-gray-900">{{ $recordRequest->reference_no }}</span>
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold lg:hidden {{ $actions[$recordRequest->status->value]['classes'] }}">
                                            {{ $actions[$recordRequest->status->value]['label'] }}
                                        </span>
                                    </div>

                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $recordRequest->fullName() }}
                                        <span class="block text-sm font-normal text-gray-600">{{ $recordRequest->student_no }}</span>
                                    </p>

                                    <p class="text-sm text-gray-700">
                                        {{ $recordRequest->document_type->label() }}
                                        <span class="whitespace-nowrap font-semibold">&times; {{ $recordRequest->copies }}</span>
                                    </p>

                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium lg:hidden">{{ __('Submitted') }}</span>
                                        {{ $recordRequest->created_at->format('M j, Y') }}
                                        <span class="block text-sm text-gray-600">{{ $recordRequest->created_at->diffForHumans() }}</span>
                                    </p>

                                    <p class="hidden lg:block">
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $actions[$recordRequest->status->value]['classes'] }}">
                                            {{ $actions[$recordRequest->status->value]['label'] }}
                                        </span>
                                    </p>

                                    <a wire:navigate.hover href="{{ route('requests.show', $recordRequest) }}" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:w-36">
                                        {{ __('Open request') }}
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($actionableCount > $actionableRequests->count())
                            <p class="border-t border-gray-100 bg-gray-50 px-5 py-3 text-sm text-gray-600 sm:px-6">
                                {{ __('Showing the oldest :shown of :total requests needing action.', ['shown' => $actionableRequests->count(), 'total' => $actionableCount]) }}
                            </p>
                        @endif
                    @endif
                </section>
            @endisset
        </div>
    </div>
</x-app-layout>
