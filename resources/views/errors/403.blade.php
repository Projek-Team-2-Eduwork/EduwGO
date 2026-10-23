<x-app-layout>
    <x-slot:title>Akses ditolak</x-slot:title>

    @include('errors.partials.content', ['code' => '403', 'heading' => 'Akses ditolak', 'message' => 'Kamu tidak punya izin untuk membuka halaman ini. Masuk dengan akun yang sesuai atau kembali ke beranda.'])
</x-app-layout>
