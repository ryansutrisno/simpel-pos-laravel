<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Nilai Inventory</h2>
            @if(!empty($reportData) && count($reportData['items']) > 0)
                <div class="flex gap-2">
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="exportExcel"
                    >
                        Export Excel
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{ $this->form }}

        @if(!empty($reportData))
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Metode</div>
                            <div class="text-lg font-bold">{{ $reportData['method_label'] }}</div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Produk</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $reportData['total_products'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Qty</div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ number_format($reportData['total_quantity']) }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Nilai Inventory</div>
                            <div class="text-2xl font-bold text-purple-600">
                                Rp {{ number_format($reportData['total_value'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Detail Inventory</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">No</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Produk</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">SKU</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Kategori</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-900 dark:text-white">Qty</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-900 dark:text-white">Harga Satuan</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-900 dark:text-white">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['items'] as $index => $item)
                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $item['product']->name }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $item['sku'] ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $item['category'] ?? '-' }}</td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ number_format($item['quantity']) }}</td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">Rp {{ number_format($item['unit_cost'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">Rp {{ number_format($item['total_value'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                                <td class="py-3 px-4 text-gray-900 dark:text-white" colspan="4">TOTAL</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ number_format($reportData['total_quantity']) }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">-</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">Rp {{ number_format($reportData['total_value'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="text-center py-8 text-gray-500">
                    Silakan pilih filter dan klik tombol untuk menghasilkan laporan
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
