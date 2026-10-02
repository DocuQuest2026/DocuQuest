<x-mail::message>
# Hi {{ $name }},

An administrator is setting up a registrar's office account for you on {{ config('app.name') }}. Your account is only created once you confirm that this email address is yours.

<x-mail::button :url="$confirmationUrl">
Confirm my email and create my account
</x-mail::button>

This link works for {{ $validForHours }} hours. If you were not expecting this, you can ignore this email and no account will be created.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
