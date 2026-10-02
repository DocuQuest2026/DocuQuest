<x-public-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-gray-50">
        <div class="mx-auto max-w-xl px-6 py-10 sm:py-14">
            <a href="/" class="group inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to home') }}
            </a>

            <div class="mb-8 mt-4 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('Check request status') }}</h1>
                <p class="mt-2 text-gray-600">{{ __('Enter the reference number you were emailed to see where your request stands.') }}</p>
            </div>

            <div class="space-y-6">
                @if ($recordRequest)
                    @php
                        $status = $recordRequest->status;
                        $claimedAt = $status === \App\Enums\RequestStatus::Released ? $recordRequest->release?->claimed_at : null;

                        $badge = $recordRequest->requesterStatus();

                        $message = match (true) {
                            $claimedAt !== null => ['tone' => 'green', 'text' => __('You claimed this document on :date.', ['date' => $claimedAt->format('M j, Y g:i A')])],
                            $status === \App\Enums\RequestStatus::Pending => ['tone' => 'amber', 'text' => __('The registrar is processing your request. This takes up to :days days, depending on the document requested.', ['days' => config('school.processing_days')])],
                            $status === \App\Enums\RequestStatus::Approved => ['tone' => 'blue', 'text' => __('Your request is approved and your document is being prepared. It can no longer be cancelled.')],
                            $status === \App\Enums\RequestStatus::Released => ['tone' => 'green', 'text' => __('Your document is ready. Bring a valid ID, or have your named representative bring theirs.')],
                            $status === \App\Enums\RequestStatus::Rejected => ['tone' => 'red', 'text' => __('The registrar could not approve this request. Please contact the registrar for details.')],
                            $status === \App\Enums\RequestStatus::CancellationRequested => ['tone' => 'orange', 'text' => __('You asked to cancel this request. The registrar will confirm it.')],
                            default => ['tone' => 'gray', 'text' => __('This request was cancelled.')],
                        };

                        $tones = [
                            'amber' => 'bg-amber-50 text-amber-900 ring-amber-200',
                            'blue' => 'bg-blue-50 text-blue-900 ring-blue-200',
                            'green' => 'bg-green-50 text-green-900 ring-green-200',
                            'red' => 'bg-red-50 text-red-900 ring-red-200',
                            'orange' => 'bg-orange-50 text-orange-900 ring-orange-200',
                            'gray' => 'bg-gray-100 text-gray-800 ring-gray-200',
                        ];

                        $progress = match ($status) {
                            \App\Enums\RequestStatus::Pending => ['done', 'current', 'upcoming'],
                            \App\Enums\RequestStatus::Approved => ['done', 'done', 'current'],
                            \App\Enums\RequestStatus::Released => ['done', 'done', 'done'],
                            default => null,
                        };
                    @endphp

                    <div class="rounded-2xl bg-white p-6 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-8">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Reference number') }}</p>
                                <p class="mt-1 font-mono text-xl font-bold tracking-wider text-gray-900">{{ $recordRequest->reference_no }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                        </div>

                        <div class="mt-5 rounded-xl p-4 text-sm leading-relaxed ring-1 {{ $tones[$message['tone']] }}">
                            <p>{{ $message['text'] }}</p>

                            @if ($recordRequest->isCancellable())
                                <p class="mt-2">
                                    {{ __('You can still cancel it :window of submitting.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}
                                    <a href="{{ route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]) }}" class="font-semibold underline underline-offset-2">{{ __('Cancel this request') }}</a>
                                </p>
                            @endif
                        </div>

                        <dl class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Document') }}</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $recordRequest->document_type->label() }}
                                    <span class="whitespace-nowrap font-normal text-gray-500">&times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                                </dd>
                            </div>
                            @if (! in_array($status, [\App\Enums\RequestStatus::Rejected, \App\Enums\RequestStatus::Cancelled], true))
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-gray-500">{{ $claimedAt !== null ? __('Total fee') : __('Amount to pay') }}</dt>
                                    <dd class="text-right">
                                        <span class="block text-base font-bold text-indigo-600">{{ $recordRequest->formattedTotalFee() }}</span>
                                        <span class="block text-xs text-gray-500">{{ $recordRequest->document_type->formattedFee() }} &times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                                <dd class="font-medium text-gray-900">{{ $recordRequest->created_at->format('M j, Y') }}</dd>
                            </div>
                            @if ($status === \App\Enums\RequestStatus::Released && $recordRequest->release?->claim_available_at)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Available to claim from') }}</dt>
                                    <dd class="text-right font-medium text-gray-900">{{ $recordRequest->release->claim_available_at->format('M j, Y g:i A') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if ($progress)
                            <ol class="mt-6 space-y-3 border-t border-gray-100 pt-5">
                                @foreach ([
                                    ['Request submitted', 'We received your request.', null, null],
                                    ['Approved by the registrar', 'Your request is reviewed and approved.', 'The registrar is processing for approval', __('This takes up to :days days, depending on the document.', ['days' => config('school.processing_days')])],
                                    ['Ready to claim', 'Your document is ready to be picked up.', null, null],
                                ] as $index => [$title, $description, $titleWhileCurrent, $descriptionWhileCurrent])
                                    @php
                                        $state = $progress[$index];

                                        if ($state === 'current' && $titleWhileCurrent !== null) {
                                            $title = $titleWhileCurrent;
                                            $description = $descriptionWhileCurrent;
                                        }
                                    @endphp

                                    <li class="flex gap-3">
                                        @if ($state === 'done')
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            </span>
                                        @elseif ($state === 'current')
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600 bg-indigo-50">
                                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                            </span>
                                        @else
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white"></span>
                                        @endif

                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold leading-5 {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-900' }}">{{ __($title) }}</p>
                                            <p class="text-xs leading-5 {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-500' }}">{{ __($description) }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('record-requests.status.store') }}" class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8" x-data="{ checking: false }" x-on:submit="checking = true" x-on:pageshow.window="checking = false">
                    @csrf

                    @error('throttle')
                        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                    @enderror

                    <div>
                        <x-input-label for="reference_no" :value="$recordRequest ? __('Check another reference number') : __('Reference number')" />

                        <div class="relative mt-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            <x-text-input
                                id="reference_no"
                                class="block w-full rounded-lg py-2.5 pl-10 font-mono uppercase tracking-wider placeholder:normal-case placeholder:tracking-normal"
                                type="text"
                                name="reference_no"
                                :value="old('reference_no')"
                                required
                                :autofocus="! $recordRequest"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                placeholder="REQ-AB12CD34"
                            />
                        </div>

                        <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
                    </div>

                    <x-primary-button class="w-full justify-center rounded-xl py-3.5 text-base" x-bind:disabled="checking">
                        <span x-show="! checking">{{ __('Check status') }}</span>
                        <span x-show="checking" x-cloak>{{ __('Checking…') }}</span>
                    </x-primary-button>

                    <p class="text-center text-sm text-gray-600">
                        {{ __('Lost your reference number?') }}
                        <a href="{{ route('record-requests.history.create') }}" class="font-medium text-indigo-600 underline underline-offset-2 hover:text-indigo-800">{{ __('View all your requests by email') }}</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
