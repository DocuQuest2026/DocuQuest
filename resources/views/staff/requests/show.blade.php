<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Request :reference', ['reference' => $recordRequest->reference_no]) }}
            </h2>

            <a href="{{ route('requests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 underline">{{ __('Back to requests') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-8">
                @php
                    $sections = [
                        __('Student') => [
                            __('Student number') => $recordRequest->student_no,
                            __('Full name') => $recordRequest->fullName(),
                            __('Course / program') => $recordRequest->course,
                            __('Enrolment status') => $recordRequest->enrolment_status->label(),
                            __('Email') => $recordRequest->email,
                            __('Contact number') => $recordRequest->contact_no,
                        ],
                        __('Request') => [
                            __('Document') => $recordRequest->document_type->label(),
                            __('Copies') => $recordRequest->copies,
                            __('Purpose') => $recordRequest->purpose,
                            __('Status') => $recordRequest->status->label(),
                            __('Submitted') => $recordRequest->created_at->format('M j, Y g:i A'),
                            ...($recordRequest->cancelled_at ? [__('Cancelled') => $recordRequest->cancelled_at->format('M j, Y g:i A')] : []),
                        ],
                    ];
                @endphp

                @foreach ($sections as $heading => $rows)
                    <section>
                        <h3 class="text-base font-semibold text-gray-900">{{ $heading }}</h3>
                        <dl class="mt-3 divide-y divide-gray-100 text-sm">
                            @foreach ($rows as $label => $value)
                                <div class="grid grid-cols-3 gap-4 py-2">
                                    <dt class="text-gray-500">{{ $label }}</dt>
                                    <dd class="col-span-2 text-gray-900 whitespace-pre-line">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
