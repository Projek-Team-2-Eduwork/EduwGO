@if($booking->status->value === 'pending')
    <div x-data="{ open: false }"
         @open-modal.window="if ($event.detail === 'cash-{{ $booking->code }}') open = true"
         @close-modal.window="if ($event.detail === 'cash-{{ $booking->code }}') open = false"
         x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="open" @click="open = false" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80"></div>

            <div x-show="open" x-transition class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Catat Pembayaran Tunai</h3>

                <form action="{{ route('admin.pesanan.pay-cash', $booking->code) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="amount" value="Nominal (Rp)" />
                            <x-text-input id="amount" name="amount" type="number" class="block w-full mt-1" :value="$booking->total_amount" required min="0" />
                        </div>
                        <div>
                            <x-input-label for="note" value="Catatan (opsional)" />
                            <textarea id="note" name="note" rows="3" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-3">
                        <x-primary-button class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700">Simpan Pembayaran</x-primary-button>
                        <button type="button" @click="$dispatch('close-modal', 'cash-{{ $booking->code }}')" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
