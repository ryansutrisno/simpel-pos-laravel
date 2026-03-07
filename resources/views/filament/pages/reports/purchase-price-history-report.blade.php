<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Riwayat Harga Beli</h2>
        </div>

        {{ $this->form }}

        @if(!empty($reportData) && count($reportData['items']) > 0)
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Harga Terendah</div>
                            <div class="text-2xl font-bold text-green-600">
                                Rp {{ number_format($reportData['summary']['min_price'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Harga Tertinggi</div>
                            <div class="text-2xl font-bold text-red-600">
                                Rp {{ number_format($reportData['summary']['max_price'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Harga Rata-rata</div>
                            <div class="text-2xl font-bold text-blue-600">
                                Rp {{ number_format($reportData['summary']['avg_price'], 0, ',', '.') }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <x-filament::card>
                        <div class="text-center">
                            <div class="text-gray-500 text-sm">Total Transaksi</div>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ $reportData['summary']['total_transactions'] }}
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>

            @if(!empty($trendData['trend']) && count($trendData['trend']) > 0)
                <x-filament::card>
                    <h3 class="text-lg font-semibold mb-4">Tren Harga Beli</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-semibold">Bulan</th>
                                    <th class="text-right py-3 px-4 font-semibold">Harga Min</th>
                                    <th class="text-right py-3 px-4 font-semibold">Harga Rata-rata</th>
                                    <th class="text-right py-3 px-4 font-semibold">Harga Max</th>
                                    <th class="text-right py-3 px-4 font-semibold">Jumlah PO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trendData['trend'] as $trend)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4">{{ $trend['month_label'] }}</td>
                                        <td class="py-3 px-4 text-right">Rp {{ number_format($trend['min_price'], 0, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right">Rp {{ number_format($trend['avg_price'], 0, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right">Rp {{ number_format($trend['max_price'], 0, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right">{{ $trend['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>
            @endif

            <x-filament::card>
                <h3 class="text-lg font-semibold mb-4">Detail Riwayat Harga Beli</h3>
                {{ $this->table }}
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="text-center py-8 text-gray-500">
                    Tidak ada data riwayat harga beli untuk filter yang dipilih
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
