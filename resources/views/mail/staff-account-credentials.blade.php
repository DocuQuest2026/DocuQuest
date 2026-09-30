<x-mail::message>
# Hi {{ $account->name }},

An administrator created a registrar's office account for you. Here are your sign-in details:

<x-mail::panel>
**Email:** {{ $account->email }}<br>
**Password:** {{ $password }}
</x-mail::panel>

We recommend changing this password after you sign in.

<x-mail::button :url="$loginUrl">
Sign in
</x-mail::button>

If you were not expecting this account, please contact the registrar's office.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
