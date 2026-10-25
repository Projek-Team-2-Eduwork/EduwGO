@php($selected = old('branch_id', $current ?? null))
<div>
    <x-input-label for="branch_id" value="Cabang *" />
    @if (auth()->user()->isSuperadmin())
        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-lg shadow-sm" required>
            <option value="">Pilih Cabang</option>
            @foreach($branches as $id => $name)
                <option value="{{ $id }}" @selected($selected == $id)>{{ $name }}</option>
            @endforeach
        </select>
    @else
        <p class="mt-1 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 py-2 text-sm">{{ $branches->first() ?? 'Anda belum ditempatkan di cabang' }}</p>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get('branch_id')" />
</div>
