<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ __('Documents') }}
            </h2>

            <a wire:navigate.hover href="{{ route('admin.document-types.create') }}">
                <x-primary-button type="button">{{ __('Add document') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 text-sm rounded-xl ring-1 ring-inset ring-green-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Fee per copy') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Processing time') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Requests') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($documentTypes as $documentType)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $documentType->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $documentType->formattedFee() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ trans_choice(':count working day|:count working days', $documentType->processing_days) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $documentType->record_requests_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($documentType->is_active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Offered') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('Not offered') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a wire:navigate.hover href="{{ route('admin.document-types.edit', $documentType) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
