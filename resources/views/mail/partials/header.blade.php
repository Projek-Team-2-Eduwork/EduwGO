<div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
    @if(setting('brand.logo_light'))
        <img src="{{ asset(setting('brand.logo_light')) }}" alt="{{ setting('brand.name', 'EduwGo') }}" style="max-height: 40px; margin: 0 auto;">
    @else
        <h1 style="color: #111827; margin: 0; font-family: sans-serif; font-size: 24px;">{{ setting('brand.name', 'EduwGo') }}</h1>
    @endif
</div>