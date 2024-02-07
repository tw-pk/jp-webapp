<x-mail::message>
# Hello {{ $notifiable->fullName() }}<br>

## Thank you for subscribing to JustDialer Basic Plan<br>

<x-mail::button :url="config('app.url')">
View Invoice
</x-mail::button>

### Thanks<br>
{{ config('app.name') }}
</x-mail::message>
