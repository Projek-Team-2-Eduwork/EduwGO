{{-- Filter cabang khusus superadmin ($branches kosong untuk admin cabang, yang terkunci ke cabangnya). --}}
@if (isset($branches) && $branches->isNotEmpty())
    <div class="{{ $class ?? '' }}">
        <x-input-label for="branch" value="Cabang" />
        <select id="branch" name="branch" class="mt-1 w-full rounded-md text-sm shadow-sm">
            <option value="">Semua Cabang</option>
            @foreach ($branches as $id => $name)
                <option value="{{ $id }}" @selected((string) request('branch') === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
@endif
