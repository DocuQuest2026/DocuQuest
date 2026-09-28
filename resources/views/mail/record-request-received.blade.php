<x-mail::message>
# Hi {{ $recordRequest->first_name }},

We received your request for a student record. The registrar will contact you once it is ready.

Your reference number:

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

Keep this number. You will need it to follow up on your request.

**Document:** {{ $recordRequest->document_type->label() }}<br>
**Copies:** {{ $recordRequest->copies }}<br>
**Fee per copy:** {{ $recordRequest->document_type->formattedFee() }}

Made a mistake or no longer need this? You can cancel the request while it is still pending.

<x-mail::button :url="$cancellationUrl" color="error">
Cancel this request
</x-mail::button>

This link works for 14 days. If you did not make this request, you can ignore this email or cancel it with the button above.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
