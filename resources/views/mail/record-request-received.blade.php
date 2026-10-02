<x-mail::message>
# Hi {{ $recordRequest->first_name }},

@if ($recordRequests->count() > 1)
We received your request for {{ $recordRequests->count() }} student records. Each document has its own reference number. The registrar will contact you once each one is ready.

@foreach ($recordRequests as $item)
<x-mail::panel>
## {{ $item->reference_no }}

**Document:** {{ $item->document_type->label() }}<br>
**Copies:** {{ $item->copies }}<br>
**Fee:** {{ $item->document_type->formattedFee() }} per copy

<a href="{{ $cancelUrls[$item->reference_no] }}">Cancel this request</a>
</x-mail::panel>

@endforeach
Keep these numbers. You will need them to follow up on your requests.

**Estimated total fee:** ₱{{ number_format($totalFee, 2) }}

Made a mistake or no longer need one of these? You can request cancellation {{ \App\Models\RecordRequest::cancellationWindowPhrase() }} of submitting, using the link under each reference number.

If you did not make these requests, you can ignore this email or cancel them with the links above.
@else
We received your request for a student record. The registrar will contact you once it is ready.

Your reference number:

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

Keep this number. You will need it to follow up on your request.

**Document:** {{ $recordRequest->document_type->label() }}<br>
**Copies:** {{ $recordRequest->copies }}<br>
**Fee per copy:** {{ $recordRequest->document_type->formattedFee() }}

Made a mistake or no longer need this? You can request cancellation {{ \App\Models\RecordRequest::cancellationWindowPhrase() }} of submitting.

<x-mail::button :url="$cancelUrl" color="error">
Cancel this request
</x-mail::button>

If you did not make this request, you can ignore this email or cancel it with the button above.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
