<x-public-layout>
    <section class="mx-auto max-w-3xl px-6 py-20 text-center sm:py-28">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ __('Office of the Registrar') }}</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
            {{ __('Online Request of Student Record') }}
        </h1>
        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-gray-600">
            {{ __('Request your transcript, certificates, and other academic records online. No queues, no paperwork.') }}
        </p>

        <div class="mt-10">
            <a href="{{ route('record-requests.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('Request Now') }}
            </a>
            <p class="mt-4 text-sm text-gray-500">
                <a href="{{ route('record-requests.cancel.create') }}" class="hover:text-gray-700 underline">{{ __('Cancel a request') }}</a>
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 pb-24">
        <h2 class="text-center text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('How it works') }}</h2>

        <ol class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
            @foreach ([
                ['Fill out the form', 'Enter your student details and choose the record you need.'],
                ['Get a reference number', 'Keep it to follow up on your request with the registrar.'],
                ['Claim your record', 'The registrar will contact you once your document is ready.'],
            ] as $index => [$title, $description])
                <li class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-600">{{ $index + 1 }}</span>
                    <h3 class="mt-4 font-semibold text-gray-900">{{ __($title) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __($description) }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</x-public-layout>
