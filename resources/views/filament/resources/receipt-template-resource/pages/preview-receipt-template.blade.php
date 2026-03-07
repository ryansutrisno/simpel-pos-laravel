@php
    $templateService = new \App\Services\ReceiptTemplateService();
    $previewData = $templateService->getTemplatePreview($record);
    $template = $record;
    $data = $previewData['preview_data'];
    $config = $template->template_data ?? [];
@endphp

<x-filament-panels::page>
    <div class="fi-page-content mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold">Preview: {{ $record->name }}</h2>
                <p class="text-sm text-gray-500">{{ $record->description ?? 'Receipt Template Preview' }}</p>
            </div>
            <div class="flex gap-2">
                <x-filament::button tag="a" :href="App\Filament\Resources\ReceiptTemplateResource::getUrl('edit', ['record' => $record->getRouteKey()])">
                    Edit Template
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Receipt Preview -->
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">Receipt Preview</h3>
                
                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 font-mono text-sm" style="max-width: 280px; margin: 0 auto;">
                    {{-- Header --}}
                    @if(($config['header']['show_logo'] ?? true) && $template->store)
                        <div class="text-center font-bold">
                            @if($template->store && $template->store->logo_url)
                                <img src="{{ $template->store->logo_url }}" alt="Logo" class="h-12 mx-auto mb-2">
                            @endif
                        </div>
                    @endif

                    @if(($config['header']['show_store_name'] ?? true) && ($data['store']['name'] ?? 'Sample Store'))
                        <div class="text-center font-bold">{{ $data['store']['name'] }}</div>
                    @endif

                    @if(($config['header']['show_store_address'] ?? true) && ($data['store']['address'] ?? '123 Sample Street'))
                        <div class="text-center text-xs">{{ $data['store']['address'] }}</div>
                    @endif

                    @if(($config['header']['show_store_phone'] ?? true) && ($data['store']['phone'] ?? '081234567890'))
                        <div class="text-center text-xs">{{ $data['store']['phone'] }}</div>
                    @endif

                    @if(($config['header']['show_tagline'] ?? true) && ($data['store']['tagline'] ?? 'Your trusted partner'))
                        <div class="text-center text-xs italic">{{ $data['store']['tagline'] }}</div>
                    @endif

                    {{-- Custom Header Message --}}
                    @if(($config['header']['custom_header_message'] ?? null))
                        <div class="text-center text-xs mt-1">{{ $config['header']['custom_header_message'] }}</div>
                    @endif

                    {{-- Separator --}}
                    <div class="my-2 {{ $config['styling']['separator_style'] ?? 'dashes' === 'dashes' ? 'border-b border-dotted border-gray-400' : 'border-b border-gray-400' }}">
                        {{ str_repeat($config['styling']['separator_style'] ?? 'dashes' === 'dashes' ? '-' : '.', 30) }}
                    </div>

                    {{-- Body --}}
                    @if($config['body']['show_transaction_id'] ?? true)
                        <div>No: {{ $data['transaction']['id'] }}</div>
                    @endif

                    @if($config['body']['show_date'] ?? true)
                        <div>Tgl: {{ $data['transaction']['date'] }}</div>
                    @endif

                    @if($config['body']['show_cashier_name'] ?? true)
                        <div>Kasir: {{ $data['transaction']['cashier'] }}</div>
                    @endif

                    {{-- Separator --}}
                    <div class="my-1 border-b border-dotted border-gray-400">{{ str_repeat('-', 30) }}</div>

                    {{-- Items Header --}}
                    @if($config['body']['show_items_header'] ?? true)
                        <div class="font-bold">
                            <span>Item</span>
                            <span class="float-right">Harga</span>
                        </div>
                    @endif

                    {{-- Items --}}
                    @foreach($data['items'] as $item)
                        @if(($config['body']['item_format'] ?? 'name_price_quantity') === 'name_price_quantity')
                            <div>{{ $item['quantity'] }}x {{ $item['name'] }}</div>
                            <div class="pl-4">
                                {{ number_format($item['price'], 0, ',', '.') }} x {{ $item['quantity'] }}
                                <span class="float-right">{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        @elseif($config['body']['item_format'] === 'name_only')
                            <div>{{ $item['name'] }}</div>
                        @else
                            <div>{{ $item['name'] }}</div>
                            <span class="float-right">{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                        @endif
                    @endforeach

                    {{-- Separator --}}
                    <div class="my-1 border-b border-dotted border-gray-400">{{ str_repeat('-', 30) }}</div>

                    {{-- Subtotal --}}
                    @if($config['body']['show_subtotal'] ?? true)
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>{{ number_format(collect($data['items'])->sum('subtotal'), 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- Footer --}}
                    @if($config['footer']['show_payment_method'] ?? true)
                        <div class="flex justify-between">
                            <span>Pembayaran</span>
                            <span class="uppercase">{{ $data['payment']['method'] }}</span>
                        </div>
                    @endif

                    @if($config['footer']['show_cash_received'] ?? true)
                        <div class="flex justify-between">
                            <span>Tunai</span>
                            <span>{{ number_format($data['payment']['cash_received'], 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if($config['footer']['show_change'] ?? true)
                        <div class="flex justify-between font-bold">
                            <span>Kembali</span>
                            <span>{{ number_format($data['payment']['change_amount'], 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="my-2 border-b border-dotted border-gray-400">{{ str_repeat('-', 30) }}</div>
                    <div class="flex justify-between font-bold text-lg">
                        <span>TOTAL</span>
                        <span>{{ number_format($data['payment']['total'], 0, ',', '.') }}</span>
                    </div>

                    {{-- Custom Footer Message --}}
                    @if(($config['footer']['custom_footer_message'] ?? null))
                        <div class="text-center mt-2">{{ $config['footer']['custom_footer_message'] }}</div>
                    @endif

                    {{-- Barcode / QR --}}
                    @if($config['footer']['show_barcode'] ?? true)
                        <div class="text-center mt-2">
                            <div class="border border-gray-800 p-1 inline-block">
                                {{-- Simple barcode representation --}}
                                <div class="text-[8px] font-mono">|||||| ||| |||| ||| |||</div>
                            </div>
                        </div>
                    @endif

                    @if($config['footer']['show_qr_code'] ?? false)
                        <div class="text-center mt-2">
                            <div class="border border-gray-800 p-1 inline-block">
                                <div class="w-16 h-16 bg-gray-800"></div>
                            </div>
                        </div>
                    @endif

                    <div class="text-center mt-2 text-xs">Terima Kasih</div>
                </div>
            </div>

            <!-- Template Configuration Summary -->
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">Template Configuration</h3>
                
                <div class="space-y-4">
                    <!-- Header Settings -->
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Header</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['header']['show_logo'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Show Logo</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['header']['show_store_name'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Store Name</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['header']['show_store_address'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Address</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['header']['show_store_phone'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Phone</span>
                            </div>
                        </div>
                    </div>

                    <!-- Body Settings -->
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Body</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['body']['show_transaction_id'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Transaction ID</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['body']['show_date'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Date & Time</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['body']['show_cashier_name'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Cashier Name</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['body']['show_subtotal'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Subtotal</span>
                            </div>
                        </div>
                        <div class="mt-2 text-sm">
                            <span class="text-gray-500">Item Format: </span>
                            <span class="font-medium">{{ $config['body']['item_format'] ?? 'name_price_quantity' }}</span>
                        </div>
                    </div>

                    <!-- Footer Settings -->
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Footer</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['footer']['show_payment_method'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Payment Method</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['footer']['show_cash_received'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Cash Received</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['footer']['show_change'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Change</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['footer']['show_barcode'] ?? true) ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>Barcode</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full {{ ($config['footer']['show_qr_code'] ?? false) ? 'bg-green-500' : 'bg-gray-300' }}</ sekarang tidak mau tahu yang penting fix dulu, Mari saya test route dan bersihkan konteks. 300' }}"></span>
                                <span>QR Code</span>
                            </div>
                        </div>
                    </div>

                    <!-- Styling Settings -->
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Styling</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-500">Font Size: </span>
                                <span class="font-medium">{{ $config['styling']['font_size'] ?? 'normal' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Alignment: </span>
                                <span class="font-medium">{{ $config['styling']['text_alignment'] ?? 'left' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Bold Headers: </span>
                                <span class="font-medium">{{ ($config['styling']['bold_headers'] ?? true) ? 'Yes' : 'No' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Separator: </span>
                                <span class="font-medium">{{ $config['styling']['separator_style'] ?? 'dashes' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
