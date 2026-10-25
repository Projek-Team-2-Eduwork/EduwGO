@props(['name' => 'vehicles'])
@php($name = (string) $name)

{{-- Decorative artwork: the adjacent heading carries the state, including payment outcomes. --}}
<svg {{ $attributes->class(['block h-auto w-60 max-w-full shrink-0']) }} viewBox="0 0 240 180" width="240" height="180" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" data-illustration="{{ $name }}">
    <g stroke="var(--ink-muted, currentColor)" stroke-width="6" stroke-linejoin="round" stroke-linecap="square">
        @switch($name)
            @case('welcome')
                <path d="M142 142V38H190V142M132 142H200M174 86V94"/>
                <g stroke="var(--accent, currentColor)">
                    <path d="M48 48H88L100 60V90L88 102H48L36 90V60Z"/>
                    <circle cx="68" cy="75" r="10"/>
                    <path d="M68 102V142H94V130H82V118"/>
                </g>
                @break
            @case('bookings')
                <path d="M54 42H174L186 54V142H54ZM54 70H186M78 32V50M162 32V50"/>
                <path stroke="var(--accent, currentColor)" d="M78 94H96M120 94H138M78 118H96M120 118H138"/>
                @break
            @case('404')
            @case('locations')
                <path d="M40 56 90 38 150 56 200 38V126L150 144 90 126 40 144ZM90 38V126M150 56V144"/>
                <path stroke="var(--accent, currentColor)" d="M60 110H76L104 82H132"/>
                @if($name === '404')
                    <path stroke="var(--accent, currentColor)" d="M168 78 184 94M184 78 168 94"/>
                @else
                    <path stroke="var(--accent, currentColor)" d="M174 66C156 66 152 86 174 108C196 86 192 66 174 66Z"/>
                @endif
                @break
            @case('403')
                <path d="M82 76V54C82 16 158 16 158 54V76"/>
                <path stroke="var(--accent, currentColor)" d="M66 76H174V132L162 144H66Z"/>
                <circle stroke="var(--accent, currentColor)" cx="120" cy="104" r="8"/>
                <path stroke="var(--accent, currentColor)" d="M120 112V124"/>
                @break
            @case('service')
                <path d="M38 128H66V104M174 76V52H202"/>
                <path stroke="var(--accent, currentColor)" d="M48 104V72H84L96 84V104ZM66 72V56M82 72V56M144 76V96L156 108H192V76ZM158 108V124M176 108V124"/>
                @break
            @case('success')
            @case('failed')
            @case('waiting')
                <path d="M64 32H160L176 48V146L160 138 144 146 128 138 112 146 96 138 80 146 64 138ZM84 54H136M84 72H116"/>
                <g stroke="var(--accent, currentColor)">
                    @if($name === 'success')
                        <path d="M98 108 114 124 146 92"/>
                    @elseif($name === 'failed')
                        <path d="M106 98 138 130M138 98 106 130"/>
                    @else
                        <path d="M104 88H140M104 128H140M108 88V96L136 120V128M136 88V96L108 120V128"/>
                    @endif
                </g>
                @break
            @case('users')
                <path d="M54 40H174L186 52V140H54Z"/>
                <circle stroke="var(--accent, currentColor)" cx="120" cy="76" r="16"/>
                <path stroke="var(--accent, currentColor)" d="M86 122V114C86 92 154 92 154 114V122"/>
                @break
            @default
                {{-- A rental key and an empty allocation slot; no inaccurate vehicle silhouette. --}}
                <path d="M40 136H200M152 54H188V110H152ZM160 70H180"/>
                <g stroke="var(--accent, currentColor)">
                    <path d="M54 38H92L104 50V82L92 94H54L42 82V50Z"/>
                    <circle cx="72" cy="64" r="9"/>
                    <path d="M72 94V122H102V110H90V100"/>
                </g>
        @endswitch
    </g>
</svg>
