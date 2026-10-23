<x-app-layout>
    <x-slot:title>Sesi telah berakhir</x-slot:title>

    @include('errors.partials.content', ['code' => '419', 'heading' => 'Sesi telah berakhir', 'message' => 'Sesi kamu sudah habis. Muat ulang halaman sebelumnya lalu coba lagi.'])
</x-app-layout>
