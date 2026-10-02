<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex flex-wrap gap-2">
                @php
                    $isActive = $statusFilter === null;
                @endphp
                <a wire:navigate.hover
                    href="{{ route('requests.index') }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('All') }}
                </a>
                @foreach ($filterableStatuses as $status)
                    @php
                        $isActive = $statusFilter === $status;
                    @endphp
                    <a wire:navigate.hover
                        href="{{ route('requests.index', ['status' => $status->value]) }}"
                        class="rounded-md px-3 py-1.5 text-sm font-medium {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                    >
                        {{ $status->label() }}
                    </a>

                    @if ($status === \App\Enums\RequestStatus::Released)
                        <a wire:navigate.hover
                            href="{{ route('requests.index', ['status' => 'claimed']) }}"
                            class="rounded-md px-3 py-1.5 text-sm font-medium {{ $showingClaimed ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                        >
                            {{ __('Claimed') }}
                        </a>
                    @endif
                @endforeach

                <a wire:navigate.hover
                    href="{{ route('requests.index', ['status' => 'archived']) }}"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $showingArchived ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} shadow-sm"
                >
                    {{ __('Archived') }}
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Copies') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                {{ $showingArchived ? __('Archived') : ($showingClaimed ? __('Claimed') : __('Submitted')) }}
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $statusColors = [
                                \App\Enums\RequestStatus::Pending->value => 'bg-yellow-100 text-yellow-800',
                                \App\Enums\RequestStatus::Approved->value => 'bg-blue-100 text-blue-800',
                                \App\Enums\RequestStatus::Released->value => 'bg-green-100 text-green-800',
                                \App\Enums\RequestStatus::Rejected->value => 'bg-red-100 text-red-800',
                                \App\Enums\RequestStatus::CancellationRequested->value => 'bg-orange-100 text-orange-800',
                                \App\Enums\RequestStatus::Cancelled->value => 'bg-gray-200 text-gray-700',
                            ];
                        @endphp
                        @forelse ($recordRequests as $recordRequest)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $recordRequest->fullName() }}
                                    <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $recordRequest->copies }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @php
                                        $dateColumn = $showingArchived
                                            ? $recordRequest->deleted_at
                                            : ($showingClaimed ? $recordRequest->release->claimed_at : $recordRequest->created_at);
                                    @endphp
                                    {{ $dateColumn->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$recordRequest->status->value] }}">{{ $recordRequest->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                    <a wire:navigate.hover href="{{ route('requests.show', $recordRequest) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('View') }}</a>

                                    @can('claim', $recordRequest)
                                        <button
                                            type="button"
                                            x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-claim-{{ $recordRequest->id }}')"
                                            class="inline-flex items-center rounded-md bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700"
                                        >{{ __('Claim') }}</button>

                                        <x-modal name="confirm-claim-{{ $recordRequest->id }}" focusable>
                                            <form method="POST" action="{{ route('requests.claim', $recordRequest) }}" class="p-6">
                                                @csrf

                                                <h2 class="text-lg font-medium text-gray-900">{{ __('Mark as claimed') }}</h2>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    {{ __('This defaults to right now, but you can set a different date and time if the pickup already happened.') }}
                                                </p>

                                                <div class="mt-4">
                                                    <x-input-label for="claimed_at_{{ $recordRequest->id }}" :value="__('Claimed at')" />
                                                    <x-text-input
                                                        id="claimed_at_{{ $recordRequest->id }}"
                                                        name="claimed_at"
                                                        type="datetime-local"
                                                        class="mt-1 block w-full"
                                                        value="{{ now()->format('Y-m-d\TH:i') }}"
                                                        max="{{ now()->format('Y-m-d\TH:i') }}"
                                                        required
                                                    />
                                                </div>

                                                <div class="mt-6 flex justify-end">
                                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                                        {{ __('Cancel') }}
                                                    </x-secondary-button>

                                                    <x-primary-button class="ms-3">
                                                        {{ __('Confirm claim') }}
                                                    </x-primary-button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    @endcan

                                    @can('restore', $recordRequest)
                                        <form method="POST" action="{{ route('requests.restore', $recordRequest) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">{{ __('Restore') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    @if ($showingArchived)
                                        {{ __('No archived requests.') }}
                                    @elseif ($showingClaimed)
                                        {{ __('No claimed documents yet.') }}
                                    @else
                                        {{ __('No requests have been submitted yet.') }}
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
