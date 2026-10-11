@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}@if ($required)<span class="ms-0.5 text-red-600" aria-hidden="true">*</span>@endif
</label>
