<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Request :reference', ['reference' => $recordRequest->reference_no]) }}
            </h2>

            <a wire:navigate.hover href="{{ route('requests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">{{ __('Back to requests') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm rounded-lg" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($recordRequest->trashed())
                <div class="p-4 bg-red-50 text-red-800 text-sm rounded-lg">
                    {{ __('This request was archived on :date.', ['date' => $recordRequest->deleted_at->format('M j, Y g:i A')]) }}
                </div>
            @endif

            @php
                $statusColors = [
                    \App\Enums\RequestStatus::Pending->value => 'bg-yellow-100 text-yellow-800',
                    \App\Enums\RequestStatus::Approved->value => 'bg-blue-100 text-blue-800',
                    \App\Enums\RequestStatus::Released->value => 'bg-green-100 text-green-800',
                    \App\Enums\RequestStatus::Rejected->value => 'bg-red-100 text-red-800',
                    \App\Enums\RequestStatus::CancellationRequested->value => 'bg-orange-100 text-orange-800',
                    \App\Enums\RequestStatus::Cancelled->value => 'bg-gray-200 text-gray-700',
                ];

                $sections = [
                        __('Student') => [
                            __('Student number') => $recordRequest->student_no,
                            __('Full name') => $recordRequest->fullName(),
                            __('Course / program') => $recordRequest->course,
                            __('Enrolment status') => $recordRequest->enrolment_status->label(),
                            __('Email') => session('revealed_email') ? $recordRequest->email : $recordRequest->maskedEmail(),
                            __('Contact number') => $recordRequest->contact_no,
                        ],
                        __('Request') => [
                            __('Document') => $recordRequest->document_type->label(),
                            __('Copies') => $recordRequest->copies,
                            __('Purpose') => $recordRequest->purpose,
                            ...($recordRequest->designated_representative_name ? [__('Authorized representative') => $recordRequest->designated_representative_name] : []),
                            ...($recordRequest->designated_representative_id_type ? [__('Representative\'s valid ID') => $recordRequest->designated_representative_id_type->label()] : []),
                            __('Status') => $recordRequest->status->label(),
                            __('Submitted') => $recordRequest->created_at->format('M j, Y g:i A'),
                            ...($recordRequest->cancellation_requested_at ? [__('Cancellation requested') => $recordRequest->cancellation_requested_at->format('M j, Y g:i A')] : []),
                            ...($recordRequest->cancellation_reason ? [__('Cancellation reason') => $recordRequest->cancellation_reason] : []),
                            ...($recordRequest->cancelled_at ? [__('Cancelled') => $recordRequest->cancelled_at->format('M j, Y g:i A')] : []),
                        ],
                        ...($recordRequest->release ? [
                            __('Release') => [
                                __('Representative') => $recordRequest->release->representative_name,
                                __('Released by') => $recordRequest->release->releasedBy->name,
                                __('Released at') => $recordRequest->release->released_at->format('M j, Y g:i A'),
                                ...($recordRequest->release->claim_available_at ? [__('Available to claim from') => $recordRequest->release->claim_available_at->format('M j, Y g:i A')] : []),
                                __('Claim status') => $recordRequest->release->isClaimed() ? __('Claimed') : __('Awaiting claim'),
                                ...($recordRequest->release->claimed_at ? [__('Claimed at') => $recordRequest->release->claimed_at->format('M j, Y g:i A')] : []),
                            ],
                        ] : []),
                ];
            @endphp

            <div class="flex flex-wrap items-center justify-between gap-3 bg-white shadow-sm sm:rounded-lg p-6">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Reference number') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $recordRequest->reference_no }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Submitted :date', ['date' => $recordRequest->created_at->format('M j, Y g:i A')]) }}</p>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $statusColors[$recordRequest->status->value] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $recordRequest->status->label() }}
                </span>
            </div>

            @foreach ($sections as $heading => $rows)
                <section class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <h3 class="border-b border-gray-100 bg-gray-50 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-gray-600">{{ $heading }}</h3>
                    <dl class="divide-y divide-gray-100 px-6 text-sm">
                        @foreach ($rows as $label => $value)
                            <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="sm:col-span-2 break-words text-gray-900 whitespace-pre-line">
                                    {{ $value }}
                                    @if ($label === __('Email') && ! session('revealed_email'))
                                        @can('revealEmail', $recordRequest)
                                            <form method="POST" action="{{ route('requests.reveal-email', $recordRequest) }}" class="ms-2 inline-block align-middle">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-0.5 text-xs font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" title="{{ __('Show the full email address. This is recorded in the audit log.') }}">
                                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                    {{ __('Reveal') }}
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($recordRequest->trashed())
                    <div class="flex flex-wrap items-center gap-3">
                        @can('restore', $recordRequest)
                            <form method="POST" action="{{ route('requests.restore', $recordRequest) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Restore request') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-3">
                        @can('approve', $recordRequest)
                            <form method="POST" action="{{ route('requests.approve', $recordRequest) }}">
                                @csrf
                                <x-primary-button>{{ __('Approve') }}</x-primary-button>
                            </form>
                        @endcan

                        @can('reject', $recordRequest)
                            <form method="POST" action="{{ route('requests.reject', $recordRequest) }}" class="flex items-center gap-2" onsubmit="return this.reason.value.trim() !== '';">
                                @csrf
                                <input type="text" name="reason" placeholder="{{ __('Reason for rejection') }}" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <x-danger-button>{{ __('Reject') }}</x-danger-button>
                            </form>
                        @endcan

                        @can('release', $recordRequest)
                            <a wire:navigate.hover href="{{ route('requests.release.create', $recordRequest) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
                                {{ __('Release document') }}
                            </a>
                        @endcan

                        @can('claim', $recordRequest)
                            <x-primary-button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-claim')"
                            >{{ __('Mark as claimed') }}</x-primary-button>

                            <x-modal name="confirm-claim" focusable>
                                <form method="POST" action="{{ route('requests.claim', $recordRequest) }}" class="p-6">
                                    @csrf

                                    <h2 class="text-lg font-medium text-gray-900">{{ __('Mark as claimed') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('This defaults to right now, but you can set a different date and time if the pickup already happened.') }}
                                    </p>

                                    <div class="mt-4">
                                        <x-input-label for="claimed_at" :value="__('Claimed at')" />
                                        <x-text-input
                                            id="claimed_at"
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

                        @can('confirmCancellation', $recordRequest)
                            <form method="POST" action="{{ route('requests.confirm-cancellation', $recordRequest) }}" onsubmit="return confirm('{{ __('Confirm this cancellation? This cannot be undone.') }}');">
                                @csrf
                                <x-danger-button>{{ __('Confirm cancellation') }}</x-danger-button>
                            </form>
                        @endcan

                        @can('denyCancellation', $recordRequest)
                            <form method="POST" action="{{ route('requests.deny-cancellation', $recordRequest) }}">
                                @csrf
                                <x-secondary-button>{{ __('Keep request') }}</x-secondary-button>
                            </form>
                        @endcan

                        @can('delete', $recordRequest)
                            <x-danger-button
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-request-archive')"
                                class="ms-auto"
                            >{{ __('Archive request') }}</x-danger-button>

                            <x-modal name="confirm-request-archive" focusable>
                                <form method="POST" action="{{ route('requests.destroy', $recordRequest) }}" class="p-6">
                                    @csrf
                                    @method('delete')

                                    <h2 class="text-lg font-medium text-gray-900">
                                        {{ __('Archive this request?') }}
                                    </h2>

                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Request :reference will be moved to the Archived tab. Nothing is deleted, and you can restore it at any time.', ['reference' => $recordRequest->reference_no]) }}
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-danger-button class="ms-3">
                                            {{ __('Archive request') }}
                                        </x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
