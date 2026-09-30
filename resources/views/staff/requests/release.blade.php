<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Release :reference', ['reference' => $recordRequest->reference_no]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="text-sm text-gray-600">
                    {{ __('Record who is claiming this document, and when they can pick it up.') }}
                </p>

                @if ($recordRequest->designated_representative_name)
                    <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('The requester authorized :name to claim this on their behalf.', ['name' => $recordRequest->designated_representative_name]) }}
                        @if ($recordRequest->designated_representative_id_type)
                            {{ __('Ask to see their :idType before releasing.', ['idType' => $recordRequest->designated_representative_id_type->label()]) }}
                        @endif
                    </p>
                @endif

                <form method="POST" action="{{ route('requests.release.store', $recordRequest) }}" class="mt-6 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="representative_name" :value="__('Representative name')" />
                        <x-text-input id="representative_name" name="representative_name" type="text" class="mt-1 block w-full" :value="old('representative_name', $recordRequest->designated_representative_name)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('representative_name')" />
                    </div>

                    <div>
                        <x-input-label for="claim_available_at" :value="__('Available to claim from')" />
                        <x-text-input id="claim_available_at" name="claim_available_at" type="datetime-local" class="mt-1 block w-full" :value="old('claim_available_at', now()->format('Y-m-d\TH:i'))" required />
                        <p class="mt-1 text-xs text-gray-500">{{ __('The requester will be emailed this date and time as when they, or their representative, can come claim the document.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('claim_available_at')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Release document') }}</x-primary-button>
                        <a href="{{ route('requests.show', $recordRequest) }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
