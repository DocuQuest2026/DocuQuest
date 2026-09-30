<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Request :reference', ['reference' => $recordRequest->reference_no]) }}
            </h2>

            <a href="{{ route('requests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">{{ __('Back to requests') }}</a>
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
                    {{ __('This request was deleted on :date.', ['date' => $recordRequest->deleted_at->format('M j, Y g:i A')]) }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-8">
                @php
                    $sections = [
                        __('Student') => [
                            __('Student number') => $recordRequest->student_no,
                            __('Full name') => $recordRequest->fullName(),
                            __('Course / program') => $recordRequest->course,
                            __('Enrolment status') => $recordRequest->enrolment_status->label(),
                            __('Email') => $recordRequest->email,
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

                @foreach ($sections as $heading => $rows)
                    <section>
                        <h3 class="text-base font-semibold text-gray-900">{{ $heading }}</h3>
                        <dl class="mt-3 divide-y divide-gray-100 text-sm">
                            @foreach ($rows as $label => $value)
                                <div class="grid grid-cols-3 gap-4 py-2">
                                    <dt class="text-gray-500">{{ $label }}</dt>
                                    <dd class="col-span-2 text-gray-900 whitespace-pre-line">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endforeach

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
                            <a href="{{ route('requests.release.create', $recordRequest) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
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
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-request-deletion')"
                                class="ms-auto"
                            >{{ __('Delete request') }}</x-danger-button>

                            <x-modal name="confirm-request-deletion" focusable>
                                <form method="POST" action="{{ route('requests.destroy', $recordRequest) }}" class="p-6">
                                    @csrf
                                    @method('delete')

                                    <h2 class="text-lg font-medium text-gray-900">
                                        {{ __('Delete this request?') }}
                                    </h2>

                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Request :reference will be removed from the list. This does not delete it permanently and can be recovered if needed.', ['reference' => $recordRequest->reference_no]) }}
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-danger-button class="ms-3">
                                            {{ __('Delete request') }}
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
