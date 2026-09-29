<section id="syarat-ketentuan" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8 lg:pt-24">
    <div class="card-surface p-6 sm:p-10">
        <h2 class="font-serif text-3xl sm:text-4xl">Syarat &amp; Ketentuan Rental Motor</h2>

        <ol class="mt-6 grid gap-x-10 gap-y-4 md:grid-cols-2">
            @foreach ($terms as $i => $term)
                <li class="flex gap-4 text-sm leading-relaxed">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white" style="background-color: var(--orange-500);">{{ $i + 1 }}</span>
                    <span>{{ $term }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</section>
