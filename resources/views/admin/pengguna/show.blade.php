<x-admin-layout title="Detail pengguna">
    <x-slot name="header">
        <h2 class="truncate text-xl font-semibold leading-tight">{{ $pengguna->name }}</h2>
    </x-slot>

    @php($isStaff = $pengguna->isStaff())
    <div class="mx-auto max-w-7xl space-y-5 py-2 sm:space-y-6 sm:py-6">
        @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

        <a href="{{ route('admin.pengguna.index', ['tab' => $isStaff ? 'staf' : 'penyewa']) }}" class="text-sm text-[var(--accent)] hover:underline">&larr; Kembali ke {{ $isStaff ? 'staf' : 'penyewa' }}</a>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="card-surface p-4 sm:p-5" aria-labelledby="data-pengguna">
                <h3 id="data-pengguna" class="mb-2 font-bold">Data {{ $isStaff ? 'staf' : 'penyewa' }}</h3>
                <dl class="receipt text-sm">
                    <div><dt>Email</dt><dd>{{ $pengguna->email }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $pengguna->phone ?? '-' }}</dd></div>
                    <div><dt>Role</dt><dd>@include('admin.pengguna.partials.role-badge', ['user' => $pengguna])</dd></div>
                    <div><dt>Terdaftar</dt><dd>{{ $pengguna->created_at->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Status</dt><dd class="{{ $pengguna->is_active ? 'text-[var(--success)]' : 'text-[var(--danger)]' }} font-semibold">{{ $pengguna->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div><dt>Total booking</dt><dd>{{ $pengguna->bookings_count }}</dd></div>
                </dl>

                @unless($pengguna->is(auth()->user()))
                    <div class="mt-5 flex flex-wrap gap-3">
                        @if($isStaff)
                            <form action="{{ route('admin.pengguna.reset-password', $pengguna) }}" method="POST">
                                @csrf
                                <button class="btn-secondary-edu inline-flex items-center px-4 text-sm">Kirim link reset password</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.pengguna.toggle', $pengguna->id) }}" method="POST">
                            @csrf
                            <button class="inline-flex items-center px-2 text-sm font-medium {{ $pengguna->is_active ? 'text-[var(--danger)]' : 'text-[var(--success)]' }} hover:underline">{{ $pengguna->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
                        </form>
                    </div>
                @endunless
            </section>

            <section class="card-surface lg:col-span-2" aria-labelledby="riwayat-pesanan">
                <h3 id="riwayat-pesanan" class="border-b border-[var(--line)] px-4 py-4 font-bold sm:px-5">Riwayat pesanan</h3>
                @if($bookings->isEmpty())
                    <p class="px-4 py-8 text-center text-sm sm:px-5">Belum ada pesanan.</p>
                @else
                    <ul class="divide-y divide-[var(--line)]">
                        @foreach($bookings as $b)
                            <li>
                                <a href="{{ route('admin.pesanan.show', $b->code) }}" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 px-4 py-3 hover:bg-[var(--bg)] sm:gap-x-4 sm:gap-y-1 sm:px-5">
                                    <span class="order-1 min-w-0 flex-1 truncate font-mono text-sm font-semibold text-[var(--ink)] sm:order-none sm:w-32 sm:flex-none">{{ $b->code }}</span>
                                    <span class="order-3 min-w-0 flex-1 truncate text-sm sm:order-none">{{ $b->vehicle?->name ?? 'Kendaraan dihapus' }} · {{ $b->start_at->translatedFormat('d M Y H:i') }}</span>
                                    <x-badge-status :status="$b->status" class="order-4 shrink-0 sm:order-none" />
                                    <span class="order-2 shrink-0 text-right text-sm font-semibold text-[var(--ink)] sm:order-none sm:w-28">{{ rupiah($b->total_amount) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-[var(--line)] p-4">{{ $bookings->links('components.pagination') }}</div>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>
