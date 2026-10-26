<div class="home-inclusions" aria-labelledby="judul-termasuk">
        <h2 id="judul-termasuk">Sudah termasuk</h2>
        <ul>
            @foreach(['Helm' => 'M4 14a8 8 0 0 1 16 0v4H4zm0 0h16M12 6v8', 'Jas hujan' => 'm8 4 4-2 4 2 5 8-4 2v8H7v-8l-4-2zm0 0 4 6 4-6'] as $label => $path)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}"/></svg><span>{{ $label }}</span></li>
            @endforeach
        </ul>
</div>
