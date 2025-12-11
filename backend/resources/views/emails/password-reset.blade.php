@component('mail::message')
# Reset Your Password

You are receiving this email because we received a password reset request for your account.

@component('mail::button', ['url' => $resetUrl])
Reset Password
@endcomponent

This password reset link will expire in 60 minutes.

If you did not request a password reset, no further action is required.

@component('mail::panel')
**Security Tip**: Never share your password or reset link with anyone.
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team

@component('mail::subcopy')
If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
[{{ $resetUrl }}]({{ $resetUrl }})
@endcomponent
@endcomponent
