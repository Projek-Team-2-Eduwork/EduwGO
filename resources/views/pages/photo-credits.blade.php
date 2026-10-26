<x-app-layout title="Kredit foto">
    <article class="document-copy mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @foreach(['images/home/CREDITS.md', 'images/vehicles/CREDITS.md'] as $credits)
            {!! Illuminate\Support\Str::markdown(file_get_contents(public_path($credits)), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        @endforeach
    </article>
</x-app-layout>
