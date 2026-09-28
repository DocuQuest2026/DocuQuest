@props(['profile' => null])

@php
    $initialStatus = old('enrolment_status', $profile?->enrolment_status?->value ?? \App\Enums\EnrolmentStatus::Enrolled->value);
@endphp

<div x-data="{ status: @js($initialStatus) }" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="first_name" :value="__('First name')" />
            <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $profile?->first_name)" required autocomplete="given-name" />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="middle_name" :value="__('Middle name')" />
            <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name', $profile?->middle_name)" autocomplete="additional-name" />
            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="last_name" :value="__('Last name')" />
            <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $profile?->last_name)" required autocomplete="family-name" />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="course" :value="__('Course / program')" />
        <x-text-input id="course" class="block mt-1 w-full" type="text" name="course" :value="old('course', $profile?->course)" required />
        <x-input-error :messages="$errors->get('course')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="enrolment_status" :value="__('Enrolment status')" />
            <x-select-input id="enrolment_status" name="enrolment_status" class="block mt-1 w-full" x-model="status" required>
                @foreach (\App\Enums\EnrolmentStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ __($status->label()) }}</option>
                @endforeach
            </x-select-input>
            <x-input-error :messages="$errors->get('enrolment_status')" class="mt-2" />
        </div>

        <div x-show="status === '{{ \App\Enums\EnrolmentStatus::Enrolled->value }}'">
            <x-input-label for="year_level" :value="__('Year level')" />
            <x-select-input id="year_level" name="year_level" class="block mt-1 w-full" x-bind:disabled="status !== '{{ \App\Enums\EnrolmentStatus::Enrolled->value }}'">
                <option value="">{{ __('Select year level') }}</option>
                @foreach (range(1, 6) as $level)
                    <option value="{{ $level }}" @selected((int) old('year_level', $profile?->year_level) === $level)>{{ $level }}</option>
                @endforeach
            </x-select-input>
            <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="contact_no" :value="__('Contact number')" />
        <x-text-input id="contact_no" class="block mt-1 w-full" type="tel" name="contact_no" :value="old('contact_no', $profile?->contact_no)" required autocomplete="tel" />
        <x-input-error :messages="$errors->get('contact_no')" class="mt-2" />
    </div>
</div>
