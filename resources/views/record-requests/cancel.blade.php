<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-900/5">
            @if ($recordRequest->isCancellable())
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Cancel this request?') }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $recordRequest->document_type->label() }} &middot; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}
                </p>
                <p class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-lg font-semibold tracking-wider text-gray-900">{{ $recordRequest->reference_no }}</p>
                <p class="mt-4 text-sm text-gray-600">{{ __('Registrar staff will review and confirm your cancellation.') }}</p>

                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf
                    <x-danger-button class="w-full justify-center">{{ __('Yes, request cancellation') }}</x-danger-button>
                </form>

                <a href="/" class="mt-4 inline-block text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('No, keep it') }}</a>
            @elseif ($recordRequest->hasMissedCancellationWindow())
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Cancellation window has passed') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request :reference can no longer be cancelled online — it is more than :days days old. Please contact the registrar directly.', ['reference' => $recordRequest->reference_no, 'days' => config('school.cancellation_window_days')]) }}</p>
            @elseif ($recordRequest->status === \App\Enums\RequestStatus::CancellationRequested)
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 text-yellow-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Cancellation requested') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request :reference is awaiting registrar staff to confirm the cancellation.', ['reference' => $recordRequest->reference_no]) }}</p>
            @elseif ($recordRequest->status === \App\Enums\RequestStatus::Cancelled)
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Request cancelled') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request :reference has been cancelled.', ['reference' => $recordRequest->reference_no]) }}</p>
                <a href="{{ route('record-requests.create') }}" class="mt-6 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('Make a new request') }}</a>
            @else
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('This request can no longer be cancelled here') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request :reference is already :status.', ['reference' => $recordRequest->reference_no, 'status' => mb_strtolower($recordRequest->status->label())]) }}</p>
            @endif
        </div>
    </div>
</x-public-layout>
