<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Actions --}}
        <div class="flex flex-wrap gap-3">
            <x-filament::button
                wire:click="createBackup"
                wire:loading.attr="disabled"
                icon="heroicon-m-plus"
                color="primary"
            >
                <span wire:loading.remove wire:target="createBackup">Buat Backup Database</span>
                <span wire:loading wire:target="createBackup">Memproses...</span>
            </x-filament::button>

            <x-filament::button
                wire:click="createFullBackup"
                wire:loading.attr="disabled"
                icon="heroicon-m-archive-box"
                color="gray"
            >
                <span wire:loading.remove wire:target="createFullBackup">Backup Lengkap</span>
                <span wire:loading wire:target="createFullBackup">Memproses...</span>
            </x-filament::button>

            <x-filament::button
                wire:click="cleanup"
                wire:loading.attr="disabled"
                icon="heroicon-m-trash"
                color="warning"
            >
                <span wire:loading.remove wire:target="cleanup">Bersihkan Backup Lama</span>
                <span wire:loading wire:target="cleanup">Membersihkan...</span>
            </x-filament::button>
        </div>

        {{-- Backup List Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Daftar Backup
            </x-slot>

            @php
                $backups = $this->getBackups();
            @endphp

            @if(count($backups) === 0)
                <div class="text-center py-8">
                    <x-heroicon-o-server-stack class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada backup</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Buat backup pertama dengan tombol di atas.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Nama File</th>
                                <th class="px-6 py-3">Ukuran</th>
                                <th class="px-6 py-3">Tanggal Dibuat</th>
                                <th class="px-6 py-3">Usia</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-archive-box class="w-5 h-5 text-primary-500" />
                                            {{ $backup['name'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ $this->formatBytes($backup['size']) }}</td>
                                    <td class="px-6 py-4">{{ $backup['created_at']->format('d M Y H:i:s') }}</td>
                                    <td class="px-6 py-4">{{ $backup['created_at']->diffForHumans() }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Download --}}
                                            <a
                                                href="{{ route('backup.download', ['file' => $backup['path']]) }}"
                                                target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                                title="Download"
                                            >
                                                <x-heroicon-m-arrow-down-tray class="w-5 h-5" />
                                            </a>

                                            {{-- Restore --}}
                                            <button
                                                wire:click="restore('{{ $backup['name'] }}')"
                                                wire:confirm="Anda akan merestore database dari backup: {{ $backup['name'] }}. Semua data saat ini akan diganti! Pastikan sudah backup terlebih dahulu."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center w-8 h-8 text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                                                title="Restore"
                                            >
                                                <x-heroicon-m-arrow-uturn-left class="w-5 h-5" />
                                            </button>

                                            {{-- Delete --}}
                                            <button
                                                wire:click="deleteBackup('{{ $backup['path'] }}')"
                                                wire:confirm="Apakah Anda yakin ingin menghapus backup ini?"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                title="Hapus"
                                            >
                                                <x-heroicon-m-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Info Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Informasi Backup
            </x-slot>
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <p><strong>Lokasi Backup:</strong> <code>storage/app/backups/Laravel/</code></p>
                <p><strong>Jadwal Otomatis:</strong> Setiap hari pukul 02:00 AM</p>
                <p><strong>Retention:</strong> Backup disimpan selama 7 hari</p>
                <p><strong>Perintah Manual:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li><code>php artisan backup:run --only-db</code> - Backup database saja</li>
                    <li><code>php artisan backup:run</code> - Backup lengkap (database + files)</li>
                    <li><code>php artisan backup:restore {filename}</code> - Restore database</li>
                    <li><code>php artisan backup:clean</code> - Bersihkan backup lama</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
