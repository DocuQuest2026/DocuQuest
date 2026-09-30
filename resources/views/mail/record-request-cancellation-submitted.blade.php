<x-mail::message>
# Hi {{ $recordRequest->first_name }},

We received your request to cancel this document request. It has been sent to the Office of the Registrar and is awaiting review.

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

**Document:** {{ $recordRequest->document_type->label() }}<br>
**Your reason:** {{ $recordRequest->cancellation_reason }}

Registrar staff will confirm the cancellation shortly. You do not need to do anything else.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
