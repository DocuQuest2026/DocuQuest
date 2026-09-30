<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <a href="/" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            {{ __('Back to home') }}
        </a>

        <div class="mb-8 mt-4 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Check request status') }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ __('Enter the reference number you were emailed to see where your request stands.') }}</p>
        </div>

        <form method="POST" action="{{ route('record-requests.status.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
            @csrf

            @error('throttle')
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
            @enderror

            <div>
                <x-input-label for="reference_no" :value="__('Reference number')" />
                <x-text-input id="reference_no" class="mt-1 block w-full" type="text" name="reference_no" :value="old('reference_no')" required autofocus placeholder="REQ-AB12CD34" />
                <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center">
                {{ __('Check status') }}
            </x-primary-button>
        </form>

        @if ($recordRequest)
            @php
                $statusCopy = [
                    \App\Enums\RequestStatus::Pending->value => ['label' => __('Being processed'), 'classes' => 'bg-yellow-100 text-yellow-800'],
                    \App\Enums\RequestStatus::Approved->value => ['label' => __('Approved — being prepared'), 'classes' => 'bg-blue-100 text-blue-800'],
                    \App\Enums\RequestStatus::Released->value => ['label' => __('Ready to claim'), 'classes' => 'bg-green-100 text-green-800'],
                    \App\Enums\RequestStatus::Rejected->value => ['label' => __('Rejected'), 'classes' => 'bg-red-100 text-red-800'],
                    \App\Enums\RequestStatus::CancellationRequested->value => ['label' => __('Cancellation requested'), 'classes' => 'bg-orange-100 text-orange-800'],
                    \App\Enums\RequestStatus::Cancelled->value => ['label' => __('Cancelled'), 'classes' => 'bg-gray-200 text-gray-700'],
                ][$recordRequest->status->value];
            @endphp

            <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <span class="font-semibold text-gray-900">{{ $recordRequest->reference_no }}</span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusCopy['classes'] }}">{{ $statusCopy['label'] }}</span>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                        <dt class="text-gray-500">{{ __('Document') }}</dt>
                        <dd class="text-gray-900">{{ $recordRequest->document_type->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                        <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                        <dd class="text-gray-900">{{ $recordRequest->created_at->format('M j, Y') }}</dd>
                    </div>
                    @if ($recordRequest->status === \App\Enums\RequestStatus::Released && $recordRequest->release?->claim_available_at)
                        <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                            <dt class="text-gray-500">{{ __('Available to claim from') }}</dt>
                            <dd class="text-gray-900">{{ $recordRequest->release->claim_available_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
</x-public-layout>
