<div x-data="{ polling: null }" x-init="
    polling = setInterval(() => { $wire.checkStatus() }, 5000);
    $wire.on('paymentSuccess', () => { clearInterval(polling); });
    $wire.on('closeModal', () => { clearInterval(polling); });
" x-on:close-modal="clearInterval(polling)" class="relative">
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="close"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pembayaran Digital
                                </h3>

                                @if($error)
                                    <div class="mt-4 rounded-md bg-red-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                                <div class="mt-2 text-sm text-red-700">
                                                    <p>{{ $error }}</p>
                                                </div>
                                                <div class="mt-4">
                                                    <button wire:click="retry" type="button" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                                        Coba Lagi
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($isLoading)
                                    <div class="mt-6 flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-500">Memuat pembayaran...</p>
                                    </div>
                                @elseif($status === 'paid')
                                    <div class="mt-6 flex flex-col items-center justify-center">
                                        <div class="rounded-full bg-green-100 p-4">
                                            <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <h4 class="mt-4 text-xl font-semibold text-green-600">Pembayaran Berhasil!</h4>
                                        <p class="mt-2 text-sm text-gray-500">Terima kasih atas pembayaran Anda.</p>
                                        <button wire:click="printReceipt" type="button" class="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Cetak Struk
                                        </button>
                                    </div>
                                @elseif($status === 'expired')
                                    <div class="mt-6 flex flex-col items-center justify-center">
                                        <div class="rounded-full bg-yellow-100 p-4">
                                            <svg class="h-12 w-12 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="mt-4 text-xl font-semibold text-yellow-600">Pembayaran Kadaluarsa</h4>
                                        <p class="mt-2 text-sm text-gray-500">Waktu pembayaran telah habis.</p>
                                        <button wire:click="retry" type="button" class="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                                            Generate Ulang
                                        </button>
                                    </div>
                                @else
                                    @if($paymentMethod === 'qris' && $qrImageUrl)
                                        <div class="mt-6 flex flex-col items-center">
                                            <p class="text-sm text-gray-600 mb-4">Scan QR code berikut menggunakan aplikasi pembayaran Anda:</p>
                                            <div class="bg-white p-4 rounded-lg border-2 border-gray-200">
                                                <img src="{{ $qrImageUrl }}" alt="QRIS Payment" class="w-64 h-64 object-contain" />
                                            </div>
                                            <div class="mt-4 flex items-center text-sm text-gray-500">
                                                <svg class="animate-spin mr-2 h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Menunggu pembayaran...
                                            </div>
                                        </div>
                                    @elseif($paymentMethod === 'invoice' && $paymentUrl)
                                        <div class="mt-6 flex flex-col items-center">
                                            <p class="text-sm text-gray-600 mb-4">Klik tombol di bawah untuk melanjutkan ke halaman pembayaran:</p>
                                            <a href="{{ $paymentUrl }}" target="_blank" class="inline-flex items-center rounded-md bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                                                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                Buka Halaman Pembayaran
                                            </a>
                                            <div class="mt-4 flex items-center text-sm text-gray-500">
                                                <svg class="animate-spin mr-2 h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Menunggu pembayaran...
                                            </div>
                                        </div>
                                    @endif

                                    @if($expiresAt)
                                        <div class="mt-4 text-center text-xs text-gray-400">
                                            Berlaku sampai: {{ \Carbon\Carbon::parse($expiresAt)->format('d M Y H:i') }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button wire:click="close" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
