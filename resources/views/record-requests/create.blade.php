<x-public-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-gray-50">
        <div class="mx-auto max-w-2xl px-6 py-10 sm:py-14">
            @if (session('reference_no'))
                {{-- Confirmation --}}
                @php
                    $submitted = session('submitted_requests') ?: [['reference_no' => session('reference_no'), 'document' => null, 'copies' => null]];
                @endphp
                <div class="mx-auto max-w-xl rounded-2xl bg-white p-8 text-center shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-10"
                     x-data="{ copied: null, copy(reference) { navigator.clipboard?.writeText(reference).then(() => { this.copied = reference; setTimeout(() => this.copied = null, 2000); }); } }">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>

                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">{{ __('Request Submitted') }}</h1>
                    @if (count($submitted) > 1)
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __('We sent your :count reference numbers to :email, along with a link to cancel each request if you need to. The registrar will contact you once each document is ready.', ['count' => count($submitted), 'email' => session('email')]) }}</p>

                        <div class="mt-6 space-y-3">
                            @foreach ($submitted as $item)
                                <div class="rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/60 px-4 py-4 text-left sm:flex sm:items-center sm:justify-between sm:gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ __($item['document']) }} &times; {{ $item['copies'] }}</p>
                                        <p class="mt-1 font-mono text-xl font-bold tracking-wider text-gray-900">{{ $item['reference_no'] }}</p>
                                    </div>
                                    <div class="mt-3 flex shrink-0 items-center gap-3 sm:mt-0">
                                        <button type="button" x-on:click="copy(@js($item['reference_no']))" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <span x-text="copied === @js($item['reference_no']) ? @js(__('Copied!')) : @js(__('Copy'))"></span>
                                        </button>
                                        <a href="{{ route('record-requests.cancel.create', ['reference_no' => $item['reference_no']]) }}" class="text-sm font-medium text-gray-600 underline underline-offset-2 hover:text-gray-900">{{ __('Cancel') }}</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ __('We sent your reference number to :email, along with a link to cancel the request if you need to. The registrar will contact you once your document is ready.', ['email' => session('email')]) }}</p>

                        <div class="mt-6 rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/60 px-4 py-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ __('Your reference number') }}</p>
                            <p class="mt-1 font-mono text-3xl font-bold tracking-wider text-gray-900">{{ session('reference_no') }}</p>
                            <button type="button" x-on:click="copy(@js(session('reference_no')))" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                <span x-text="copied === @js(session('reference_no')) ? @js(__('Copied!')) : @js(__('Copy reference number'))"></span>
                            </button>
                        </div>
                    @endif

                    <ol class="mt-8 space-y-4 text-left">
                        @foreach ([
                            ['The registrar reviews your request', 'You can follow its progress any time with your reference number.', false],
                            ['Reminder: no cancelling once approved', __('Once your request is approved, you can no longer cancel it. Until then, you can still cancel it :window of submitting.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]), true],
                            ['You are contacted when it is ready', 'The registrar reaches out once your document can be claimed.', false],
                            ['Claim your document', 'Bring a valid ID, or have your named representative bring theirs.', false],
                        ] as $index => [$title, $description, $isReminder])
                            <li class="flex gap-3 {{ $isReminder ? '-mx-3 rounded-xl bg-amber-50 px-3 py-3 ring-1 ring-amber-200' : '' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $isReminder ? 'bg-amber-200 text-amber-900' : 'bg-indigo-100 text-indigo-700' }}">{{ $index + 1 }}</span>
                                <div>
                                    <p class="text-sm font-semibold {{ $isReminder ? 'text-amber-900' : 'text-gray-900' }}">{{ __($title) }}</p>
                                    <p class="text-sm {{ $isReminder ? 'text-amber-800' : 'text-gray-600' }}">{{ __($description) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="{{ route('record-requests.status.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Check Status') }}</a>
                        <a href="/" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Back to home') }}</a>
                    </div>

                    @if (count($submitted) === 1)
                        <p class="mt-5 text-sm text-gray-500">
                            <a href="{{ route('record-requests.cancel.create', ['reference_no' => session('reference_no')]) }}" class="font-medium text-gray-600 underline underline-offset-2 hover:text-gray-900">{{ __('Cancel this request') }}</a>
                        </p>
                    @endif
                </div>
            @else
                @php
                    $documents = collect(\App\Enums\DocumentType::cases())->mapWithKeys(fn ($document) => [
                        $document->value => ['label' => __($document->label()), 'fee' => $document->fee()],
                    ]);
                    $oldDocuments = (array) old('documents', []);
                    $chosenDocuments = collect(\App\Enums\DocumentType::cases())->mapWithKeys(fn ($document) => [
                        $document->value => array_key_exists($document->value, $oldDocuments),
                    ]);
                    $copiesByDocument = collect(\App\Enums\DocumentType::cases())->mapWithKeys(fn ($document) => [
                        $document->value => (int) data_get($oldDocuments, $document->value.'.copies', 1),
                    ]);
                    $hasRepresentative = filled(old('designated_representative_name'))
                        || $errors->has('designated_representative_name')
                        || $errors->has('designated_representative_id_type');
                @endphp

                <a href="/" class="group inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-indigo-600 hover:text-white hover:ring-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('Back to home') }}
                </a>

                <div class="mb-8 mt-4 text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ __('Request a Student Record') }}</h1>
                    <p class="mt-2 text-gray-600">{{ __('Fill in your details exactly as they appear in your school records.') }}</p>
                </div>

                <div
                    class="space-y-8"
                    x-data="{
                        documents: @js($documents),
                        chosen: @js($chosenDocuments),
                        copies: @js($copiesByDocument),
                        hasRepresentative: @js($hasRepresentative),
                        submitting: false,
                        confirming: false,
                        documentsError: false,
                        review: {},
                        init() {
                            this.$watch('chosen', () => { this.documentsError = false; });
                        },
                        openReview(form) {
                            if (! form.reportValidity()) {
                                return;
                            }

                            if (this.items.length === 0) {
                                this.documentsError = true;
                                this.$refs.documentsList.scrollIntoView({ behavior: 'smooth', block: 'center' });

                                return;
                            }

                            const field = (name) => (form.elements[name]?.value ?? '').trim();
                            const option = (name) => (form.elements[name]?.selectedOptions?.[0]?.text ?? '').trim();

                            this.review = {
                                name: [field('first_name'), field('middle_name'), field('last_name')].filter(Boolean).join(' '),
                                studentNo: field('student_no'),
                                course: option('course'),
                                email: field('email'),
                                contactNo: field('contact_no'),
                                documents: this.items.map((item) => ({ label: item.label, copies: item.copies, subtotal: this.peso(item.subtotal) })),
                                total: this.peso(this.total),
                                representative: this.hasRepresentative ? field('designated_representative_name') : '',
                            };

                            this.confirming = true;
                            this.$nextTick(() => this.$refs.confirmButton.focus());
                        },
                        confirmSubmit() {
                            this.confirming = false;
                            this.submitting = true;
                            this.$refs.form.submit();
                        },
                        quantity(type) { return Math.min(Math.max(parseInt(this.copies[type]) || 1, 1), 10); },
                        get items() {
                            return Object.entries(this.documents)
                                .filter(([type]) => this.chosen[type])
                                .map(([type, document]) => ({ type, label: document.label, fee: document.fee, copies: this.quantity(type), subtotal: document.fee * this.quantity(type) }));
                        },
                        get total() { return this.items.reduce((sum, item) => sum + item.subtotal, 0); },
                        peso(amount) { return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                    }"
                    x-on:pageshow.window="submitting = false; confirming = false"
                >
                    <form method="POST" action="{{ route('record-requests.store') }}" class="space-y-6" x-ref="form" x-on:submit.prevent="openReview($event.target)">
                        @csrf

                        @error('throttle')
                            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</div>
                        @enderror

                        {{-- 1. Student details --}}
                        <fieldset class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                            <legend class="sr-only">{{ __('Student details') }}</legend>

                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white" aria-hidden="true">1</span>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Student details') }}</h2>
                                    <p class="text-sm text-gray-500">{{ __('Who is this record for?') }}</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <x-input-label for="student_no" :value="__('Student number')" />
                                    <x-text-input id="student_no" class="mt-1 block w-full rounded-lg py-2.5" type="text" name="student_no" :value="old('student_no')" required autofocus placeholder="2024-00001" />
                                    <x-input-error :messages="$errors->get('student_no')" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <x-input-label for="first_name" :value="__('First name')" />
                                        <x-text-input id="first_name" class="mt-1 block w-full rounded-lg py-2.5" type="text" name="first_name" :value="old('first_name')" required autocomplete="given-name" title="{{ __('Letters only, no numbers.') }}" x-on:input="$el.value = $el.value.replace(/\p{N}/gu, '')" />
                                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="middle_name" :value="__('Middle name')" />
                                        <x-text-input id="middle_name" class="mt-1 block w-full rounded-lg py-2.5" type="text" name="middle_name" :value="old('middle_name')" autocomplete="additional-name" title="{{ __('Letters only, no numbers.') }}" x-on:input="$el.value = $el.value.replace(/\p{N}/gu, '')" />
                                        <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="last_name" :value="__('Last name')" />
                                        <x-text-input id="last_name" class="mt-1 block w-full rounded-lg py-2.5" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" title="{{ __('Letters only, no numbers.') }}" x-on:input="$el.value = $el.value.replace(/\p{N}/gu, '')" />
                                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="course" :value="__('Course / program')" />
                                        <x-select-input id="course" name="course" class="mt-1 block w-full rounded-lg py-2.5" required>
                                            <option value="">{{ __('Select your course') }}</option>
                                            @foreach (config('school.courses') as $course)
                                                <option value="{{ $course }}" @selected(old('course') === $course)>{{ $course }}</option>
                                            @endforeach
                                        </x-select-input>
                                        <x-input-error :messages="$errors->get('course')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="enrolment_status" :value="__('Enrolment status')" />
                                        <x-select-input id="enrolment_status" name="enrolment_status" class="mt-1 block w-full rounded-lg py-2.5" required>
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
                                            class="mt-1 block w-full rounded-lg py-2.5"
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
                                        <x-text-input
                                            id="contact_no"
                                            class="mt-1 block w-full rounded-lg py-2.5"
                                            type="tel"
                                            name="contact_no"
                                            :value="old('contact_no')"
                                            required
                                            autocomplete="tel"
                                            inputmode="numeric"
                                            minlength="11"
                                            pattern="[0-9]{11}"
                                            placeholder="09171234567"
                                            title="{{ __('Enter exactly 11 digits, numbers only (for example 09171234567).') }}"
                                            x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 11)"
                                        />
                                        <x-input-error :messages="$errors->get('contact_no')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        {{-- 2. Record requested --}}
                        <fieldset class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                            <legend class="sr-only">{{ __('Record requested') }}</legend>

                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white" aria-hidden="true">2</span>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Record requested') }}</h2>
                                    <p class="text-sm text-gray-500">{{ __('What do you need and why?') }}</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-5">
                                <div x-ref="documentsList">
                                    <p class="block text-sm font-medium text-gray-700" id="documents-label">{{ __('Documents') }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Tick every document you need. You can choose more than one. Fees are per copy.') }}</p>

                                    <div class="mt-3 space-y-3" role="group" aria-labelledby="documents-label">
                                        @foreach (\App\Enums\DocumentType::cases() as $document)
                                            <div
                                                class="rounded-xl border p-4 transition"
                                                x-bind:class="chosen['{{ $document->value }}'] ? 'border-indigo-500 bg-indigo-50/60 ring-1 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'"
                                            >
                                                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
                                                    <label class="flex min-w-[12rem] flex-1 cursor-pointer items-start gap-3">
                                                        <input
                                                            type="checkbox"
                                                            id="document_{{ $document->value }}"
                                                            class="mt-0.5 h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                            x-model="chosen['{{ $document->value }}']"
                                                            @checked($chosenDocuments[$document->value])
                                                        >
                                                        <span>
                                                            <span class="block text-sm font-semibold text-gray-900">{{ __($document->label()) }}</span>
                                                            <span class="block text-xs text-gray-500">{{ $document->formattedFee() }} {{ __('per copy') }}</span>
                                                        </span>
                                                    </label>

                                                    <div x-show="chosen['{{ $document->value }}']" x-cloak class="flex shrink-0 items-center gap-2">
                                                        <label for="copies_{{ $document->value }}" class="text-xs font-medium text-gray-600">{{ __('Copies') }}</label>
                                                        <input
                                                            type="number"
                                                            id="copies_{{ $document->value }}"
                                                            name="documents[{{ $document->value }}][copies]"
                                                            min="1"
                                                            max="10"
                                                            value="{{ $copiesByDocument[$document->value] }}"
                                                            x-model="copies['{{ $document->value }}']"
                                                            x-bind:disabled="! chosen['{{ $document->value }}']"
                                                            @disabled(! $chosenDocuments[$document->value])
                                                            required
                                                            class="w-20 rounded-lg border-gray-300 py-2 text-center shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        >
                                                    </div>
                                                </div>

                                                <x-input-error :messages="$errors->get('documents.'.$document->value.'.copies')" class="mt-2" />
                                            </div>
                                        @endforeach
                                    </div>

                                    <p x-show="documentsError" x-cloak class="mt-2 text-sm text-red-600" role="alert">{{ __('Choose at least one document.') }}</p>
                                    <x-input-error :messages="$errors->get('documents')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="purpose" :value="__('Purpose of request')" />
                                    <textarea id="purpose" name="purpose" rows="3" required placeholder="{{ __('For example: scholarship application, employment, transfer') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('purpose') }}</textarea>
                                    <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                                </div>
                            </div>
                        </fieldset>

                        {{-- 3. Authorized representative --}}
                        <fieldset class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                            <legend class="sr-only">{{ __('Authorized representative') }}</legend>

                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white" aria-hidden="true">3</span>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Authorized representative') }}</h2>
                                    <p class="text-sm text-gray-500">{{ __('Optional') }}</p>
                                </div>
                            </div>

                            <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200 transition hover:bg-gray-100">
                                <input type="checkbox" x-model="hasRepresentative" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">{{ __('Someone else will claim this for me') }}</span>
                                    <span class="block text-xs text-gray-500">{{ __('Leave this off if you will pick up the document yourself.') }}</span>
                                </span>
                            </label>

                            <div x-show="hasRepresentative" x-cloak class="mt-5 space-y-5">
                                <div>
                                    <x-input-label for="designated_representative_name" :value="__('Name (optional)')" />
                                    <x-text-input id="designated_representative_name" class="mt-1 block w-full rounded-lg py-2.5" type="text" name="designated_representative_name" :value="old('designated_representative_name')" placeholder="{{ __('Name of the person who will claim this on your behalf, if not you') }}" x-bind:disabled="! hasRepresentative" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('If someone else will pick this up for you, name them here. Registrar staff will check their ID against this name on release.') }}</p>
                                    <x-input-error :messages="$errors->get('designated_representative_name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="designated_representative_id_type" :value="__('Valid ID your representative will show')" />
                                    <x-select-input id="designated_representative_id_type" name="designated_representative_id_type" class="mt-1 block w-full rounded-lg py-2.5" x-bind:disabled="! hasRepresentative">
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

                        {{-- Fee summary --}}
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Your request') }}</h2>

                            <p x-show="items.length === 0" class="mt-4 text-sm text-gray-500">{{ __('No document chosen yet.') }}</p>

                            <ul x-show="items.length > 0" x-cloak class="mt-4 space-y-3 text-sm">
                                <template x-for="item in items" :key="item.type">
                                    <li class="flex justify-between gap-4">
                                        <span class="text-gray-700"><span class="font-medium text-gray-900" x-text="item.label"></span> <span class="text-gray-500">&times; <span x-text="item.copies"></span></span></span>
                                        <span class="shrink-0 font-medium text-gray-900" x-text="peso(item.subtotal)"></span>
                                    </li>
                                </template>
                            </ul>

                            <div class="mt-5 flex items-end justify-between border-t border-gray-100 pt-4">
                                <span class="text-sm font-medium text-gray-700">{{ __('Estimated fee') }}</span>
                                <span class="text-2xl font-bold text-indigo-600" x-text="items.length ? peso(total) : '—'"></span>
                            </div>
                        </div>

                        <x-primary-button class="w-full justify-center rounded-xl py-3.5 text-base" x-bind:disabled="submitting">
                            <span x-show="! submitting">{{ __('Submit request') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Submitting…') }}</span>
                        </x-primary-button>

                        <p class="text-center text-sm text-gray-500">
                            {{ __('Already submitted a request?') }}
                            <a href="{{ route('record-requests.cancel.create') }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ __('Cancel it') }}</a>
                        </p>
                    </form>

                    {{-- Review and confirm pop-up --}}
                    <div
                        x-show="confirming"
                        x-cloak
                        x-effect="document.body.classList.toggle('overflow-y-hidden', confirming)"
                        x-on:keydown.escape.window="confirming = false"
                        class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="confirm-submit-title"
                    >
                        <div
                            x-show="confirming"
                            x-transition.opacity
                            x-on:click="confirming = false"
                            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                            aria-hidden="true"
                        ></div>

                        <div
                            x-show="confirming"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="scale-95 opacity-0"
                            x-transition:enter-end="scale-100 opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="scale-100 opacity-100"
                            x-transition:leave-end="scale-95 opacity-0"
                            class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                        >
                            <div class="flex items-start gap-4 border-b border-gray-100 p-6">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                </span>
                                <div>
                                    <h2 id="confirm-submit-title" class="text-lg font-semibold text-gray-900">{{ __('Review your request') }}</h2>
                                    <p class="mt-1 text-sm text-gray-600">{{ __('Please check your details before you submit.') }}</p>
                                </div>
                            </div>

                            <div class="space-y-5 overflow-y-auto p-6">
                                <dl class="space-y-3 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Name') }}</dt>
                                        <dd class="text-right font-medium text-gray-900" x-text="review.name"></dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Student number') }}</dt>
                                        <dd class="text-right font-medium text-gray-900" x-text="review.studentNo"></dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Course / program') }}</dt>
                                        <dd class="text-right font-medium text-gray-900" x-text="review.course"></dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Email') }}</dt>
                                        <dd class="break-all text-right font-medium text-gray-900" x-text="review.email"></dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Contact number') }}</dt>
                                        <dd class="text-right font-medium text-gray-900" x-text="review.contactNo"></dd>
                                    </div>
                                    <div class="space-y-2 border-t border-gray-100 pt-3">
                                        <dt class="text-gray-500" x-text="(review.documents ?? []).length > 1 ? @js(__('Documents')) : @js(__('Document'))"></dt>
                                        <template x-for="item in review.documents ?? []" :key="item.label">
                                            <dd class="flex justify-between gap-4 font-medium text-gray-900">
                                                <span><span x-text="item.label"></span> <span class="font-normal text-gray-500">&times; <span x-text="item.copies"></span></span></span>
                                                <span class="shrink-0" x-text="item.subtotal"></span>
                                            </dd>
                                        </template>
                                    </div>
                                    <div class="flex justify-between gap-4" x-show="review.representative" x-cloak>
                                        <dt class="text-gray-500">{{ __('Representative') }}</dt>
                                        <dd class="text-right font-medium text-gray-900" x-text="review.representative"></dd>
                                    </div>
                                    <div class="flex items-end justify-between gap-4 rounded-xl bg-indigo-50 px-4 py-3">
                                        <dt class="text-sm font-medium text-indigo-900">{{ __('Estimated fee') }}</dt>
                                        <dd class="text-xl font-bold text-indigo-700" x-text="review.total"></dd>
                                    </div>
                                </dl>

                                <ul class="space-y-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                                    <li class="flex gap-2">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ __('The registrar needs up to :days days to process your request, depending on the document requested.', ['days' => config('school.processing_days')]) }}
                                    </li>
                                    <li class="flex gap-2">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                        {{ __('You can cancel a pending request :window. Once it is approved, it can no longer be cancelled.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}
                                    </li>
                                </ul>
                            </div>

                            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 p-4 sm:flex-row sm:justify-end">
                                <button type="button" x-on:click="confirming = false" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('Edit details') }}
                                </button>
                                <button type="button" x-ref="confirmButton" x-on:click="confirmSubmit()" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('Confirm and submit') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tips --}}
                    <aside>
                        <div class="rounded-2xl bg-indigo-600 p-6 text-white shadow-sm">
                            <h2 class="font-semibold">{{ __('Before you submit') }}</h2>
                            <ul class="mt-3 space-y-3 text-sm text-indigo-100">
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    {{ __('Use your name and student number exactly as in your school records.') }}
                                </li>
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    {{ __('Your reference number is emailed to your Gmail address.') }}
                                </li>
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    {{ __('The registrar needs up to :days days to process your request, depending on the document requested.', ['days' => config('school.processing_days')]) }}
                                </li>
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    {{ __('You can ask to cancel a pending request :window. Once your request is approved, it can no longer be cancelled.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}
                                </li>
                            </ul>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
