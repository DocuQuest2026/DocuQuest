<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 via-white to-white">
        <div class="pointer-events-none absolute -top-24 left-1/2 h-96 w-[48rem] -translate-x-1/2 rounded-full bg-indigo-100/60 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 sm:py-24 lg:grid-cols-2">
            <div class="text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                    {{ __('Office of the Registrar') }}
                </p>

                <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-gray-900 sm:text-5xl">
                    {{ __('Request your student records online') }}
                </h1>

                <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-gray-600 lg:mx-0">
                    {{ __('Request your transcript, certificates, and other academic records online. No queues, no paperwork.') }}
                </p>

                <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    <a href="{{ route('record-requests.create') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                        {{ __('Request Now') }}
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a href="{{ route('record-requests.status.create') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-7 py-3.5 text-base font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                        {{ __('Check Status') }}
                    </a>
                </div>

                <p class="mt-5 text-sm text-gray-500">
                    {{ __('Changed your mind?') }}
                    <a href="{{ route('record-requests.cancel.create') }}" class="font-medium text-indigo-600 underline underline-offset-2 hover:text-indigo-800">{{ __('Cancel a request') }}</a>
                </p>
            </div>

            {{-- Example of what a request looks like once submitted --}}
            <div class="mx-auto w-full max-w-md lg:max-w-none">
                <div class="rounded-2xl bg-white p-6 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Reference number') }}</p>
                            <p class="mt-1 font-mono text-lg font-semibold text-gray-900">REQ-A1B2C3D4</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">{{ __('Example') }}</span>
                    </div>

                    <p class="mt-4 text-sm font-medium text-gray-700">{{ \App\Enums\DocumentType::TranscriptOfRecords->label() }}</p>

                    <ol class="mt-6 space-y-5">
                        @foreach ([
                            ['Request submitted', 'You get a reference number right away.', 'done'],
                            ['Approved by the registrar', 'Your request is reviewed and approved.', 'current'],
                            ['Ready to claim', 'The registrar contacts you when it is ready.', 'upcoming'],
                        ] as [$title, $description, $state])
                            <li class="flex gap-4">
                                @if ($state === 'done')
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </span>
                                @elseif ($state === 'current')
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600 bg-indigo-50">
                                        <span class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
                                    </span>
                                @else
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white"></span>
                                @endif

                                <div>
                                    <p class="text-sm font-semibold {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-900' }}">{{ __($title) }}</p>
                                    <p class="mt-0.5 text-sm {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-600' }}">{{ __($description) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- Documents --}}
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Records you can request') }}</h2>
            <p class="mt-3 text-gray-600">{{ __('Choose the document you need on the request form. Fees are charged per copy.') }}</p>
        </div>

        <ul class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (\App\Enums\DocumentType::cases() as $documentType)
                <li class="group flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 transition hover:shadow-md hover:ring-indigo-200">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </span>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ __($documentType->label()) }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __(':fee per copy', ['fee' => $documentType->formattedFee()]) }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- How it works --}}
    <section class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('How it works') }}</h2>
                <p class="mt-3 text-gray-600">{{ __('Three simple steps from request to pickup.') }}</p>
            </div>

            <ol class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach ([
                    ['Fill out the form', 'Enter your student details and choose the record you need.'],
                    ['Get a reference number', 'Keep it to follow up on your request with the registrar.'],
                    ['Claim your record', 'The registrar will contact you once your document is ready.'],
                ] as $index => [$title, $description])
                    <li class="relative text-center">
                        @if (! $loop->last)
                            <div class="absolute left-1/2 top-6 hidden h-0.5 w-full bg-gradient-to-r from-indigo-200 to-indigo-100 md:block" aria-hidden="true"></div>
                        @endif

                        <span class="relative mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white shadow-md shadow-indigo-600/30 ring-8 ring-white">{{ $index + 1 }}</span>
                        <h3 class="mt-5 font-semibold text-gray-900">{{ __($title) }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-gray-600">{{ __($description) }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Good to know --}}
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-20">
        <div class="grid gap-5 md:grid-cols-3">
            <div class="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="font-semibold text-gray-900">{{ __('Keep your reference number') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __('You need it to check the status of your request or to cancel it.') }}</p>
                <div class="mt-auto pt-5">
                    <a href="{{ route('record-requests.status.create') }}" class="group inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Check a request') }}
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>

            <div class="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="font-semibold text-gray-900">{{ __('Cancel :window', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __('You can ask to cancel a pending request :window of submitting it. Once the registrar approves it, it can no longer be cancelled. After that, please contact the registrar.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}</p>
                <div class="mt-auto pt-5">
                    <a href="{{ route('record-requests.cancel.create') }}" class="group inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Cancel a request') }}
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>

            <div class="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="font-semibold text-gray-900">{{ __('Your request history') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __('Get a code by email to see all the requests made with your email address.') }}</p>
                <div class="mt-auto pt-5">
                    <a href="{{ route('record-requests.history.create') }}" class="group inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('View my requests') }}
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Final call to action --}}
    <section class="px-6 pb-14">
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-700 px-6 py-6 shadow-lg shadow-indigo-600/20 sm:px-8">
            <div class="pointer-events-none absolute -right-10 -top-16 h-40 w-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>

            <div class="relative flex flex-col items-center gap-4 text-center md:flex-row md:justify-between md:text-left">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-white">{{ __('Ready to request a record?') }}</h2>
                    <p class="mt-1 text-sm text-indigo-100">{{ __('It only takes a few minutes. No queues, no paperwork.') }}</p>
                </div>

                <div class="flex shrink-0 flex-col items-center gap-2 sm:flex-row sm:gap-3">
                    <a href="{{ route('record-requests.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-700">
                        {{ __('Request Now') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a href="{{ route('record-requests.status.create') }}" class="inline-flex items-center justify-center rounded-lg border-2 border-white bg-white/10 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-white hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-700">
                        {{ __('Check Status') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
