<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-900/5">
            @if ($recordRequest->isCancellable())
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Cancel this request?') }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $recordRequest->document_type->label() }} &middot; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}
                </p>
                <p class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-lg font-semibold tracking-wider text-gray-900">{{ $recordRequest->reference_no }}</p>

                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf
                    <x-danger-button class="w-full justify-center">{{ __('Yes, cancel my request') }}</x-danger-button>
                </form>

                <a href="/" class="mt-4 inline-block text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('No, keep it') }}</a>
            @else
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Request cancelled') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request :reference has been cancelled.', ['reference' => $recordRequest->reference_no]) }}</p>
                <a href="{{ route('record-requests.create') }}" class="mt-6 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('Make a new request') }}</a>
            @endif
        </div>
    </div>
</x-public-layout>
