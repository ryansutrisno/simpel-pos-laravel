<div class="flex flex-col h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Point of Sale</h1>
                    <span id="printer-status" class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-300">
                        <svg id="printer-icon-status" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="currentColor" class="w-4 h-4 text-red-500">
                            <path fill-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span id="printer-status-text">Printer tidak terhubung</span>
                    </span>
                </div>
                <div class="flex items-center gap-3 mt-2 md:mt-0">
                    <div class="text-sm text-gray-600 dark:text-gray-300"> Kasir: {{ Auth::user()->name }} </div>
                    <button onclick="window.connectPrinter()"
                        class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-4 h-4">
                            <path
                                d="M7 17v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 17v2a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2M7 17H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1m0 0V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2M6 7h12m0 0h1a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2" />
                        </svg> Hubungkan Printer </button>
                    <a href="{{ route('filament.admin.pages.end-of-day-report') }}"
                        class="flex items-center gap-1 px-3 py-1.5 bg-orange-600 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-orange-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg> Tutup Kasir </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        <!-- Kolom Kiri - Produk -->
        <div class="w-full md:w-3/4 p-4 overflow-y-auto">
            <!-- Barcode Scanner Input -->
            <div class="mb-4">
                <div class="relative" x-data="{ focused: false }"
                    x-init="setTimeout(() => { $refs.barcodeInput.focus(); }, 100)">
                    <input type="text" wire:model.live="barcodeInput" x-ref="barcodeInput"
                        placeholder="Scan barcode di sini..."
                        class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-dashed border-primary-400 dark:border-primary-600 bg-primary-50 dark:bg-primary-900/20 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 text-sm font-medium"
                        @focus="focused = true" @blur="focused = false">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-primary-500 dark:text-primary-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" x-show="focused">
                        <span class="text-xs text-primary-500 dark:text-primary-400">Tekan Enter</span>
                    </div>
                </div>
            </div>
            <!-- Tab Kategori -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-4">
                <div class="flex overflow-x-auto p-2 space-x-2 gap-1">
                    <button wire:click="filterProductsByCategory()"
                        @class([ 'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap'
                        , 'bg-primary-600 text-white'=> is_null($selectedCategoryId), 'bg-gray-100 dark:bg-gray-700
                        text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                        !is_null($selectedCategoryId), ])> Semua Produk </button>
                @foreach($categories as $category)
                    <button wire:click="filterProductsByCategory({{ $category->id }})"
                        @class([ 'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap'
                        , 'bg-primary-600 text-white'=> $selectedCategoryId === $category->id, 'bg-gray-100
                        dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                        $selectedCategoryId !== $category->id, ])> {{ $category->name }} </button>
                @endforeach
                </div>
            </div>
            <!-- Search Bar -->
            <div class="mb-4">
                <div class="relative">
                    <input type="text" wire:model.live="searchQuery" placeholder="Cari produk..."
                        class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <!-- Grid Produk -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @forelse($products as $product)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow p-2">
                    <div class="aspect-w-1 aspect-h-1 mb-2">
                @if($product->image)
                <img
                            src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                            class="w-full h-32 object-cover rounded-lg">
                @else
                <div
                            class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                @endif </div>
                    <div class="flex items-start justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate flex-1">{{ $product->name }}</h3>
                        @if($product->variants()->where('is_active', true)->exists())
                        <span class="text-[10px] bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded">Varian</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Stok: {{ $product->stock }}</p>
                    <div class="mt-1 flex justify-between items-center">
                        <span class="text-primary-600 dark:text-primary-400 font-medium text-sm">Rp {{
                            number_format($product->selling_price, 0, ',', '.') }}</span>
                        <button wire:click="addToCart({{ $product->id }})"
                            @class([ 'px-2 py-1 rounded text-xs font-medium'
                            , 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600'=>
                            $product->stock > 0, 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500
                            cursor-not-allowed' => $product->stock <= 0, ]) @disabled($product->stock <= 0)> {{
                                    $product->stock > 0 ? ('+') : 'Habis' }} </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">Tidak ada produk tersedia</div>
                </div>
                @endforelse </div>
        </div>
        <!-- Kolom Kanan - Keranjang -->
        <div
            class="w-full md:w-1/4 bg-white dark:bg-gray-800 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 flex flex-col h-[40vh] md:h-auto">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Keranjang Belanja</h2>
                    <div class="flex items-center gap-2">
                        <button wire:click="loadSuspendedTransactions"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Transaksi Tertangguh">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </button>
                @if(count($cart) > 0)
                <button wire:click="holdTransaction"
                            class="p-1.5 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors"
                            title="Tangguhkan Transaksi">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                @endif
                    </div>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                @if(count($cart) > 0)
                <div class="space-y-3">
                @foreach($cart as $index => $item)
                <div
                        class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <div class="flex-1 min-w-0 pr-4">
                            <h4 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $item['name'] }}
                            </h4>
                @if(($item['discount_amount'] ?? 0) > 0)
                <p
                                class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="line-through">Rp {{ number_format($item['original_price'] ??
                                    $item['selling_price'], 0, ',', '.') }}</span>
                                <span class="text-green-600 dark:text-green-400 ml-1">Rp {{
                                    number_format($item['final_price'] ?? $item['selling_price'], 0, ',', '.') }}</span>
                            </p>
                @else
                <p class="text-xs text-gray-500 dark:text-gray-400">Rp {{
                                number_format($item['selling_price'], 0, ',', '.') }}</p>
                @endif
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button wire:click="updateQuantity({{ $index }}, 'decrease')"
                                class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 12H4" />
                                </svg>
                            </button>
                            <span class="w-8 text-center text-sm">{{ $item['quantity'] }}</span>
                            <button wire:click="updateQuantity({{ $index }}, 'increase')"
                                class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>
                            <button wire:click="removeFromCart({{ $index }})"
                                class="p-1 rounded-full hover:bg-red-100 transition-colors">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach </div>
                @else
                <div
                    class="flex flex-col items-center justify-center h-full text-center py-8">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Keranjang masih kosong</p>
                </div>
                @endif </div>
                @if($appliedBundle)
                <div class="mx-4 mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ $appliedBundle->name }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">
                                @if($appliedBundle->isFreeItem())
                                    Gratis item berlaku!
                                @elseif($appliedBundle->isPercentage())
                                    Diskon {{ $appliedBundle->discount_value }}% berlaku
                                @else
                                    Diskon Rp {{ number_format($bundleDiscount, 0, ',', '.') }} berlaku
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                @if(count($cart) > 0)
                <div
                class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800 sticky bottom-0">
                <!-- Customer Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pelanggan
                        (Opsional)</label>
                @if($selectedCustomer)
                <div
                        class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div>
                            <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $selectedCustomer->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"> {{ $selectedCustomer->phone }} | Poin:
                                {{ $selectedCustomer->points }} </p>
                        </div>
                        <button wire:click="removeCustomer" class="text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @else
                <div class="relative">
                        <input type="text" wire:model.live="customerSearch" placeholder="Cari nama atau no. telepon..."
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        @if(strlen($customerSearch) >= 2 && $customers->count() > 0)
                <div
                            class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                            @foreach($customers as $customer)
                <button wire:click="selectCustomer({{ $customer->id }})"
                                class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $customer->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs block">{{ $customer->phone }} -
                                    Poin: {{ $customer->points }}</span>
                            </button>
                @endforeach </div>
                @endif
                    </div>
                @endif
                </div>
                <!-- Points Redemption -->
                @if($selectedCustomer && $selectedCustomer->points >= 10)
                <div
                    class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="usePoints" id="usePoints"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <label for="usePoints" class="text-sm font-medium text-gray-700 dark:text-gray-300">Gunakan
                                Poin</label>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Saldo: {{ $selectedCustomer->points
                            }}</span>
                    </div>
                @if($usePoints)
                <div class="mt-2">
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live="redeemPoints" min="0"
                                max="{{ $getMaxRedeemablePoints() }}"
                                class="flex-1 px-2 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                            <button wire:click="$set('redeemPoints', {{ $getMaxRedeemablePoints() }})"
                                class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 rounded">Max</button>
                        </div>
                @if($redeemPoints > 0)
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            Diskon: Rp {{ number_format($redeemPoints * 1000, 0, ',', '.') }} </p>
                @endif
                    </div>
                @endif
                </div>
                @endif
                <!-- Voucher Code -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kode Voucher
                        (Opsional)</label>
                @if($appliedVoucher)
                <div
                        class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div>
                            <p class="font-medium text-sm text-green-700 dark:text-green-300">{{ $appliedVoucher->name
                                }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">Kode: {{ $appliedVoucher->code }}</p>
                        </div>
                        <button wire:click="removeVoucher" class="text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @else
                <div class="flex gap-2">
                        <input type="text" wire:model="voucherCode" placeholder="Masukkan kode voucher..."
                            class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <button wire:click="applyVoucher"
                            class="px-3 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            Terapkan </button>
                    </div>
                @if($voucherError)
                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $voucherError }}
                    </p>
                @endif @endif
                </div>
                <!-- Metode Pembayaran -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metode
                        Pembayaran</label>
                    <select wire:model.live="paymentMethod"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS Statis</option>
                        @if($store?->payment_gateway_enabled)
                        <option value="digital">QRIS/Invoice Digital</option>
                        @endif
                    </select>
                </div>
                <!-- Ringkasan Pembayaran -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white">Rp {{
                            number_format($this->getSubtotalBeforeDiscount(), 0, ',', '.') }}</span>
                    </div>
                @if($this->getProductDiscountAmount() > 0)
                <div class="flex justify-between text-sm">
                        <span class="text-green-600 dark:text-green-400">Diskon Produk</span>
                        <span class="font-medium text-green-600 dark:text-green-400">- Rp {{
                            number_format($this->getProductDiscountAmount(), 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($this->getGlobalDiscountAmount() > 0)
                <div class="flex justify-between text-sm">
                        <span class="text-green-600 dark:text-green-400">Diskon Global</span>
                        <span class="font-medium text-green-600 dark:text-green-400">- Rp {{
                            number_format($this->getGlobalDiscountAmount(), 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($appliedVoucher)
                <div class="flex justify-between text-sm">
                        <span class="text-green-600 dark:text-green-400">Diskon Voucher ({{ $appliedVoucher->code
                            }})</span>
                        <span class="font-medium text-green-600 dark:text-green-400">- Rp {{
                            number_format($this->getVoucherDiscountAmount(), 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($usePoints && $redeemPoints > 0)
                <div class="flex justify-between text-sm">
                        <span class="text-yellow-600 dark:text-yellow-400">Diskon Poin (-{{ $redeemPoints }}
                            poin)</span>
                        <span class="font-medium text-yellow-600 dark:text-yellow-400">- Rp {{
                            number_format($redeemPoints * 1000, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($selectedCustomer && $pointsToEarn > 0)
                <div class="flex justify-between text-sm">
                        <span class="text-blue-600 dark:text-blue-400">Poin yang didapat</span>
                        <span class="font-medium text-blue-600 dark:text-blue-400">+{{ $pointsToEarn }} poin</span>
                    </div>
                @endif
                @if($paymentMethod === 'cash')
                <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uang
                            Dibayarkan</label>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="flex-shrink-0">
                                <span class="text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                            </div>
                            <div class="flex-1 relative">
                                <input type="number" wire:model.live="cashAmount"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="0" min="0" x-data x-on:focus="$el.value === '0' && ($el.value = '')"
                                    x-on:blur="!$el.value && ($el.value = '0')">
                            </div>
                        </div>
                    </div>
                    <!-- Kembalian -->
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-600 dark:text-gray-400">Kembalian</span>
                        <span
                            class="font-medium {{ $change < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($change, 0, ',', '.') }} </span>
                    </div>
                @else
                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                @if($paymentMethod === 'transfer') Silakan
                            lakukan pembayaran melalui transfer bank
                @else Silakan scan kode QRIS untuk melakukan
                            pembayaran
                @endif </p>
                    </div>
                @endif
                <div
                        class="flex justify-between text-base font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">Grand Total</span>
                        <span class="text-gray-900 dark:text-white">Rp {{ number_format($grandTotal, 0, ',', '.')
                            }}</span>
                    </div>
                </div>
                <!-- Tombol Aksi -->
                <div class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <button wire:click="openPaymentModal"
                            class="py-2 rounded-lg font-medium transition-colors text-sm bg-purple-600 text-white hover:bg-purple-700">
                            Multi Bayar </button>
                        <button wire:click="openSplitBillModal"
                            class="py-2 rounded-lg font-medium transition-colors text-sm bg-teal-600 text-white hover:bg-teal-700">
                            Split Bill </button>
                    </div>
                    <button wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout"
                        @class([ 'w-full py-2 rounded-lg font-medium transition-colors text-sm'
                        , 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600'=>
                        ($paymentMethod !== 'cash' || $this->canCheckout), 'bg-gray-300 dark:bg-gray-600 text-gray-500
                        dark:text-gray-400 cursor-not-allowed' => ($paymentMethod === 'cash' && !$this->canCheckout) ])
                        @disabled($paymentMethod === 'cash' && !$this->canCheckout)> <span wire:loading.remove
                            wire:target="checkout">Proses Pembayaran</span>
                        <span wire:loading wire:target="checkout">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    <!-- Variant Selection Modal -->
    @if($showVariantModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 shadow-xl max-h-[80vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pilih Varian</h3>
                <button wire:click="closeVariantModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedProductForVariant?->name }}</p>
            </div>
            <div class="overflow-y-auto flex-1 space-y-2">
                @forelse($availableVariants as $variant)
                <button wire:click="selectVariant({{ $variant->id }})"
                    class="w-full flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors {{ $variant->stock <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $variant->stock <= 0 ? 'disabled' : '' }}>
                    <div class="text-left">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $variant->variant_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $variant->sku }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($variant->selling_price, 0, ',', '.') }}
                        </p>
                        <p class="text-xs {{ $variant->stock <= $variant->low_stock_threshold ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                            Stok: {{ $variant->stock }}
                        </p>
                    </div>
                </button>
                @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada varian tersedia</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
    <!-- Suspended Transactions Modal -->
                @if($showSuspendedModal)
                <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4 shadow-xl max-h-[80vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transaksi Tertangguh</h3>
                <button wire:click="$set('showSuspendedModal', false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1">
                @if(count($suspendedTransactions) > 0)
                <div class="space-y-3">
                    @foreach($suspendedTransactions as $suspended)
                <div
                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{
                                $suspended->suspension_key }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                $suspended->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Rp {{
                                    number_format($suspended->total, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"> {{ count($suspended->cart_items) }}
                                    item
                @if($suspended->customer) - {{ $suspended->customer->name }}@endif </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="resumeTransaction('{{ $suspended->suspension_key }}')"
                                    class="px-3 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700">
                                    Lanjutkan </button>
                                <button wire:click="deleteSuspended({{ $suspended->id }})"
                                    class="px-3 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">
                                    Hapus </button>
                            </div>
                        </div>
                    </div>
                @endforeach </div>
                @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada transaksi tertangguh</p>
                </div>
                @endif </div>
        </div>
    </div>
                @endif
    <!-- Multi Payment Modal -->
                @if($showPaymentModal)
                <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multi Pembayaran</h3>
                <button wire:click="$set('showPaymentModal', false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white mb-2">
                    <span>Total Tagihan:</span>
                    <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
                <div
                    class="flex justify-between text-sm {{ $this->remainingPayment > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    <span>Sisa:</span>
                    <span>Rp {{ number_format($this->remainingPayment, 0, ',', '.') }}</span>
                </div>
            </div>
                @if(count($payments) > 0)
                <div class="mb-4 space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Pembayaran:</p>
                @foreach($payments as $index => $payment)
                <div
                    class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                @if($payment['payment_method']
                            === 'cash') Tunai
                @elseif($payment['payment_method'] === 'transfer') Transfer
                @else QRIS
                            @endif </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">Rp {{
                            number_format($payment['amount'], 0, ',', '.') }}</span>
                    </div>
                    <button wire:click="removePayment({{ $index }})" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endforeach
            </div>
                @endif
                @if($this->remainingPayment > 0)
                <div
                class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tambah Pembayaran:</p>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Metode</label>
                        <select wire:model.live="currentPaymentMethod"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Jumlah</label>
                        <input type="number" wire:model.live="currentPaymentAmount"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                    </div>
                </div>
                @if($currentPaymentMethod !== 'cash')
                <div class="mb-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Referensi</label>
                    <input type="text" wire:model="currentPaymentReference" placeholder="No. referensi..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                </div>
                @endif
                <button wire:click="addPayment"
                    class="w-full py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 text-sm">
                    + Tambah Pembayaran </button>
            </div>
                @endif
                <button wire:click="completeMultiPayment" @disabled($this->remainingPayment > 0) class="w-full
                py-2 rounded-lg font-medium text-sm {{ $this->remainingPayment > 0 ? 'bg-gray-300 dark:bg-gray-600
                text-gray-500 dark:text-gray-400 cursor-not-allowed' : 'bg-primary-600 text-white hover:bg-primary-700'
                }}"> {{ $this->remainingPayment > 0 ? 'Sisa: Rp '.number_format($this->remainingPayment, 0, ',', '.') :
                'Selesaikan Pembayaran' }} </button>
        </div>
    </div>
                @endif
    <!-- Split Bill Modal -->
                @if($showSplitBillModal)
                <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4 shadow-xl max-h-[80vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Split Bill</h3>
                <button wire:click="$set('showSplitBillModal', false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jumlah Split</label>
                <div class="flex items-center gap-2">
                    <button wire:click="$set('splitCount', {{ $splitCount - 1 }})"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-600 rounded">-</button>
                    <input type="number" wire:model.live="splitCount" min="2" max="10"
                        class="w-20 text-center rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                    <button wire:click="$set('splitCount', {{ $splitCount + 1 }})"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-600 rounded">+</button>
                </div>
            </div>
            <div class="mb-4">
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white">
                    <span>Total:</span>
                    <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="overflow-y-auto flex-1 space-y-2">
                @foreach($splits as $index => $split)
                <div
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 {{ $split['paid'] ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Split {{ $split['number']
                            }}</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">Rp {{
                            number_format($split['amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="splits.{{ $index }}.payment_method"
                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm"
                            @disabled($split['paid'])>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                @if(!$split['paid'])
                <button wire:click="processSplitPayment({{ $index }})"
                            class="px-3 py-1 bg-primary-600 text-white rounded text-sm hover:bg-primary-700"> Bayar
                        </button>
                @else
                <span class="px-3 py-1 bg-green-600 text-white rounded text-sm">
                            @if($split['payment_method'] === 'cash') Tunai
                @elseif($split['payment_method'] ===
                            'transfer') Transfer
                @else QRIS
                @endif </span>
                @endif
                    </div>
                </div>
                @endforeach </div>
            <button wire:click="completeSplitBill" @disabled(collect($splits)->contains('paid', false)) class="w-full
                py-2 rounded-lg font-medium text-sm mt-4 {{ collect($splits)->contains('paid', false) ? 'bg-gray-300
                dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' : 'bg-primary-600 text-white
                hover:bg-primary-700' }}"> {{ collect($splits)->contains('paid', false) ? 'Selesaikan semua split' :
                'Selesaikan Transaksi' }} </button>
        </div>
    </div>
                @endif {{-- Success Modal --}}
                @if($showSuccessModal)
                <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-sm mx-4 shadow-xl transform transition-all">
            <div class="text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Transaksi Berhasil!</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Transaksi telah berhasil disimpan.</p>
                <div class="space-y-3">
                    <button type="button" onclick="window.printReceipt('{{ $lastTransactionId }}')"
                        class="w-full flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg> Cetak Struk </button>
                    <button wire:click="$set('showSuccessModal', false)"
                        class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Tutup </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    <!-- JavaScript untuk Bluetooth Printer & Barcode -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.bluetoothPrinter) {
                window.bluetoothPrinter.autoConnect();
            }
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('transaction-completed', (data) => {
                if (window.bluetoothPrinter && window.bluetoothPrinter.connected) {
                    window.printReceipt(data.transactionId);
                }
            });

            // Auto-focus barcode input after transaction
            Livewire.on('focus-barcode', () => {
                setTimeout(() => {
                    const barcodeInput = document.querySelector('input[wire\\:model\\.live="barcodeInput"]');
                    if (barcodeInput) {
                        barcodeInput.focus();
                    }
                }, 100);
            });
        });

        // Keep barcode input focused
        document.addEventListener('keydown', function(e) {
            // Don't intercept if user is typing in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                return;
            }

            // Focus barcode input on any key press (if not already focused)
            const barcodeInput = document.querySelector('input[wire\\:model\\.live="barcodeInput"]');
            if (barcodeInput && document.activeElement !== barcodeInput) {
                // Only for alphanumeric keys and some special keys
                if (e.key.length === 1 || e.key === 'Enter') {
                    barcodeInput.focus();
                }
            }
        });
    </script>

    <!-- Payment Gateway Modal -->
    <div x-data="paymentModal()" x-show="isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto"
        x-on:show-payment-modal.window="openModal($event.detail)" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                @click="closeModal()"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <span x-text="paymentMethod === 'qris' ? 'Pembayaran QRIS' : 'Pembayaran Invoice'"></span>
                    </h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Amount -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Jumlah Pembayaran</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(amount)"></p>
                    </div>

                    <!-- QRIS Display -->
                    <div x-show="paymentMethod === 'qris'" class="flex flex-col items-center space-y-4">
                        <div x-show="qrImageUrl" class="bg-white p-4 rounded-lg border">
                            <img :src="qrImageUrl" alt="QRIS Code" class="w-64 h-64 mx-auto">
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Scan QRIS di atas menggunakan aplikasi pembayaran favorit Anda
                        </p>
                    </div>

                    <!-- Invoice Display -->
                    <div x-show="paymentMethod === 'invoice'" class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">Link Pembayaran:</p>
                            <a :href="paymentUrl" target="_blank" class="text-blue-600 dark:text-blue-400 underline break-all">
                                <span x-text="paymentUrl"></span>
                            </a>
                        </div>
                        <button @click="copyPaymentUrl()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Salin Link Pembayaran
                        </button>
                    </div>

                    <!-- Expiry Timer -->
                    <div x-show="expiresAt" class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 text-center">
                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Berlaku hingga: <span x-text="formatExpiry(expiresAt)"></span>
                        </p>
                    </div>

                    <!-- Payment Status -->
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Menunggu pembayaran...</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button @click="checkPaymentStatus()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Cek Status
                    </button>
                    <button @click="closeModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function paymentModal() {
            return {
                isOpen: false,
                transactionId: null,
                paymentMethod: 'qris',
                qrImageUrl: null,
                paymentUrl: null,
                amount: 0,
                reference: null,
                expiresAt: null,
                pollInterval: null,

                openModal(data) {
                    this.transactionId = data.transaction_id;
                    this.paymentMethod = data.payment_method || 'qris';
                    this.qrImageUrl = data.qr_image_url;
                    this.paymentUrl = data.payment_url;
                    this.amount = data.amount;
                    this.reference = data.reference;
                    this.expiresAt = data.expires_at;
                    this.isOpen = true;

                    // Start polling for payment status
                    this.startPolling();
                },

                closeModal() {
                    this.isOpen = false;
                    this.stopPolling();
                },

                startPolling() {
                    this.pollInterval = setInterval(() => {
                        this.checkPaymentStatus();
                    }, 5000); // Check every 5 seconds
                },

                stopPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                },

                async checkPaymentStatus() {
                    try {
                        const response = await fetch(`/payment/status/${this.transactionId}`);
                        const data = await response.json();

                        if (data.success && data.status === 'paid') {
                            this.stopPolling();
                            this.isOpen = false;
                            @this.call('onDigitalPaymentSuccess', this.transactionId);
                        }
                    } catch (error) {
                        console.error('Failed to check payment status:', error);
                    }
                },

                copyPaymentUrl() {
                    navigator.clipboard.writeText(this.paymentUrl);
                    alert('Link pembayaran berhasil disalin!');
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                    }).format(amount);
                },

                formatExpiry(timestamp) {
                    if (!timestamp) return '-';
                    const date = new Date(timestamp * 1000);
                    return date.toLocaleString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },
            };
        }
    </script>

    <!-- Payment Modal disabled - using direct API call instead -->
    {{-- <livewire:payment-modal /> --}}
</div>
