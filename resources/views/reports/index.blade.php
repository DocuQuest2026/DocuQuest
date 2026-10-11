<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ __('Reports') }}
            </h2>

            <a href="{{ route('reports.export', array_filter($filters, fn ($value) => $value !== null && $value !== '')) }}">
                <x-primary-button type="button">{{ __('Download PDF') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    @php
        $statusClasses = [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'released' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'cancellation_requested' => 'bg-orange-100 text-orange-800',
            'cancelled' => 'bg-gray-200 text-gray-700',
        ];
        $peso = fn (int $amount): string => '₱'.number_format($amount, 2);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" name="from" type="date" min="{{ $minDate }}" class="mt-1 block w-full" :value="$filters['from']" />
                    <x-input-error class="mt-1" :messages="$errors->get('from')" />
                </div>

                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" name="to" type="date" min="{{ $minDate }}" class="mt-1 block w-full" :value="$filters['to']" />
                    <x-input-error class="mt-1" :messages="$errors->get('to')" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <x-select-input id="status" name="status" class="mt-1 block w-full">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-1" :messages="$errors->get('status')" />
                </div>

                <div>
                    <x-input-label for="document_type_id" :value="__('Document')" />
                    <x-select-input id="document_type_id" name="document_type_id" class="mt-1 block w-full">
                        <option value="">{{ __('All documents') }}</option>
                        @foreach ($documentTypes as $documentType)
                            <option value="{{ $documentType->id }}" @selected($filters['document_type_id'] === $documentType->id)>{{ $documentType->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-1" :messages="$errors->get('document_type_id')" />
                </div>

                <x-primary-button class="justify-center">{{ __('Apply filters') }}</x-primary-button>
            </form>

            <p class="text-sm text-gray-600">
                {{ __('Showing requests submitted :from – :to (archived included).', ['from' => $report->from->format('M j, Y'), 'to' => $report->to->format('M j, Y')]) }}
            </p>

            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <dt class="text-sm font-medium text-gray-600">{{ __('Requests') }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($totals['total']) }}</dd>
                    <p class="mt-1 text-xs text-gray-500">{{ trans_choice(':count copy|:count copies', $totals['copies']) }} &middot; {{ __(':count urgent', ['count' => $totals['urgent']]) }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <dt class="text-sm font-medium text-gray-600">{{ __('Fees from released documents') }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-indigo-600">{{ $peso($totals['collectedFees']) }}</dd>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Fees across all requests: :amount', ['amount' => $peso($totals['fees'])]) }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <dt class="text-sm font-medium text-gray-600">{{ __('Average processing time') }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">
                        {{ $totals['averageProcessingDays'] === null ? '—' : trans_choice(':count day|:count days', $totals['averageProcessingDays'], ['count' => $totals['averageProcessingDays']]) }}
                    </dd>
                    <p class="mt-1 text-xs text-gray-500">{{ __('From submission to release, released requests only.') }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <dt class="text-sm font-medium text-gray-600">{{ __('Released') }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($byStatus['released']) }}</dd>
                    <p class="mt-1 text-xs text-gray-500">{{ __(':count still open', ['count' => $byStatus['pending'] + $byStatus['approved'] + $byStatus['cancellation_requested']]) }}</p>
                </div>
            </dl>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
                    <h3 class="border-b border-gray-100 bg-gray-50 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-gray-600">{{ __('By status') }}</h3>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($statuses as $status)
                            <li class="flex items-center justify-between px-6 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses[$status->value] }}">{{ $status->label() }}</span>
                                <span class="font-medium text-gray-900">{{ number_format($byStatus[$status->value]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
                    <h3 class="border-b border-gray-100 bg-gray-50 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-gray-600">{{ __('By document') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-gray-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 font-medium">{{ __('Document') }}</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">{{ __('Requests') }}</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">{{ __('Copies') }}</th>
                                    <th scope="col" class="px-6 py-3 text-right font-medium">{{ __('Fees') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($byDocument as $row)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-900">{{ $row->name }}</td>
                                        <td class="px-3 py-3 text-right text-gray-600">{{ number_format($row->requests) }}</td>
                                        <td class="px-3 py-3 text-right text-gray-600">{{ number_format($row->copies) }}</td>
                                        <td class="px-6 py-3 text-right text-gray-900">{{ $peso($row->fees) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-600">{{ __('No requests match these filters.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
