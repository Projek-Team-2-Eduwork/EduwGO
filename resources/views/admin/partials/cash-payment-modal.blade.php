@if($booking->status->value === 'pending')
    @php($isCod = $codPending ?? false)

    <div x-data="{ open: false }"
         @open-modal.window="if ($event.detail === 'cash-{{ $booking->code }}') open = true"
         @close-modal.window="if ($event.detail === 'cash-{{ $booking->code }}') open = false"
         x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="open" @click="open = false" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80"></div>

            <div x-show="open" x-transition class="inline-block w-full max-w-lg overflow-hidden px-4 pb-4 pt-5 text-left align-bottom transition-all transform card-surface sm:my-8 sm:align-middle sm:w-full sm:p-6">
                <h3 class="mb-2 text-lg font-semibold text-[var(--navy-900)]">{{ $isCod ? 'Tandai Pembayaran COD' : 'Catat Pembayaran Tunai' }}</h3>

                @if($isCod)
                    <p class="mb-4 text-sm">Konfirmasi bahwa uang tunai sudah diterima di cabang. Status pesanan akan berubah menjadi <strong>Lunas</strong>.</p>
                @endif

                <form action="{{ route('admin.pesanan.pay-cash', $booking->code) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="amount_{{ $booking->code }}" value="Nominal (Rp)" />
                            <x-text-input id="amount_{{ $booking->code }}" name="amount" type="number" class="block w-full mt-1" :value="(int) $booking->total_amount" required min="1" step="1" />
                        </div>
                        <div>
                            <x-input-label for="note_{{ $booking->code }}" value="Catatan (opsional)" />
                            <textarea id="note_{{ $booking->code }}" name="note" rows="3" class="mt-1 block w-full rounded-md shadow-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-3">
                        <x-primary-button class="w-full sm:w-auto">{{ $isCod ? 'Tandai Lunas' : 'Simpan Pembayaran' }}</x-primary-button>
                        <x-secondary-button @click="$dispatch('close-modal', 'cash-{{ $booking->code }}')" class="mt-3 w-full sm:mt-0 sm:w-auto">Batal</x-secondary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
