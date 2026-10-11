<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ __('Add document') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-2xl">
                <form method="POST" action="{{ route('admin.document-types.store') }}" class="max-w-xl space-y-6">
                    @csrf

                    @include('admin.document-types._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Add document') }}</x-primary-button>
                        <a wire:navigate.hover href="{{ route('admin.document-types.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
