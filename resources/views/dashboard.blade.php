<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-3xl">Dashboard</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-empty-state
            title="Selamat datang kembali"
            description="Mulai sewa motor atau cek pesanan kamu."
            action-label="Pilih Kendaraan"
            :action-href="url('/kendaraan')"
        />
    </div>
</x-app-layout>
