<x-mail::message>
# Hi {{ $recordRequest->first_name }},

Good news — your document is ready to be claimed at the Office of the Registrar.

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

**Document:** {{ $recordRequest->document_type->label() }}<br>
**Copies:** {{ $recordRequest->copies }}<br>
**Released to:** {{ $documentRelease->representative_name }}<br>
**Available to claim from:** {{ $documentRelease->claim_available_at->format('M j, Y g:i A') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
