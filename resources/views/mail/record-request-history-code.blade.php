<x-mail::message>
# Your verification code

Use this code to view the history of your record requests:

<x-mail::panel>
## {{ $code }}
</x-mail::panel>

This code expires in {{ $expiresInMinutes }} minutes. If you did not ask for it, you can ignore this email. Nobody can see your requests without it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
