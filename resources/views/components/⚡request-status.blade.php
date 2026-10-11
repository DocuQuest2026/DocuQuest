<?php

use App\Models\RecordRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    /**
     * The reference number being followed. It is public information the requester typed in,
     * but locked so it can not be swapped for someone else's request from the browser.
     */
    #[Locked]
    public string $referenceNo = '';

    /**
     * The request, loaded fresh on every poll so the status, queue place and estimated ready
     * date update without the requester reloading the page.
     */
    #[Computed]
    public function recordRequest(): ?RecordRequest
    {
        return RecordRequest::where('reference_no', $this->referenceNo)->first();
    }
};
?>

@php
    $recordRequest = $this->recordRequest;
@endphp

<div>
@if ($recordRequest)
    @php
        $status = $recordRequest->status;
        $claimedAt = $status === \App\Enums\RequestStatus::Released ? $recordRequest->release?->claimed_at : null;

        $badge = $recordRequest->requesterStatus();

        $message = match (true) {
            $claimedAt !== null => ['tone' => 'green', 'text' => __('You claimed this document on :date.', ['date' => $claimedAt->format('M j, Y g:i A')])],
            $status === \App\Enums\RequestStatus::Pending => ['tone' => 'amber', 'text' => __('The registrar is processing your request. This takes up to :days days, depending on the document requested.', ['days' => config('school.processing_days')])],
            $status === \App\Enums\RequestStatus::Approved => ['tone' => 'blue', 'text' => __('Your request is approved and your document is being prepared. It can no longer be cancelled.')],
            $status === \App\Enums\RequestStatus::Released => ['tone' => 'green', 'text' => __('Your document is ready. Bring a valid ID, or have your named representative bring theirs.')],
            $status === \App\Enums\RequestStatus::Rejected => ['tone' => 'red', 'text' => __('The registrar could not approve this request. Please contact the registrar for details.')],
            $status === \App\Enums\RequestStatus::CancellationRequested => ['tone' => 'orange', 'text' => __('You asked to cancel this request. The registrar will confirm it.')],
            default => ['tone' => 'gray', 'text' => __('This request was cancelled.')],
        };

        $tones = [
            'amber' => 'bg-amber-50 text-amber-900 ring-amber-200',
            'blue' => 'bg-blue-50 text-blue-900 ring-blue-200',
            'green' => 'bg-green-50 text-green-900 ring-green-200',
            'red' => 'bg-red-50 text-red-900 ring-red-200',
            'orange' => 'bg-orange-50 text-orange-900 ring-orange-200',
            'gray' => 'bg-gray-100 text-gray-800 ring-gray-200',
        ];

        $progress = match ($status) {
            \App\Enums\RequestStatus::Pending => ['done', 'current', 'upcoming'],
            \App\Enums\RequestStatus::Approved => ['done', 'done', 'current'],
            \App\Enums\RequestStatus::Released => ['done', 'done', 'done'],
            default => null,
        };
@endphp

    <div wire:poll.20s.visible class="rounded-2xl bg-white p-6 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Reference number') }}</p>
                <p class="mt-1 font-mono text-xl font-bold tracking-wider text-gray-900">{{ $recordRequest->reference_no }}</p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
        </div>

        <div class="mt-5 rounded-xl p-4 text-sm leading-relaxed ring-1 {{ $tones[$message['tone']] }}">
            <p>{{ $message['text'] }}</p>

            @if ($recordRequest->isCancellable())
                <p class="mt-2">
                    {{ __('You can still cancel it :window of submitting.', ['window' => \App\Models\RecordRequest::cancellationWindowPhrase()]) }}
                    <a href="{{ route('record-requests.cancel.create', ['reference_no' => $recordRequest->reference_no]) }}" class="font-semibold underline underline-offset-2">{{ __('Cancel this request') }}</a>
                </p>
            @endif
        </div>

        <dl class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">{{ __('Document') }}</dt>
                <dd class="text-right font-medium text-gray-900">
                    {{ $recordRequest->documentType->label() }}
                    <span class="whitespace-nowrap font-normal text-gray-500">&times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                </dd>
            </div>
            @if (! in_array($status, [\App\Enums\RequestStatus::Rejected, \App\Enums\RequestStatus::Cancelled], true))
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-gray-500">{{ $claimedAt !== null ? __('Total fee') : __('Amount to pay') }}</dt>
                    <dd class="text-right">
                        <span class="block text-base font-bold text-indigo-600">{{ $recordRequest->formattedTotalFee() }}</span>
                        <span class="block text-xs text-gray-500">{{ $recordRequest->formattedFeePerCopy() }} &times; {{ trans_choice(':count copy|:count copies', $recordRequest->copies) }}</span>
                    </dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                <dd class="font-medium text-gray-900">{{ $recordRequest->created_at->format('M j, Y') }}</dd>
            </div>
            @if (($estimatedReady = $recordRequest->estimatedReadyDate()) !== null)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('Estimated ready by') }}</dt>
                    <dd class="text-right font-medium text-gray-900">
                        {{ $estimatedReady->format('D, M j, Y') }}
                        <span class="block text-xs font-normal text-gray-500">{{ trans_choice('Up to :count working day|Up to :count working days', $recordRequest->documentType->processingDays()) }}</span>
                    </dd>
                </div>
            @endif
            @if (($queuePosition =$recordRequest->queuePosition()) !== null)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('Place in queue') }}</dt>
                    <dd class="text-right font-medium text-gray-900">
                        {{ __('#:position of :total', ['position' => $queuePosition, 'total' => \App\Models\RecordRequest::queueLength()]) }}
                        <span class="block text-xs font-normal text-gray-500">{{ $queuePosition === 1 ? __('You are next in line.') : trans_choice(':count request ahead of you|:count requests ahead of you', $queuePosition - 1) }}</span>
                    </dd>
                </div>
            @endif
            @if ($status === \App\Enums\RequestStatus::Released && $recordRequest->release?->claim_available_at)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('Available to claim from') }}</dt>
                    <dd class="text-right font-medium text-gray-900">{{ $recordRequest->release->claim_available_at->format('M j, Y g:i A') }}</dd>
                </div>
            @endif
        </dl>

        @if ($progress)
            <ol class="mt-6 space-y-3 border-t border-gray-100 pt-5">
                @foreach ([
                    ['Request submitted', 'We received your request.', null, null],
                    ['Approved by the registrar', 'Your request is reviewed and approved.', 'The registrar is processing for approval', __('This takes up to :days days, depending on the document.', ['days' => config('school.processing_days')])],
                    ['Ready to claim', 'Your document is ready to be picked up.', null, null],
                ] as $index => [$title, $description, $titleWhileCurrent, $descriptionWhileCurrent])
                    @php
                        $state = $progress[$index];

                        if ($state === 'current' && $titleWhileCurrent !== null) {
                            $title = $titleWhileCurrent;
                            $description = $descriptionWhileCurrent;
                        }
                    @endphp

                    <li class="flex gap-3">
                        @if ($state === 'done')
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                        @elseif ($state === 'current')
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-indigo-600 bg-indigo-50">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                            </span>
                        @else
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white"></span>
                        @endif

                        <div class="min-w-0">
                            <p class="text-sm font-semibold leading-5 {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-900' }}">{{ __($title) }}</p>
                            <p class="text-xs leading-5 {{ $state === 'upcoming' ? 'text-gray-400' : 'text-gray-500' }}">{{ __($description) }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif

        <p class="mt-5 text-center text-xs text-gray-400">{{ __('This page updates automatically.') }}</p>
    </div>
@endif
</div>
