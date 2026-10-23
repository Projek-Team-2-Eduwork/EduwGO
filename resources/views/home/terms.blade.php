<section id="syarat-ketentuan" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    {{-- Judul & subjudul ditengahkan supaya seksi ini terasa seperti panduan yang enak dibaca --}}
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="font-serif text-2xl sm:text-3xl">Syarat &amp; Ketentuan Rental Motor</h2>
        <p class="mt-3 text-sm leading-relaxed">Transparansi adalah kunci. Pastikan Anda memenuhi beberapa persyaratan dasar sebelum melakukan pemesanan.</p>
    </div>

    {{-- Satu poin satu kartu: lebih gampang dibaca sekilas daripada daftar panjang --}}
    <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($terms as $term)
            <li class="card-surface card-lift flex flex-col gap-3 p-5">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-[#0A0F1D]" style="background-image: var(--accent-gradient);" aria-hidden="true">{{ $loop->iteration }}</span>
                <p class="text-sm leading-relaxed">{{ $term }}</p>
            </li>
        @endforeach
    </ol>
</section>
