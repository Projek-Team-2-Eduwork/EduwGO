<x-app-layout title="Kredit foto">
    <article class="document-copy mx-auto max-w-3xl px-4 py-12 sm:px-6">
        {!! Illuminate\Support\Str::markdown(file_get_contents(public_path('images/vehicles/CREDITS.md')), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </article>
</x-app-layout>
