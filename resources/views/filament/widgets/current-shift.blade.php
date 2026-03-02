<x-filament-widgets::widget class="fi-wi-stats-overview">
    @php
        $activeShift = $this->getActiveShift();
        $summary = $this->getShiftSummary();
    @endphp

    @if($activeShift)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-success-500"/>
                        Shift Aktif - {{ $activeShift->shift_type === 'morning' ? 'Pagi' : 'Sore' }}
                    </div>
                    <span class="text-sm text-gray-500">
                        {{ $activeShift->opened_at->format('H:i') }} - Sekarang
                    </span>
                </div>
            </x-slot>

            <div class="flex flex-row gap-4 w-full">
                <div class="flex-1 p-3 bg-primary-50 rounded-lg min-w-0">
                    <div class="text-xs text-gray-600 mb-1">Total Penjualan</div>
                    <div class="text-lg font-bold text-primary-600 truncate">
                        Rp {{ number_format($summary['total_sales'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>

                <div class="flex-1 p-3 bg-success-50 rounded-lg min-w-0">
                    <div class="text-xs text-gray-600 mb-1">Jumlah Transaksi</div>
                    <div class="text-lg font-bold text-success-600 truncate">
                        {{ $summary['total_transactions'] ?? 0 }}
                    </div>
                </div>

                <div class="flex-1 p-3 bg-warning-50 rounded-lg min-w-0">
                    <div class="text-xs text-gray-600 mb-1">Penjualan Tunai</div>
                    <div class="text-lg font-bold text-warning-600 truncate">
                        Rp {{ number_format($summary['cash_sales'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>

                <div class="flex-1 p-3 bg-danger-50 rounded-lg min-w-0">
                    <div class="text-xs text-gray-600 mb-1">Pengeluaran</div>
                    <div class="text-lg font-bold text-danger-600 truncate">
                        Rp {{ number_format($summary['total_expenses'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end">
                    <a href="{{ \App\Filament\Resources\ShiftResource::getUrl('edit', ['record' => $activeShift]) }}"
                       class="text-sm text-danger-600 hover:text-danger-700 font-medium">
                        Tutup Shift →
                    </a>
                </div>
            </x-slot>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/>
                    Tidak Ada Shift Aktif
                </div>
            </x-slot>

            <div class="text-center py-6">
                <p class="text-gray-500 mb-4">Anda belum membuka shift untuk hari ini.</p>
                <a href="{{ \App\Filament\Resources\ShiftResource::getUrl('create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <x-heroicon-o-play class="w-4 h-4"/>
                    Buka Shift Sekarang
                </a>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
