<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-navy-900 py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="card-surface bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 md:p-10 text-center">
                
                <div class="flex justify-center mb-6">
                    <svg class="w-20 h-20 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Ups, Waktunya Habis!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-2">Pesanan Anda tidak dibayar dalam batas waktu dan telah dibatalkan.</p>
                <p class="text-lg font-mono font-semibold text-gray-800 dark:text-gray-200 mb-8 bg-gray-100 dark:bg-gray-700 inline-block px-4 py-2 rounded">{{ $booking->code }}</p>
                
                <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4">
                    <a href="{{ route('booking.rebook', $booking->code) }}" class="btn-primary-edu px-6 py-3 rounded-lg text-center">
                        Booking Ulang
                    </a>
                    <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}?text={{ urlencode('Halo, saya ingin bertanya soal pesanan ' . $booking->code) }}" target="_blank" class="btn-secondary-edu px-6 py-3 rounded-lg text-center flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.015c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Chat Admin
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>