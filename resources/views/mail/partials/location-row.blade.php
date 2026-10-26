@if($branch)
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">{{ $label ?? 'Lokasi ambil' }}</td>
        <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }};">
            <strong>{{ $branch->name }}</strong>
            @if($branch->address)
                <br>{{ $branch->address }}
                <br><a href="{{ $branch->mapsUrl() }}" style="color: {{ App\Support\BrandTokens::value('--mail-action') }};">Buka di Google Maps</a>
            @endif
        </td>
    </tr>
@endif
