<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Tutup Kasir (End of Day)</h2> @if($existingRecord) <x-filament::button
                color="danger" icon="heroicon-o-document-text" wire:click="exportPdf"> Export PDF </x-filament::button>
            @endif
        </div> {{ $this->form }} @if(!empty($summary)) <div
            style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
            <div
                class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="text-center">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Total Penjualan</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400"> Rp {{
                        number_format($summary['total_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['total_transactions'] }} transaksi
                    </div>
                </div>
            </div>
            <div
                class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="text-center">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Total Profit</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"> Rp {{
                        number_format($summary['total_profit'], 0, ',', '.') }} </div>
                    <div class="text-sm invisible">-</div>
                </div>
            </div>
            <div
                class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="text-center">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Saldo Awal</div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-300"> Rp {{
                        number_format($summary['opening_balance'], 0, ',', '.') }} </div>
                    <div class="text-sm invisible">-</div>
                </div>
            </div>
            <div
                class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="text-center">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Expected Cash</div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400"> Rp {{
                        number_format($summary['expected_cash'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Saldo awal + penjualan cash</div>
                </div>
            </div>
        </div>
        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4 dark:text-white">Breakdown per Metode Pembayaran</h3>
            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-green-700 dark:text-green-400 font-semibold">Cash</div>
                    <div class="text-xl font-bold text-green-700 dark:text-green-400"> Rp {{
                        number_format($summary['total_cash_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['cash_transactions'] }} transaksi
                    </div>
                </div>
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-blue-700 dark:text-blue-400 font-semibold">Transfer</div>
                    <div class="text-xl font-bold text-blue-700 dark:text-blue-400"> Rp {{
                        number_format($summary['total_transfer_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['transfer_transactions'] }}
                        transaksi</div>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="text-purple-700 dark:text-purple-400 font-semibold">QRIS</div>
                    <div class="text-xl font-bold text-purple-700 dark:text-purple-400"> Rp {{
                        number_format($summary['total_qris_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['qris_transactions'] }} transaksi
                    </div>
                </div>
                <div class="text-center p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                    <div class="text-indigo-700 dark:text-indigo-400 font-semibold">Digital</div>
                    <div class="text-xl font-bold text-indigo-700 dark:text-indigo-400"> Rp {{
                        number_format($summary['total_digital_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['digital_transactions'] }} transaksi
                    </div>
                </div>
                <div class="text-center p-4 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg">
                    <div class="text-cyan-700 dark:text-cyan-400 font-semibold">Invoice</div>
                    <div class="text-xl font-bold text-cyan-700 dark:text-cyan-400"> Rp {{
                        number_format($summary['total_invoice_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['invoice_transactions'] }} transaksi
                    </div>
                </div>
                <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <div class="text-amber-700 dark:text-amber-400 font-semibold">Multi Payment</div>
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400"> Rp {{
                        number_format($summary['total_multi_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['multi_transactions'] }} transaksi
                    </div>
                </div>
                <div class="text-center p-4 bg-rose-50 dark:bg-rose-900/20 rounded-lg">
                    <div class="text-rose-700 dark:text-rose-400 font-semibold">Split Bill</div>
                    <div class="text-xl font-bold text-rose-700 dark:text-rose-400"> Rp {{
                        number_format($summary['total_split_sales'], 0, ',', '.') }} </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $summary['split_transactions'] }} transaksi
                    </div>
                </div>
            </div>
        </x-filament::card> @if(!$existingRecord) <x-filament::card>
            <h3 class="text-lg font-semibold mb-4 dark:text-white">Hitung Selisih</h3>
            <div
                style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; align-items: center;">
                <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Expected Cash</div>
                    <div class="text-xl font-bold dark:text-white"> Rp {{ number_format($summary['expected_cash'], 0,
                        ',', '.') }} </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl dark:text-white">-</div>
                </div>
                <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Actual Cash (Input)</div>
                    <div
                        class="text-xl font-bold {{ $actualCash ? 'dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        Rp {{ number_format($actualCash ?? 0, 0, ',', '.') }} </div>
                </div>
            </div> @if($actualCash !== null) <div
                class="mt-4 text-center p-4 {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} rounded-lg">
                <div
                    class="{{ ($actualCash - $summary['expected_cash']) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }} text-sm">
                    {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'SELISIH LEBIH' : 'SELISIH KURANG' }} </div>
                <div
                    class="text-2xl font-bold {{ ($actualCash - $summary['expected_cash']) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    Rp {{ number_format(abs($actualCash - $summary['expected_cash']), 0, ',', '.') }} </div>
            </div> @endif
        </x-filament::card>
        <x-filament::card>
            <x-filament::button color="success" size="lg" wire:click="save" class="w-full"> Simpan & Tutup Kasir
            </x-filament::button>
        </x-filament::card> @else <x-filament::card>
            <div class="text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-green-700 dark:text-green-400 text-lg font-semibold">Kasir Sudah Ditutup</div>
                <div class="text-gray-600 dark:text-gray-400 mt-2"> Ditutup oleh: {{ $existingRecord->user->name }} pada
                    {{ $existingRecord->updated_at->format('d/m/Y H:i') }} </div> @if($existingRecord->notes) <div
                    class="text-gray-500 dark:text-gray-400 mt-2">Catatan: {{ $existingRecord->notes }}</div> @endif
            </div>
            <div class="mt-4" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Expected Cash</div>
                    <div class="text-xl font-bold dark:text-white"> Rp {{ number_format($existingRecord->expected_cash,
                        0, ',', '.') }} </div>
                </div>
                <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="text-gray-600 dark:text-gray-400 text-sm">Actual Cash</div>
                    <div class="text-xl font-bold dark:text-white"> Rp {{ number_format($existingRecord->actual_cash, 0,
                        ',', '.') }} </div>
                </div>
                <div
                    class="text-center p-4 {{ $existingRecord->difference >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} rounded-lg">
                    <div
                        class="{{ $existingRecord->difference >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }} text-sm">
                        {{ $existingRecord->difference >= 0 ? 'Selisih Lebih' : 'Selisih Kurang' }} </div>
                    <div
                        class="text-xl font-bold {{ $existingRecord->difference >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                        Rp {{ number_format(abs($existingRecord->difference), 0, ',', '.') }} </div>
                </div>
            </div>
        </x-filament::card> @endif @endif
    </div>
</x-filament-panels::page>