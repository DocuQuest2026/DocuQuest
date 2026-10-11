<x-mail::message>
# Hi {{ $recordRequest->first_name }},

Good news! Your request has been approved by the Office of the Registrar. Your document is now being prepared and is waiting to be released.

<x-mail::panel>
## {{ $recordRequest->reference_no }}
</x-mail::panel>

**Document:** {{ $recordRequest->documentType->label() }}<br>
**Estimated ready by:** {{ $recordRequest->estimatedReadyDate()?->format('D, M j, Y') }}

We will email you again once it is ready to claim. You can check the progress of your request anytime using your reference number.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
