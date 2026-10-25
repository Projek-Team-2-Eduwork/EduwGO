<x-app-layout title="Akses ditolak">
    <x-slot:title>Akses ditolak</x-slot:title>

    @include('errors.partials.content', ['code' => '403', 'heading' => 'Akses ditolak', 'message' => 'Anda tidak punya izin untuk membuka halaman ini. Masuk dengan akun yang sesuai atau kembali ke beranda.'])
</x-app-layout>
