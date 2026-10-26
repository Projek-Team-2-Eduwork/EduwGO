{{-- Override layout notifikasi Laravel: logo brand + kontak global (email non-booking). --}}
@php($contact = App\Models\Branch::mailContact())
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
<x-brand-logo email />
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
**{{ $contact['name'] }}**<br>
@isset($contact['address']){{ $contact['address'] }}<br>
@endisset
@isset($contact['hours'])Jam operasional: {{ $contact['hours'] }}<br>
@endisset
@isset($contact['whatsapp'])WhatsApp: [{{ $contact['phone'] }}]({{ $contact['whatsapp'] }})
@endisset
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
