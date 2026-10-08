@php
    $allowed = ['pending', 'paid', 'rented'];
@endphp

@if(in_array($booking->status->value, $allowed))
<x-modal name="update-{{ $booking->code }}" :show="false" maxWidth="sm" focusable>
    <div class="p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
            Update Status: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $booking->code }}</span>
        </h2>

        @if($booking->status->value === 'paid')
            <!-- Paid to Rented -->
            <form action="{{ route('admin.pesanan.update', $booking->code) }}" method="POST" class="mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
                @csrf
                <input type="hidden" name="to" value="rented">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Tandai bahwa unit telah diserahkan ke penyewa dan masa sewa dimulai.</p>
                <div class="mb-3">
                    <x-input-label for="note_rented_{{ $booking->code }}" value="Catatan Serah Terima (Opsional)" />
                    <textarea id="note_rented_{{ $booking->code }}" name="note" rows="2" class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                </div>
                <x-primary-button class="w-full justify-center">Serah Terima Unit</x-primary-button>
            </form>
            
            <!-- Paid to Cancelled -->
            <form action="{{ route('admin.pesanan.update', $booking->code) }}" method="POST">
                @csrf
                <input type="hidden" name="to" value="cancelled">
                <div class="mb-3">
                    <x-input-label for="reason_paid_{{ $booking->code }}" value="Alasan Pembatalan *" />
                    <textarea id="reason_paid_{{ $booking->code }}" name="reason" rows="2" required class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">Catat kesepakatan refund dari chat WA</p>
                </div>
                <x-danger-button class="w-full justify-center">Batalkan Pesanan</x-danger-button>
            </form>

        @elseif($booking->status->value === 'pending')
            <!-- Pending to Cancelled -->
            <form action="{{ route('admin.pesanan.update', $booking->code) }}" method="POST">
                @csrf
                <input type="hidden" name="to" value="cancelled">
                <div class="mb-3">
                    <x-input-label for="reason_pending_{{ $booking->code }}" value="Alasan Pembatalan *" />
                    <textarea id="reason_pending_{{ $booking->code }}" name="reason" rows="2" required class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                </div>
                <x-danger-button class="w-full justify-center">Batalkan Pesanan</x-danger-button>
            </form>

        @elseif($booking->status->value === 'rented')
            <!-- Rented to Returned -->
            <form action="{{ route('admin.pesanan.update', $booking->code) }}" method="POST">
                @csrf
                <input type="hidden" name="to" value="returned">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Tandai bahwa unit telah dikembalikan oleh penyewa.</p>
                <div class="mb-3">
                    <x-input-label for="note_returned_{{ $booking->code }}" value="Catatan Kondisi (Opsional)" />
                    <textarea id="note_returned_{{ $booking->code }}" name="note" rows="2" class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                </div>
                <x-primary-button class="w-full justify-center">Unit Sudah Kembali</x-primary-button>
            </form>
        @endif
        
        <div class="mt-4 flex justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Tutup</button>
        </div>
    </div>
</x-modal>
@endif