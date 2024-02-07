<x-mail::message>
# Introduction

Hello {{ $invitation->firstname }}<br>

Please use this link to register yourself<br>

<x-mail::button :url="config('app.url').'/member?invitation='.encrypt($invitation->id).'&email='.$invitation->email">
Register yourself now
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
