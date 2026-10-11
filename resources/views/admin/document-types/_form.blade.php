@props(['documentType' => null])

<div>
    <x-input-label required for="name" :value="__('Document name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $documentType?->name)" required autofocus autocomplete="off" />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label required for="fee" :value="__('Fee per copy (₱)')" />
    <x-text-input id="fee" name="fee" type="number" min="0" max="100000" step="1" inputmode="numeric" class="mt-1 block w-full" :value="old('fee', $documentType?->fee)" required />
    <p class="mt-1 text-xs text-gray-500">{{ __('Requests already submitted keep the fee they were submitted with.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('fee')" />
</div>

<div>
    <x-input-label required for="processing_days" :value="__('Processing time (working days)')" />
    <x-text-input id="processing_days" name="processing_days" type="number" min="1" max="60" step="1" inputmode="numeric" class="mt-1 block w-full" :value="old('processing_days', $documentType?->processing_days)" required />
    <p class="mt-1 text-xs text-gray-500">{{ __('Monday to Friday. Used for the estimated ready date shown to requesters.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('processing_days')" />
</div>

<div>
    <label for="is_active" class="inline-flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $documentType?->is_active ?? true))>
        <span class="text-sm font-medium text-gray-700">{{ __('Offered on the request form') }}</span>
    </label>
    <p class="mt-1 text-xs text-gray-500">{{ __('Switch this off to stop new requests for the document. Existing requests are not affected.') }}</p>
</div>
