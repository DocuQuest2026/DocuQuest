<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ __('Cancellation Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm rounded-xl ring-1 ring-inset ring-green-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @php
                $filters = ['search' => $search, 'month' => $month, 'day' => $day];
                $hasFilters = $search !== '' || $month !== now()->format('Y-m') || $day !== now()->format('d');
                $baseQuery = $showingCancelled ? ['status' => 'cancelled'] : [];
            @endphp

            <form method="GET" action="{{ route('requests.cancellations') }}" class="flex flex-wrap items-center gap-2" x-data="{ timer: null }">
                @if ($showingCancelled)
                    <input type="hidden" name="status" value="cancelled">
                @endif

                <div class="relative w-full sm:max-w-md">

                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>

                    <x-text-input
                    type="search"
                    name="search"
                    :value="$search"
                    placeholder="{{ __('Search by name, reference no. or student no.') }}"
                    aria-label="{{ __('Search cancellation requests') }}"
                    class="block w-full rounded-lg py-2.5 pl-10"
                    x-on:input="clearTimeout(timer); timer = setTimeout(() => $el.form.requestSubmit(), 500)"
                    x-init="if ($el.value !== '' && new URLSearchParams(location.search).has('search')) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length); }"
                    autocomplete="off"
                />

                </div>

                <x-text-input
                    type="month"
                    name="month"
                    :value="$month"
                    min="{{ config('school.first_request_month') }}"
                    aria-label="{{ $showingCancelled ? __('Filter by month cancelled') : __('Filter by month requested') }}"
                    title="{{ $showingCancelled ? __('Show only requests cancelled in this month') : __('Show only cancellation requests made in this month') }}"
                    class="block rounded-lg py-2.5"
                    onchange="this.form.requestSubmit()"
                />

                <x-select-input
                    name="day"
                    class="rounded-lg py-2.5"
                    aria-label="{{ __('Filter by day') }}"
                    :disabled="$month === ''"
                    onchange="this.form.requestSubmit()"
                >
                    <option value="">{{ __('All days') }}</option>
                    @foreach ($daysOfMonth as $dayNumber)
                        <option value="{{ sprintf('%02d', $dayNumber) }}" @selected($day === sprintf('%02d', $dayNumber))>{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $month.'-'.sprintf('%02d', $dayNumber))->format('j · D') }}</option>
                    @endforeach
                </x-select-input>

                @if ($hasFilters)
                    <a wire:navigate href="{{ route('requests.cancellations', $baseQuery) }}" class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50">{{ __('Clear') }}</a>
                @endif
            </form>

            <div class="flex flex-wrap gap-2">
                <a wire:navigate.hover
                    href="{{ route('requests.cancellations', $filters) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold {{ ! $showingCancelled ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50' }}"
                >
                    {{ __('Pending') }}
                </a>
                <a wire:navigate.hover
                    href="{{ route('requests.cancellations', ['status' => 'cancelled'] + $filters) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $showingCancelled ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50' }}"
                >
                    {{ __('Cancelled') }}
                </a>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reason') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ $showingCancelled ? __('Cancelled') : __('Requested') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recordRequests as $recordRequest)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $recordRequest->fullName() }}
                                    <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $recordRequest->cancellation_reason }}">{{ $recordRequest->cancellation_reason }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ ($showingCancelled ? $recordRequest->cancelled_at : $recordRequest->cancellation_requested_at)->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a wire:navigate.hover href="{{ route('requests.show', $recordRequest) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('View') }}</a>

                                    @can('confirmCancellation', $recordRequest)
                                        <form method="POST" action="{{ route('requests.confirm-cancellation', $recordRequest) }}" class="inline" onsubmit="return confirm('{{ __('Confirm this cancellation? This cannot be undone.') }}');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('Confirm') }}</button>
                                        </form>
                                    @endcan

                                    @can('denyCancellation', $recordRequest)
                                        <form method="POST" action="{{ route('requests.deny-cancellation', $recordRequest) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Keep request') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    @if ($search !== '' || $month !== '')
                                        {{ __('No cancellation requests match your search or date.') }}
                                    @else
                                        {{ $showingCancelled ? __('No requests have been cancelled.') : __('No cancellation requests are awaiting review.') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $recordRequests->links() }}
        </div>
    </div>
</x-app-layout>
