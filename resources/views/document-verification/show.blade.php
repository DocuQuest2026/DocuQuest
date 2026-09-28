<x-public-layout>
    <div class="mx-auto max-w-md px-6 py-12 sm:py-16">
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-900/5">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Genuine document') }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('This document was released by the Office of the Registrar and is genuine.') }}
            </p>

            <dl class="mt-6 space-y-3 text-left text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">{{ __('Reference') }}</dt>
                    <dd class="font-semibold text-gray-900">{{ $release->recordRequest->reference_no }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">{{ __('Document') }}</dt>
                    <dd class="text-gray-900">{{ $release->recordRequest->document_type->label() }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">{{ __('Requester') }}</dt>
                    <dd class="text-gray-900">{{ $release->recordRequest->fullName() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('Released') }}</dt>
                    <dd class="text-gray-900">{{ $release->released_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-public-layout>
