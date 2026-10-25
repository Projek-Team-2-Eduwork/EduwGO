<x-app-layout title="Halaman tidak ditemukan">
    <x-slot:title>Halaman tidak ditemukan</x-slot:title>

    @include('errors.partials.content', ['code' => '404', 'heading' => 'Halaman tidak ditemukan', 'message' => 'Halaman yang Anda cari tidak ada atau sudah dipindahkan. Periksa kembali alamatnya atau kembali ke beranda.'])
</x-app-layout>
