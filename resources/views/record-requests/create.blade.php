<x-public-layout>
    <div class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
        @if (session('reference_no'))
            <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-900/5">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">{{ __('Request submitted') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('We sent your reference number to :email, along with a link to cancel the request if you need to. The registrar will contact you once your document is ready.', ['email' => session('email')]) }}</p>
                <p class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-2xl font-semibold tracking-wider text-gray-900">{{ session('reference_no') }}</p>
                <div class="mt-6 flex items-center justify-center gap-6 text-sm font-medium">
                    <a href="/" class="text-indigo-600 hover:text-indigo-500">{{ __('Back to home') }}</a>
                    <a href="{{ route('record-requests.cancel.create', ['reference_no' => session('reference_no')]) }}" class="text-gray-600 hover:text-gray-900">{{ __('Cancel this request') }}</a>
                </div>
            </div>
        @else
            <a href="/" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to home') }}
            </a>

            <div class="mb-8 mt-4 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ __('Request a Student Record') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Fill in your details exactly as they appear in your school records.') }}</p>
            </div>

            <form method="POST" action="{{ route('record-requests.store') }}" class="space-y-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                @csrf

                @error('throttle')
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $message }}</div>
                @enderror

                <fieldset class="space-y-5">
                    <legend class="text-base font-semibold text-gray-900">{{ __('Student details') }}</legend>

                    <div>
                        <x-input-label for="student_no" :value="__('Student number')" />
                        <x-text-input id="student_no" class="mt-1 block w-full" type="text" name="student_no" :value="old('student_no')" required autofocus placeholder="2024-00001" />
                        <x-input-error :messages="$errors->get('student_no')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="first_name" :value="__('First name')" />
                            <x-text-input id="first_name" class="mt-1 block w-full" type="text" name="first_name" :value="old('first_name')" required autocomplete="given-name" />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="middle_name" :value="__('Middle name')" />
                            <x-text-input id="middle_name" class="mt-1 block w-full" type="text" name="middle_name" :value="old('middle_name')" autocomplete="additional-name" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" />
                            <x-text-input id="last_name" class="mt-1 block w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="course" :value="__('Course / program')" />
                            <x-select-input id="course" name="course" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select your course') }}</option>
                                @foreach (config('school.courses') as $course)
                                    <option value="{{ $course }}" @selected(old('course') === $course)>{{ $course }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('course')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="enrolment_status" :value="__('Enrolment status')" />
                            <x-select-input id="enrolment_status" name="enrolment_status" class="mt-1 block w-full" required>
                                @foreach (\App\Enums\EnrolmentStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('enrolment_status') === $status->value)>{{ __($status->label()) }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error :messages="$errors->get('enrolment_status')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input
                                id="email"
                                class="mt-1 block w-full"
                                type="email"
                                name="email"
                                :value="old('email')"
                                required
                                autocomplete="email"
                                placeholder="yourname@gmail.com"
                                pattern="(?=.{6,30}@)[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*@gmail\.com"
                                title="{{ __('Enter a valid Gmail address, e.g. juan.delacruz@gmail.com') }}"
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ __('A Gmail address is required (e.g. juan.delacruz@gmail.com). We email your confirmation here.') }}</p>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="contact_no" :value="__('Contact number')" />
                            <x-text-input id="contact_no" class="mt-1 block w-full" type="tel" name="contact_no" :value="old('contact_no')" required autocomplete="tel" />
                            <x-input-error :messages="$errors->get('contact_no')" class="mt-2" />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="text-base font-semibold text-gray-900">{{ __('Record requested') }}</legend>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-input-label for="document_type" :value="__('Document')" />
                            <x-select-input id="document_type" name="document_type" class="mt-1 block w-full" required>
                                <option value="">{{ __('Select a document') }}</option>
                                @foreach (\App\Enums\DocumentType::cases() as $document)
                                    <option value="{{ $document->value }}" @selected(old('document_type') === $document->value)>{{ __($document->label()) }} — {{ $document->formattedFee() }}</option>
                                @endforeach
                            </x-select-input>
                            <p class="mt-1 text-xs text-gray-500">{{ __('Fees are per copy.') }}</p>
                            <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="copies" :value="__('Copies')" />
                            <x-text-input id="copies" class="mt-1 block w-full" type="number" name="copies" min="1" max="10" :value="old('copies', 1)" required />
                            <x-input-error :messages="$errors->get('copies')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="purpose" :value="__('Purpose of request')" />
                        <textarea id="purpose" name="purpose" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('purpose') }}</textarea>
                        <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                    </div>

                    <div class="border-t border-gray-200 pt-5 space-y-5">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Authorized representative') }}</h3>

                        <div>
                            <x-input-label for="designated_representative_name" :value="__('Name (optional)')" />
                            <x-text-input id="designated_representative_name" class="mt-1 block w-full" type="text" name="designated_representative_name" :value="old('designated_representative_name')" placeholder="{{ __('Name of the person who will claim this on your behalf, if not you') }}" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('If someone else will pick this up for you, name them here. Registrar staff will check their ID against this name on release.') }}</p>
                            <x-input-error :messages="$errors->get('designated_representative_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="designated_representative_id_type" :value="__('Valid ID your representative will show')" />
                            <x-select-input id="designated_representative_id_type" name="designated_representative_id_type" class="mt-1 block w-full">
                                <option value="">{{ __('Select a valid ID type') }}</option>
                                @foreach (\App\Enums\ValidIdType::cases() as $idType)
                                    <option value="{{ $idType->value }}" @selected(old('designated_representative_id_type') === $idType->value)>{{ $idType->label() }}</option>
                                @endforeach
                            </x-select-input>
                            <p class="mt-1 text-xs text-gray-500">{{ __('Required if you named a representative above. This is what they must present to the registrar when claiming the document.') }}</p>
                            <x-input-error :messages="$errors->get('designated_representative_id_type')" class="mt-2" />
                        </div>
                    </div>
                </fieldset>

                <x-primary-button class="w-full justify-center">
                    {{ __('Submit request') }}
                </x-primary-button>

                <p class="text-center text-sm text-gray-500">
                    {{ __('Already submitted a request?') }}
                    <a href="{{ route('record-requests.cancel.create') }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ __('Cancel it') }}</a>
                </p>
            </form>
        @endif
    </div>
</x-public-layout>
