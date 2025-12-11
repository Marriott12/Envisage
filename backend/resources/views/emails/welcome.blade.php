@component('mail::message')
# Welcome to {{ config('app.name') }}, {{ $userName }}!

Thank you for joining our marketplace community. We're excited to have you on board!

## Get Started

Here's what you can do now:

- **Browse Products**: Discover amazing items from our sellers
- **Create Listings**: Start selling your products (if you're a seller)
- **Manage Your Profile**: Update your information and preferences

@component('mail::button', ['url' => $marketplaceUrl])
Explore Marketplace
@endcomponent

## Need Help?

If you have any questions or need assistance, feel free to contact our support team.

@component('mail::panel')
**Quick Tip**: Complete your profile to enhance your marketplace experience!
@endcomponent

Thanks,<br>
The {{ config('app.name') }} Team

@component('mail::subcopy')
If you're having trouble clicking the button, copy and paste the URL below into your web browser:
[{{ $marketplaceUrl }}]({{ $marketplaceUrl }})
@endcomponent
@endcomponent
