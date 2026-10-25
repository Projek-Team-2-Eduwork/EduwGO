<x-app-layout title="Syarat & ketentuan">
    <article class="document-copy mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <h1>Syarat &amp; ketentuan</h1>
        <p class="mt-4">Baca ketentuan berikut sebelum memesan motor.</p>
        <ol class="mt-8 list-decimal space-y-5 pl-6">@foreach($points as $point)<li data-sk-poin>{{ $point }}</li>@endforeach</ol>
        <a href="{{ route('kendaraan.index') }}" class="mt-10 inline-flex min-h-11 items-center font-semibold underline">Pilih motor</a>
    </article>
</x-app-layout>
