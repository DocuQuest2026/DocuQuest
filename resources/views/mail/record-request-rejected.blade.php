<x-mail::message>
# Hi {{ $recordRequest->first_name }},

Unfortunately, your request could not be processed by the Office of the Registrar.

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

**Document:** {{ $recordRequest->document_type->label() }}

**Reason:** {{ $reason }}

If you have questions or would like to submit a corrected request, please contact the registrar or submit a new request.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
