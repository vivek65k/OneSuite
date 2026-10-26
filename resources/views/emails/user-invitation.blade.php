@component('mail::message')
# You're invited to {{ $user->company?->name ?? 'OneSuite' }}

Hi {{ $user->name }},

You've been set up with an account on **{{ $user->company?->name ?? 'OneSuite' }}**'s OneSuite workspace. Set a password to activate it.

@component('mail::button', ['url' => $acceptUrl])
Set your password
@endcomponent

This link works once and expires in 7 days. If you weren't expecting this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
