<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Laporan Penjualan</h2>
            <div class="flex gap-2">
                <x-filament::button
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="exportExcel"
                >
                    Export Excel
                </x-filament::button>
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-document-text"
                    wire:click="exportPdf"
                >
                    Export PDF
                </x-filament::button>
            </div>
        </div>

        {{ $this->form }}

        @if(!empty($reportData))
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Penjualan</div>
                            <div class="text-2xl font-bold text-green-600">
                                Rp {{ number_format($reportData['total_sales'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Transaksi</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $reportData['total_transactions'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Profit</div>
                            <div class="text-2xl font-bold text-purple-600">
                                Rp {{ number_format($reportData['total_profit'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Rata-rata/Transaksi</div>
                            <div class="text-2xl font-bold text-orange-600">
                                Rp {{ number_format($reportData['total_transactions'] > 0 ? $reportData['total_sales'] / $reportData['total_transactions'] : 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            @if($reportData['sales_by_payment_method']->isNotEmpty())
                <x-filament::card>
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Penjualan per Metode Pembayaran</h3>
                    <div class="flex flex-wrap gap-4">
                        @foreach($reportData['sales_by_payment_method'] as $method => $data)
                            <div class="flex-1 min-w-[200px] text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="text-gray-600 dark:text-gray-300 capitalize">{{ $method }}</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($data['total'], 0, ',', '.') }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $data['count'] }} transaksi</div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            @endif

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Detail Transaksi</h3>
                {{ $this->table }}
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
