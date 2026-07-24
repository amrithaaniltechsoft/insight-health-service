<x-mail::message>
# New Enquiry

A new enquiry has been submitted from the Insight Health Services website.

<x-mail::panel>
**Name:** {{ $enquiry['first_name'] }} {{ $enquiry['last_name'] }}<br>
**Email:** {{ $enquiry['email'] }}<br>
**Phone:** {{ $enquiry['phone'] ?? 'N/A' }}
</x-mail::panel>

**Message:**

{{ $enquiry['message'] }}

<br>
<x-mail::button :url="'mailto:' . $enquiry['email']">
Reply to {{ $enquiry['first_name'] }}
</x-mail::button>

Thanks,<br>
{{ config('mail.from.name') }}
</x-mail::message>
