<section id="syarat-ketentuan" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    <h2 class="text-2xl">Syarat &amp; Ketentuan Rental Motor</h2>
    <p class="mt-2 text-sm">Transparansi adalah kunci. Pastikan Anda memenuhi beberapa persyaratan dasar sebelum melakukan pemesanan.</p>

    <div class="card-surface mt-6 p-5 sm:p-6">
        <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed">
            @foreach ($terms as $term)
                <li>{{ $term }}</li>
            @endforeach
        </ol>
    </div>
</section>
