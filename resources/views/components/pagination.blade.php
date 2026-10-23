{{-- View pagination kustom: pakai `$paginator->links('components.pagination')`. --}}
@if ($paginator->hasPages())
    @php
        $baseButton = 'inline-flex min-w-[2.25rem] items-center justify-center border px-3 py-1.5 text-sm font-medium transition';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm">
            Menampilkan <span class="font-semibold text-[var(--navy-900)]">{{ $paginator->firstItem() }}</span>
            &ndash; <span class="font-semibold text-[var(--navy-900)]">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-[var(--navy-900)]">{{ $paginator->total() }}</span> data
        </p>

        <ul class="flex flex-wrap items-center justify-center gap-1">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li><span class="{{ $baseButton }} cursor-not-allowed border-[var(--border)] opacity-50" aria-disabled="true">&lsaquo;<span class="sr-only">Sebelumnya</span></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $baseButton }} border-[var(--border)] bg-[var(--surface)] text-[var(--navy-900)] hover:bg-[var(--bg)]" aria-label="Sebelumnya">&lsaquo;</a></li>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="{{ $baseButton }} border-transparent">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page" class="{{ $baseButton }} border-[var(--navy-900)] bg-[var(--navy-900)] text-[var(--surface)]">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="{{ $baseButton }} border-[var(--border)] bg-[var(--surface)] text-[var(--navy-900)] hover:bg-[var(--bg)]" aria-label="Halaman {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $baseButton }} border-[var(--border)] bg-[var(--surface)] text-[var(--navy-900)] hover:bg-[var(--bg)]" aria-label="Berikutnya">&rsaquo;</a></li>
            @else
                <li><span class="{{ $baseButton }} cursor-not-allowed border-[var(--border)] opacity-50" aria-disabled="true">&rsaquo;<span class="sr-only">Berikutnya</span></span></li>
            @endif
        </ul>
    </nav>
@endif
