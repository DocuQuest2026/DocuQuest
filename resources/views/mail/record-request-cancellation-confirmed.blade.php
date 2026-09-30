<x-mail::message>
# Hi {{ $recordRequest->first_name }},

Your request has been cancelled as you asked. This confirms it — no further action is needed from you.

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

**Document:** {{ $recordRequest->document_type->label() }}

If you need this document after all, you are welcome to submit a new request.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
