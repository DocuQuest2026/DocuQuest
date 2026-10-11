<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ __('Requests report') }}</title>
        <style>
            @page { margin: 28px 32px; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; }
            h1 { font-size: 16px; margin: 0 0 2px; }
            h2 { font-size: 12px; margin: 18px 0 6px; }
            .meta { color: #555; margin: 0 0 4px; }
            table { width: 100%; border-collapse: collapse; }
            th { text-align: left; background: #f3f4f6; color: #374151; font-weight: bold; }
            th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
            .right { text-align: right; }
            .totals td { border: 0; padding: 2px 6px 2px 0; }
            .totals td.label { color: #555; width: 190px; }
            .note { color: #92400e; margin-top: 8px; }
            .half { width: 48%; display: inline-block; vertical-align: top; }
        </style>
    </head>
    <body>
        <h1>{{ __('Office of the Registrar — Requests report') }}</h1>
        <p class="meta">{{ __('Requests submitted from :from to :to.', ['from' => $report->from->format('M j, Y'), 'to' => $report->to->format('M j, Y')]) }}</p>
        <p class="meta">
            {{ __('Status: :status', ['status' => $report->status?->label() ?? __('All')]) }}
            &nbsp;&middot;&nbsp;
            {{ __('Document: :document', ['document' => $documentName ?? __('All')]) }}
            &nbsp;&middot;&nbsp;
            {{ __('Archived requests are included.') }}
        </p>
        <p class="meta">{{ __('Generated :date by :name', ['date' => now()->format('M j, Y g:i A'), 'name' => $generatedBy]) }}</p>

        <h2>{{ __('Summary') }}</h2>
        <table class="totals">
            <tr><td class="label">{{ __('Requests') }}</td><td>{{ number_format($totals['total']) }}</td></tr>
            <tr><td class="label">{{ __('Copies') }}</td><td>{{ number_format($totals['copies']) }}</td></tr>
            <tr><td class="label">{{ __('Urgent requests') }}</td><td>{{ number_format($totals['urgent']) }}</td></tr>
            <tr><td class="label">{{ __('Fees from released documents') }}</td><td>₱{{ number_format($totals['collectedFees'], 2) }}</td></tr>
            <tr><td class="label">{{ __('Fees across all requests') }}</td><td>₱{{ number_format($totals['fees'], 2) }}</td></tr>
            <tr><td class="label">{{ __('Average processing time') }}</td><td>{{ $totals['averageProcessingDays'] === null ? '—' : trans_choice(':count day|:count days', $totals['averageProcessingDays'], ['count' => $totals['averageProcessingDays']]) }}</td></tr>
        </table>

        <h2>{{ __('By status') }}</h2>
        <table style="width: 48%;">
            @foreach ($statuses as $status)
                <tr>
                    <td>{{ $status->label() }}</td>
                    <td class="right">{{ number_format($byStatus[$status->value]) }}</td>
                </tr>
            @endforeach
        </table>

        <h2>{{ __('By document') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Document') }}</th>
                    <th class="right">{{ __('Requests') }}</th>
                    <th class="right">{{ __('Copies') }}</th>
                    <th class="right">{{ __('Fees') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byDocument as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="right">{{ number_format($row->requests) }}</td>
                        <td class="right">{{ number_format($row->copies) }}</td>
                        <td class="right">₱{{ number_format($row->fees, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ __('No requests match these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>{{ __('Requests') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Student') }}</th>
                    <th>{{ __('Document') }}</th>
                    <th class="right">{{ __('Copies') }}</th>
                    <th class="right">{{ __('Total fee') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Submitted') }}</th>
                    <th>{{ __('Released') }}</th>
                    <th>{{ __('Claimed') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $recordRequest)
                    <tr>
                        <td>{{ $recordRequest->reference_no }}{{ $recordRequest->is_urgent ? ' ('.__('urgent').')' : '' }}</td>
                        <td>{{ $recordRequest->fullName() }}<br>{{ $recordRequest->student_no }}</td>
                        <td>{{ $recordRequest->documentType->label() }}</td>
                        <td class="right">{{ $recordRequest->copies }}</td>
                        <td class="right">₱{{ number_format($recordRequest->totalFee(), 2) }}</td>
                        <td>{{ $recordRequest->status->label() }}</td>
                        <td>{{ $recordRequest->created_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $recordRequest->release?->released_at?->format('M j, Y g:i A') }}</td>
                        <td>{{ $recordRequest->release?->claimed_at?->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">{{ __('No requests match these filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($truncated)
            <p class="note">{{ __('Only the newest :count requests are listed. Narrow the dates or filters to see the rest; the totals above include every request.', ['count' => number_format($requestLimit)]) }}</p>
        @endif
    </body>
</html>
