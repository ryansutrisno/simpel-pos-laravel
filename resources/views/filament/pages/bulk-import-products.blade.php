<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Instructions Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Petunjuk Import Produk
            </x-slot>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <ol class="list-decimal list-inside space-y-1">
                    <li>Download template Excel menggunakan tombol di bawah</li>
                    <li>Isi data produk sesuai dengan format yang ditentukan</li>
                    <li>Upload file Excel yang sudah diisi</li>
                    <li>Klik tombol "Import" untuk memulai proses import</li>
                </ol>
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="font-medium text-blue-800 dark:text-blue-200">
                        <x-heroicon-o-information-circle class="inline w-5 h-5 mr-1" />
                        Format Kolom:
                    </p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li><code>nama_produk</code> (wajib) - Nama produk minimal 3 karakter</li>
                        <li><code>harga_beli</code> (wajib) - Harga beli (angka)</li>
                        <li><code>harga_jual</code> (wajib) - Harga jual (angka)</li>
                        <li><code>stok</code> (wajib) - Jumlah stok (integer)</li>
                        <li><code>sku</code> (opsional) - Kode produk</li>
                        <li><code>barcode</code> (opsional) - Barcode produk</li>
                        <li><code>kategori</code> (opsional) - Nama kategori</li>
                        <li><code>reorder_point</code> (opsional) - Titik pesan ulang</li>
                        <li><code>deskripsi</code> (opsional) - Deskripsi produk</li>
                        <li><code>is_active</code> (opsional) - Status aktif (true/false)</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        {{-- Download Template & Upload --}}
        <x-filament::section>
            <x-slot name="heading">
                Upload File Import
            </x-slot>

            <div class="space-y-6">
                {{-- Download Template Button --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                            <x-heroicon-o-document-arrow-down class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Template Import Produk</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Format Excel (.xlsx)</p>
                        </div>
                    </div>
                    <a
                        href="{{ route("import-template.download") }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-m-arrow-down-tray class="w-5 h-5" />
                        Download Template
                    </a>
                </div>

                {{-- Drag & Drop Zone --}}
                <div
                    x-data="{ isDragging: false }"
                    x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false"
                    x-on:drop="$el.querySelector('input[type=file]').files = $event.dataTransfer.files; $el.querySelector('input[type=file]').dispatchEvent(new Event('change'))"
                    class="relative"
                >
                    <label
                        class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200"
                        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                    >
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            @if($file)
                                <div class="flex flex-col items-center">
                                    <x-heroicon-o-document-check class="w-12 h-12 text-green-500 mb-2" />
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $file->getClientOriginalName() }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($file->getSize() / 1024, 2) }} KB</p>
                                    <button
                                        wire:click="$set('file', null)"
                                        class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Hapus file
                                    </button>
                                </div>
                            @else
                                <x-heroicon-o-cloud-arrow-up class="w-12 h-12 text-gray-400 mb-3" />
                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold">Klik untuk upload</span> atau drag & drop
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Format yang didukung: .xlsx
                                </p>
                            @endif
                        </div>
                        <input
                            type="file"
                            class="hidden"
                            wire:model.live="file"
                            accept=".xlsx"
                        />
                    </label>
                </div>

                {{-- Progress Bar --}}
                @if($isImporting || $progress > 0)
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Progress Import</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $progress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div
                                class="bg-primary-600 h-2.5 rounded-full transition-all duration-300"
                                :style="'width: ' + {{ $progress }} + '%'"
                            ></div>
                        </div>
                        @if($statusMessage)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $statusMessage }}</p>
                        @endif
                    </div>
                @endif

                {{-- Import Button --}}
                <div class="flex justify-end">
                    <x-filament::button
                        wire:click="import"
                        wire:loading.attr="disabled"
                        wire:target="import, file"
                        icon="heroicon-m-arrow-up-tray"
                        color="primary"
                        :disabled="!$file || $isImporting"
                    >
                        <span wire:loading.remove wire:target="import">
                            @if($file)
                                Import {{ $file->getClientOriginalName() }}
                            @else
                                Import Produk
                            @endif
                        </span>
                        <span wire:loading wire:target="import">
                            Mengimport...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Status Messages --}}
        @if($statusMessage && !$isImporting)
            <x-filament::section>
                <div class="flex items-start gap-3">
                    @if(str_contains($statusMessage, 'berhasil'))
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 flex-shrink-0" />
                        <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $statusMessage }}</div>
                    @else
                        <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-500 flex-shrink-0" />
                        <div class="text-sm text-red-700 dark:text-red-300 whitespace-pre-line">{{ $statusMessage }}</div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
