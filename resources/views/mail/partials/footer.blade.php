@php($contact = App\Models\Branch::mailContact($branch ?? null))
<div style="margin-top: 40px; text-align: center; border-top: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; padding-top: 20px; font-size: 14px; line-height: 1.6; color: {{ App\Support\BrandTokens::value('--mail-faint') }};">
    <p style="margin: 0; font-weight: bold; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">{{ $contact['name'] }}</p>
    @isset($contact['address'])
        <p style="margin: 0;">{{ $contact['address'] }}</p>
    @endisset
    @isset($contact['hours'])
        <p style="margin: 0;">Jam operasional: {{ $contact['hours'] }}</p>
    @endisset
    @isset($contact['whatsapp'])
        <p style="margin: 0;">WhatsApp: <a href="{{ $contact['whatsapp'] }}" style="color: {{ App\Support\BrandTokens::value('--mail-action') }};">{{ $contact['phone'] }}</a></p>
    @endisset
</div>
