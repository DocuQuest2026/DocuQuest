<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ __('Request :reference', ['reference' => $recordRequest->reference_no]) }}
            </h2>

            <a wire:navigate.hover href="{{ route('requests.index') }}" class="group inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ __('Back to requests') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm rounded-xl ring-1 ring-inset ring-green-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($recordRequest->trashed())
                <div class="flex items-start gap-3 p-4 bg-red-50 text-red-800 text-sm rounded-xl ring-1 ring-inset ring-red-200" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                    <span>{{ __('This request was archived on :date.', ['date' => $recordRequest->deleted_at->format('M j, Y g:i A')]) }}</span>
                </div>
            @endif

            @php
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
                            __('Status') => $recordRequest->staffStatus()['label'],
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

            <div class="flex flex-wrap items-center justify-between gap-3 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl p-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Reference number') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $recordRequest->reference_no }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Submitted :date', ['date' => $recordRequest->created_at->format('M j, Y g:i A')]) }}</p>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $recordRequest->staffStatus()['classes'] }}">
                    {{ $recordRequest->staffStatus()['label'] }}
                </span>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($sections as $heading => $rows)
                <section class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl overflow-hidden self-start {{ $loop->last && $loop->count === 3 ? 'lg:col-span-2' : '' }}">
                    <h3 class="border-b border-gray-100 bg-gray-50 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-gray-600">{{ $heading }}</h3>
                    <dl class="divide-y divide-gray-100 px-6 text-sm">
                        @foreach ($rows as $label => $value)
                            <div class="grid grid-cols-1 items-center gap-1 py-3.5 sm:grid-cols-5 sm:gap-4">
                                <dt class="font-medium text-gray-600 sm:col-span-2">{{ $label }}</dt>
                                <dd class="flex flex-wrap items-center gap-x-3 gap-y-2 break-words text-gray-900 sm:col-span-3">
                                    <span class="whitespace-pre-line">{{ $value }}</span>

                                    @if ($label === __('Email'))
                                        @if (session('revealed_email'))
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-200" title="{{ __('Shown for this page view only. This was recorded in the audit log.') }}">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                {{ __('Email revealed') }}
                                            </span>
                                        @else
                                            @can('revealEmail', $recordRequest)
                                                <form method="POST" action="{{ route('requests.reveal-email', $recordRequest) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" title="{{ __('Show the full email address. This is recorded in the audit log.') }}">
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                        {{ __('Reveal email') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl p-6 space-y-6">
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-inset ring-red-200" role="alert">
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
                            <x-primary-button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-approve')"
                            >{{ __('Approve') }}</x-primary-button>

                            <x-modal name="confirm-approve" focusable>
                                <form method="POST" action="{{ route('requests.approve', $recordRequest) }}" class="p-6">
                                    @csrf

                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Approve this request?') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Request :reference will be approved so its document can be released. Once approved, the requester can no longer cancel it.', ['reference' => $recordRequest->reference_no]) }}
                                    </p>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-primary-button>{{ __('Yes, approve') }}</x-primary-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endcan

                        @can('reject', $recordRequest)
                            <x-danger-button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-reject')"
                            >{{ __('Reject') }}</x-danger-button>

                            <x-modal name="confirm-reject" :show="$errors->has('reason')" focusable>
                                <form method="POST" action="{{ route('requests.reject', $recordRequest) }}" class="p-6">
                                    @csrf

                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Reject this request?') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Tell the requester why request :reference could not be approved. The reason is emailed to them.', ['reference' => $recordRequest->reference_no]) }}
                                    </p>

                                    <div class="mt-4">
                                        <x-input-label for="reject_reason" :value="__('Reason for rejection')" />
                                        <textarea
                                            id="reject_reason"
                                            name="reason"
                                            rows="4"
                                            maxlength="1000"
                                            required
                                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="{{ __('For example: the student number does not match our records.') }}"
                                        >{{ old('reason') }}</textarea>
                                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-danger-button>{{ __('Reject request') }}</x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endcan

                        @can('release', $recordRequest)
                            <a wire:navigate.hover href="{{ route('requests.release.create', $recordRequest) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
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
