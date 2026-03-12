<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialRecord;
use App\Models\Product;
use App\Models\SplitBill;
use App\Models\SuspendedTransaction;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Services\BundleService;
use App\Services\DiscountService;
use App\Services\PointService;
use App\Services\ReceiptTemplateService;
use App\Services\ReorderPointService;
use App\Services\ShiftService;
use App\Services\TaxService;
use App\Services\VariantService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Pos extends Component
{
    use WithPagination;

    public $cart = [];

    public $paymentMethod = 'cash';

    public $cashReceived = 0;

    public $searchQuery = '';

    public $selectedCategoryId = null;

    public $categories;

    public $cashAmount = 0;

    public $lastTransactionId = null;

    public $showSuccessModal = false;

    public $store;

    public $availableTemplates = [];

    public $selectedTemplateId = null;

    public $selectedCustomerId = null;

    public $customerSearch = '';

    public $redeemPoints = 0;

    public $usePoints = false;

    public $voucherCode = '';

    public $appliedVoucher = null;

    public $voucherError = null;

    public $showSuspendedModal = false;

    public $suspendedTransactions = [];

    public $payments = [];

    public $showPaymentModal = false;

    public $currentPaymentMethod = 'cash';

    public $currentPaymentAmount = 0;

    public $currentPaymentReference = '';

    public $showSplitBillModal = false;

    public $splitCount = 2;

    public $splits = [];

    public $barcodeInput = '';

    public $showVariantModal = false;

    public $selectedProductForVariant = null;

    public $availableVariants = [];

    public $appliedBundle = null;

    public $bundleDiscount = 0;

    public $taxEnabled = false;

    protected PointService $pointService;

    protected DiscountService $discountService;

    protected TaxService $taxService;

    protected $listeners = ['barcode-scanned' => 'processBarcode', 'paymentSuccess' => 'onDigitalPaymentSuccess'];

    protected BundleService $bundleService;

    protected VariantService $variantService;

    public function boot(PointService $pointService, DiscountService $discountService, TaxService $taxService, BundleService $bundleService, VariantService $variantService): void
    {
        $this->pointService = $pointService;
        $this->discountService = $discountService;
        $this->taxService = $taxService;
        $this->bundleService = $bundleService;
        $this->variantService = $variantService;
    }

    public function mount()
    {
        $this->categories = Category::all();
        $this->store = \App\Models\Store::first();

        $templateService = new ReceiptTemplateService;
        $this->availableTemplates = $templateService->getAvailableTemplates($this->store);

        $activeTemplate = $templateService->getActiveTemplate($this->store);
        $this->selectedTemplateId = $activeTemplate?->id;

        $this->taxEnabled = $this->store?->isTaxEnabled() ?? false;

        // Check for active shift
        if (! ShiftService::hasActiveShift(Auth::id())) {
            Notification::make()
                ->title('Tidak ada shift aktif')
                ->body('Anda harus membuka shift terlebih dahulu sebelum melakukan transaksi.')
                ->warning()
                ->persistent()
                ->send();
        }

        // Check for maintenance mode
        $appSettings = AppSettings::getInstance();
        if ($appSettings->maintenance_mode) {
            Notification::make()
                ->title('Mode Maintenance Aktif')
                ->body('Aplikasi sedang dalam mode maintenance. Beberapa fitur mungkin tidak tersedia.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function updatedPaymentMethod($value)
    {
        if ($value !== 'cash') {
            $this->cashAmount = 0;
        }
    }

    public function updatedSelectedCustomerId($value)
    {
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function updatedUsePoints($value)
    {
        if ($value && $this->selectedCustomer) {
            $this->redeemPoints = $this->getMaxRedeemablePoints();
        } else {
            $this->redeemPoints = 0;
        }
    }

    public function updatedBarcodeInput($value)
    {
        if (strlen($value) >= 8) {
            $this->processBarcode($value);
        }
    }

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::with('membershipTier')->find($this->selectedCustomerId) : null;
    }

    public function getCustomerPointsProperty(): int
    {
        return $this->selectedCustomer?->points ?? 0;
    }

    public function getPaymentGatewayConfigProperty()
    {
        return \App\Models\PaymentGatewayConfig::where('store_id', $this->store?->id ?? 1)
            ->where('is_active', true)
            ->first();
    }

    public function getMaxRedeemablePoints(): int
    {
        if (! $this->selectedCustomer) {
            return 0;
        }

        $subtotal = $this->getSubtotal();

        return $this->pointService->getMaxRedeemablePoints($this->selectedCustomer->points, $subtotal);
    }

    public function getSubtotal(): float
    {
        return collect($this->cart)->sum(fn ($item) => ($item['final_price'] ?? $item['selling_price']) * $item['quantity']);
    }

    public function getSubtotalBeforeDiscount(): float
    {
        return collect($this->cart)->sum(fn ($item) => $item['selling_price'] * $item['quantity']);
    }

    public function getProductDiscountAmount(): float
    {
        return collect($this->cart)->sum(fn ($item) => ($item['discount_amount'] ?? 0) * $item['quantity']);
    }

    public function applyVoucher()
    {
        $this->voucherError = null;

        if (empty($this->voucherCode)) {
            return;
        }

        $subtotal = $this->getSubtotal();
        $voucher = $this->discountService->validateVoucher($this->voucherCode, $subtotal);

        if (! $voucher) {
            $this->voucherError = 'Kode voucher tidak valid atau tidak memenuhi syarat';
            $this->appliedVoucher = null;

            return;
        }

        $this->appliedVoucher = $voucher;
        $this->voucherCode = strtoupper($this->voucherCode);
    }

    public function removeVoucher()
    {
        $this->voucherCode = '';
        $this->appliedVoucher = null;
        $this->voucherError = null;
    }

    public function getVoucherDiscountAmount(): float
    {
        if (! $this->appliedVoucher) {
            return 0;
        }

        return $this->discountService->calculateTransactionDiscount(
            $this->getSubtotal(),
            $this->appliedVoucher->code
        )['voucher_discount_amount'];
    }

    public function getGlobalDiscountAmount(): float
    {
        $subtotal = $this->getSubtotal();

        return $this->discountService->calculateTransactionDiscount($subtotal)['global_discount_amount'];
    }

    public function getTotalDiscountAmount(): float
    {
        return $this->getProductDiscountAmount() + $this->getGlobalDiscountAmount() + $this->getVoucherDiscountAmount();
    }

    public function getDiscountFromPoints(): float
    {
        if (! $this->usePoints || $this->redeemPoints <= 0) {
            return 0;
        }

        return $this->pointService->calculateRedeemValue($this->redeemPoints);
    }

    public function getGrandTotalProperty()
    {
        $subtotal = $this->getSubtotal();
        $globalDiscount = $this->getGlobalDiscountAmount();
        $voucherDiscount = $this->getVoucherDiscountAmount();
        $pointsDiscount = $this->getDiscountFromPoints();
        $bundleDiscount = $this->bundleDiscount;

        return max(0, $subtotal - $globalDiscount - $voucherDiscount - $pointsDiscount - $bundleDiscount);
    }

    public function getChangeProperty()
    {
        if ($this->paymentMethod !== 'cash') {
            return 0;
        }

        return max(0, (float) $this->cashAmount - $this->grandTotal);
    }

    public function getTaxAmountProperty(): float
    {
        if (! $this->taxEnabled) {
            return 0;
        }

        return $this->taxService->calculateTax($this->grandTotal, $this->store?->getTaxRate() ?? 10.00);
    }

    public function getGrandTotalWithTaxProperty(): float
    {
        return $this->grandTotal + $this->taxAmount;
    }

    public function getTaxRateProperty(): float
    {
        return $this->store?->getTaxRate() ?? 10.00;
    }

    public function getTaxNameProperty(): string
    {
        return $this->store?->getTaxName() ?? 'PPN';
    }

    public function getChangeWithTaxProperty()
    {
        if ($this->paymentMethod !== 'cash') {
            return 0;
        }

        return max(0, (float) $this->cashAmount - $this->grandTotalWithTax);
    }

    public function getCanCheckoutProperty()
    {
        if (empty($this->cart)) {
            return false;
        }

        if ($this->paymentMethod === 'cash') {
            return (float) $this->cashAmount >= $this->grandTotalWithTax;
        }

        return true;
    }

    public function getPointsToEarnProperty(): int
    {
        if (! $this->selectedCustomer) {
            return 0;
        }

        return $this->pointService->calculateEarnedPoints($this->grandTotal, $this->selectedCustomer?->membershipTier);
    }

    public function updateQuantity($index, $action)
    {
        if ($action === 'increase') {
            $this->cart[$index]['quantity']++;
        } elseif ($action === 'decrease') {
            if ($this->cart[$index]['quantity'] > 1) {
                $this->cart[$index]['quantity']--;
            }
        }

        if ($this->usePoints) {
            $this->redeemPoints = min($this->redeemPoints, $this->getMaxRedeemablePoints());
        }
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);

        if ($this->usePoints) {
            $this->redeemPoints = min($this->redeemPoints, $this->getMaxRedeemablePoints());
        }
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('barcode', 'like', '%'.$this->searchQuery.'%')
                        ->orWhereHas('category', function ($q) {
                            $q->where('name', 'like', '%'.$this->searchQuery.'%');
                        });
                });
            })
            ->when($this->selectedCategoryId, function ($query) {
                $query->where('category_id', $this->selectedCategoryId);
            })
            ->where('is_active', true)
            ->paginate(12);

        $customers = collect();
        if (strlen($this->customerSearch) >= 2) {
            $customers = Customer::where('is_active', true)
                ->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->customerSearch.'%')
                        ->orWhere('phone', 'like', '%'.$this->customerSearch.'%');
                })
                ->limit(10)
                ->get();
        }

        return view('livewire.pos', [
            'products' => $products,
            'categories' => $this->categories,
            'change' => $this->change,
            'store' => $this->store,
            'availableTemplates' => $this->availableTemplates,
            'customers' => $customers,
            'selectedCustomer' => $this->selectedCustomer,
            'grandTotal' => $this->grandTotal,
            'pointsToEarn' => $this->pointsToEarn,
        ]);
    }

    public function filterProductsByCategory($categoryId = null)
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addToCart($productId, $variantId = null)
    {
        $product = Product::find($productId);

        // Check if product has variants
        if ($variantId === null && $this->variantService->hasVariants($productId)) {
            $this->showVariantSelection($productId);

            return;
        }

        // Get variant or product data
        if ($variantId) {
            $variant = $this->variantService->getVariantBySku($variantId);
            if (! $variant) {
                $variant = \App\Models\ProductVariant::find($variantId);
            }

            if (! $variant || $variant->stock <= 0) {
                Notification::make()
                    ->title('Stok varian habis')
                    ->body('Varian produk ini sudah habis')
                    ->danger()
                    ->send();

                return;
            }

            $name = $product->name.' - '.$variant->variant_name;
            $purchasePrice = $variant->purchase_price;
            $sellingPrice = $variant->selling_price;
            $stock = $variant->stock;
        } else {
            $name = $product->name;
            $purchasePrice = $product->purchase_price;
            $sellingPrice = $product->selling_price;
            $stock = $product->stock;
        }

        $discountInfo = $this->discountService->calculateProductDiscount($product);
        $finalPrice = $variantId ? $sellingPrice : $discountInfo['final_price'];
        $originalPrice = $variantId ? $sellingPrice : $discountInfo['original_price'];
        $discountAmount = $variantId ? 0 : $discountInfo['discount_amount'];

        $this->cart[] = [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'name' => $name,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'discount_id' => $variantId ? null : ($discountInfo['discount']?->id),
            'quantity' => 1,
            'profit' => $finalPrice - $purchasePrice,
        ];

        // Apply bundle discounts after adding to cart
        $this->applyBundleDiscounts();
    }

    public function showVariantSelection($productId)
    {
        $this->selectedProductForVariant = Product::find($productId);
        $this->availableVariants = $this->variantService->getAvailableVariants($productId);
        $this->showVariantModal = true;
    }

    public function selectVariant($variantId)
    {
        $this->addToCart($this->selectedProductForVariant->id, $variantId);
        $this->showVariantModal = false;
        $this->selectedProductForVariant = null;
        $this->availableVariants = [];
    }

    public function closeVariantModal()
    {
        $this->showVariantModal = false;
        $this->selectedProductForVariant = null;
        $this->availableVariants = [];
    }

    public function applyBundleDiscounts()
    {
        $cartItems = collect($this->cart)->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'selling_price' => $item['selling_price'],
            ];
        })->toArray();

        $bundleResult = $this->bundleService->applyBestBundle($cartItems);

        if ($bundleResult['applied']) {
            $this->appliedBundle = $bundleResult['bundle'];
            $this->bundleDiscount = $bundleResult['discount'];
            $this->cart = $bundleResult['cart_items'];
        } else {
            $this->appliedBundle = null;
            $this->bundleDiscount = 0;
        }
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->customerSearch = '';
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function removeCustomer()
    {
        $this->selectedCustomerId = null;
        $this->redeemPoints = 0;
        $this->usePoints = false;
    }

    public function processBarcode($barcode)
    {
        // First check if barcode matches a product variant SKU
        $variant = $this->variantService->getVariantBySku($barcode);

        if ($variant) {
            $product = $variant->product;
            $this->addToCart($product->id, $variant->id);
            $this->barcodeInput = '';

            Notification::make()
                ->title('Produk ditambahkan')
                ->body($variant->variant_name.' telah ditambahkan ke keranjang')
                ->success()
                ->send();

            return;
        }

        // Then check regular product barcode
        $product = Product::where('barcode', $barcode)->first();

        if ($product) {
            $this->addToCart($product->id);
            $this->barcodeInput = '';

            Notification::make()
                ->title('Produk ditambahkan')
                ->body($product->name.' telah ditambahkan ke keranjang')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Produk tidak ditemukan')
                ->body('Barcode: '.$barcode.' tidak ditemukan')
                ->danger()
                ->send();
        }
    }

    public function holdTransaction()
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->body('Tidak ada transaksi untuk ditangguhkan')
                ->warning()
                ->send();

            return;
        }

        $suspendedCount = SuspendedTransaction::where('user_id', Auth::id())->count();
        if ($suspendedCount >= 5) {
            Notification::make()
                ->title('Batas maksimal tercapai')
                ->body('Anda sudah memiliki 5 transaksi tertangguh. Selesaikan atau hapus salah satu.')
                ->danger()
                ->send();

            return;
        }

        $suspensionKey = SuspendedTransaction::generateSuspensionKey();

        SuspendedTransaction::create([
            'user_id' => Auth::id(),
            'suspension_key' => $suspensionKey,
            'customer_id' => $this->selectedCustomerId,
            'cart_items' => $this->cart,
            'subtotal' => $this->getSubtotal(),
            'discount_amount' => $this->getTotalDiscountAmount(),
            'total' => $this->grandTotal,
            'voucher_code' => $this->appliedVoucher?->code,
            'notes' => null,
        ]);

        $this->reset(['cart', 'selectedCustomerId', 'redeemPoints', 'usePoints', 'voucherCode', 'appliedVoucher', 'voucherError', 'cashAmount']);

        Notification::make()
            ->title('Transaksi ditangguhkan')
            ->body('Kode tangguhan: '.$suspensionKey)
            ->success()
            ->send();
    }

    public function loadSuspendedTransactions()
    {
        $this->suspendedTransactions = SuspendedTransaction::where('user_id', Auth::id())
            ->with('customer')
            ->latest()
            ->get();
        $this->showSuspendedModal = true;
    }

    public function resumeTransaction($suspensionKey)
    {
        $suspended = SuspendedTransaction::where('suspension_key', $suspensionKey)
            ->where('user_id', Auth::id())
            ->first();

        if (! $suspended) {
            Notification::make()
                ->title('Transaksi tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $this->cart = $suspended->cart_items;
        $this->selectedCustomerId = $suspended->customer_id;

        if ($suspended->voucher_code) {
            $this->voucherCode = $suspended->voucher_code;
            $this->applyVoucher();
        }

        $suspended->delete();
        $this->showSuspendedModal = false;

        Notification::make()
            ->title('Transaksi dipulihkan')
            ->success()
            ->send();
    }

    public function deleteSuspended($id)
    {
        $suspended = SuspendedTransaction::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($suspended) {
            $suspended->delete();
            $this->loadSuspendedTransactions();

            Notification::make()
                ->title('Transaksi tertangguh dihapus')
                ->success()
                ->send();
        }
    }

    public function openPaymentModal()
    {
        $this->showPaymentModal = true;
        $this->payments = [];
        $this->currentPaymentMethod = 'cash';
        $this->currentPaymentAmount = $this->grandTotal;
        $this->currentPaymentReference = '';
    }

    public function addPayment()
    {
        $remaining = $this->getRemainingPaymentProperty();

        if ($this->currentPaymentAmount <= 0) {
            Notification::make()
                ->title('Jumlah tidak valid')
                ->body('Masukkan jumlah pembayaran yang valid')
                ->warning()
                ->send();

            return;
        }

        if ($this->currentPaymentAmount > $remaining) {
            Notification::make()
                ->title('Jumlah melebihi sisa')
                ->body('Jumlah pembayaran melebihi sisa tagihan')
                ->warning()
                ->send();

            return;
        }

        $this->payments[] = [
            'payment_method' => $this->currentPaymentMethod,
            'amount' => $this->currentPaymentAmount,
            'reference' => $this->currentPaymentReference,
        ];

        $remaining = $this->getRemainingPaymentProperty();

        if ($remaining > 0) {
            $this->currentPaymentAmount = $remaining;
            $this->currentPaymentReference = '';
        } else {
            $this->currentPaymentAmount = 0;
        }

        Notification::make()
            ->title('Pembayaran ditambahkan')
            ->success()
            ->send();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->currentPaymentAmount = $this->getRemainingPaymentProperty();
    }

    public function getRemainingPaymentProperty(): float
    {
        $totalPaid = collect($this->payments)->sum('amount');

        return max(0, $this->grandTotal - $totalPaid);
    }

    public function completeMultiPayment()
    {
        $remaining = $this->getRemainingPaymentProperty();

        if ($remaining > 0) {
            Notification::make()
                ->title('Pembayaran belum lunas')
                ->body('Sisa tagihan: Rp '.number_format($remaining, 0, ',', '.'))
                ->warning()
                ->send();

            return;
        }

        $transaction = $this->createTransactionWithPayments($this->payments);

        $this->finalizeTransaction($transaction);

        $this->showPaymentModal = false;
        $this->payments = [];
    }

    public function openSplitBillModal()
    {
        $this->showSplitBillModal = true;
        $this->splitCount = 2;
        $this->initSplits();
    }

    public function updatedSplitCount()
    {
        $this->initSplits();
    }

    public function initSplits()
    {
        if ($this->splitCount < 2) {
            $this->splitCount = 2;
        }

        if ($this->splitCount > 10) {
            $this->splitCount = 10;
        }

        $amountPerSplit = round($this->grandTotal / $this->splitCount, 2);
        $this->splits = [];

        for ($i = 0; $i < $this->splitCount; $i++) {
            $this->splits[] = [
                'number' => $i + 1,
                'amount' => $amountPerSplit,
                'payment_method' => 'cash',
                'reference' => '',
                'paid' => false,
            ];
        }

        $totalSplit = $amountPerSplit * $this->splitCount;
        if ($totalSplit != $this->grandTotal) {
            $this->splits[$this->splitCount - 1]['amount'] += ($this->grandTotal - $totalSplit);
        }
    }

    public function processSplitPayment($index)
    {
        $this->splits[$index]['paid'] = true;

        Notification::make()
            ->title('Split '.$this->splits[$index]['number'].' dibayar')
            ->body('Rp '.number_format($this->splits[$index]['amount'], 0, ',', '.').' - '.$this->getPaymentMethodLabel($this->splits[$index]['payment_method']))
            ->success()
            ->send();
    }

    public function completeSplitBill()
    {
        $allPaid = collect($this->splits)->every(fn ($split) => $split['paid']);

        if (! $allPaid) {
            Notification::make()
                ->title('Belum semua split dibayar')
                ->warning()
                ->send();

            return;
        }

        $transaction = $this->createTransactionWithSplitBill($this->splits);

        $this->finalizeTransaction($transaction);

        $this->showSplitBillModal = false;
        $this->splits = [];
    }

    public function checkout()
    {
        if (! $this->canCheckout) {
            Notification::make()
                ->title('Pembayaran tidak valid')
                ->danger()
                ->send();

            return;
        }

        // Handle digital payment (QRIS/Invoice) - create pending transaction
        if ($this->paymentMethod === 'digital') {
            $this->handleDigitalPayment();

            return;
        }

        $subtotalBeforeDiscount = $this->getSubtotalBeforeDiscount();
        $subtotalAfterProductDiscount = $this->getSubtotal();
        $globalDiscountAmount = $this->getGlobalDiscountAmount();
        $voucherDiscountAmount = $this->getVoucherDiscountAmount();
        $discountFromPoints = $this->getDiscountFromPoints();
        $bundleDiscount = $this->bundleDiscount;
        $totalDiscountAmount = $globalDiscountAmount + $voucherDiscountAmount + $discountFromPoints + $bundleDiscount;
        $grandTotal = max(0, $subtotalAfterProductDiscount - $totalDiscountAmount);
        $totalProfit = collect($this->cart)->sum(fn ($item) => $item['profit'] * $item['quantity']);

        $discountId = null;
        if ($this->appliedVoucher) {
            $discountId = $this->appliedVoucher->id;
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'shift_id' => ShiftService::getActiveShift(Auth::id())?->id,
            'customer_id' => $this->selectedCustomerId,
            'discount_id' => $discountId,
            'total' => $grandTotal,
            'subtotal_before_discount' => $subtotalBeforeDiscount,
            'discount_amount' => $this->getProductDiscountAmount() + $globalDiscountAmount + $voucherDiscountAmount + $bundleDiscount,
            'voucher_code' => $this->appliedVoucher?->code,
            'payment_method' => $this->paymentMethod,
            'cash_amount' => $this->paymentMethod === 'cash' ? $this->cashAmount : null,
            'change_amount' => $this->paymentMethod === 'cash' ? $this->change : null,
            'points_earned' => 0,
            'points_redeemed' => $this->redeemPoints,
            'discount_from_points' => $discountFromPoints,
            'is_split' => false,
            'total_splits' => 1,
            'subtotal_before_tax' => $grandTotal,
            'tax_amount' => $this->taxAmount,
            'tax_rate' => $this->taxEnabled ? $this->store->getTaxRate() : 0,
            'tax_enabled' => $this->taxEnabled,
        ]);

        foreach ($this->cart as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => ($item['discount_amount'] ?? 0) + ($item['bundle_discount'] ?? 0),
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            // Decrement variant stock if variant, otherwise product stock
            if (! empty($item['variant_id'])) {
                $this->variantService->decrementStock($item['variant_id'], $item['quantity']);
            } else {
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }
        }

        // Check for reorder alerts after stock decrement
        ReorderPointService::checkStockLevels();

        if ($this->appliedVoucher) {
            $this->discountService->incrementUsage($this->appliedVoucher);
        }

        if ($this->selectedCustomer) {
            if ($this->redeemPoints > 0) {
                $this->pointService->redeemPoints($this->selectedCustomer, $this->redeemPoints, $transaction->id);
            }

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal, $this->selectedCustomer?->membershipTier);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
            $this->selectedCustomer->recalculateTier();
            $transaction->update(['points_earned' => $pointsEarned]);
        }

        FinancialRecord::create([
            'type' => 'sales',
            'amount' => $grandTotal,
            'profit' => $totalProfit,
            'transaction_id' => $transaction->id,
            'description' => 'Penjualan produk'.($this->selectedCustomer ? ' - '.$this->selectedCustomer->name : ''),
            'record_date' => now()->toDateString(),
        ]);

        $this->lastTransactionId = $transaction->id;

        Notification::make()
            ->title('Transaksi berhasil')
            ->success()
            ->send();

        $this->dispatch('transaction-completed', [
            'transactionId' => $transaction->id,
            'templateId' => $this->selectedTemplateId,
            'transactionData' => $this->getTransactionData($transaction->id),
        ]);

        $this->reset(['cart', 'cashAmount', 'paymentMethod', 'selectedCategoryId', 'searchQuery', 'selectedCustomerId', 'redeemPoints', 'usePoints', 'voucherCode', 'appliedVoucher', 'voucherError']);
        $this->showSuccessModal = true;
    }

    protected function createTransactionWithPayments(array $payments): Transaction
    {
        $subtotalBeforeDiscount = $this->getSubtotalBeforeDiscount();
        $subtotalAfterProductDiscount = $this->getSubtotal();
        $globalDiscountAmount = $this->getGlobalDiscountAmount();
        $voucherDiscountAmount = $this->getVoucherDiscountAmount();
        $discountFromPoints = $this->getDiscountFromPoints();
        $bundleDiscount = $this->bundleDiscount;
        $grandTotal = max(0, $subtotalAfterProductDiscount - $globalDiscountAmount - $voucherDiscountAmount - $discountFromPoints - $bundleDiscount);
        $totalProfit = collect($this->cart)->sum(fn ($item) => $item['profit'] * $item['quantity']);

        $discountId = null;
        if ($this->appliedVoucher) {
            $discountId = $this->appliedVoucher->id;
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'shift_id' => ShiftService::getActiveShift(Auth::id())?->id,
            'customer_id' => $this->selectedCustomerId,
            'discount_id' => $discountId,
            'total' => $grandTotal,
            'subtotal_before_discount' => $subtotalBeforeDiscount,
            'discount_amount' => $this->getProductDiscountAmount() + $globalDiscountAmount + $voucherDiscountAmount,
            'voucher_code' => $this->appliedVoucher?->code,
            'payment_method' => 'multi',
            'cash_amount' => null,
            'change_amount' => null,
            'points_earned' => 0,
            'points_redeemed' => $this->redeemPoints,
            'discount_from_points' => $discountFromPoints,
            'is_split' => false,
            'total_splits' => 1,
            'subtotal_before_tax' => $grandTotal,
            'tax_amount' => $this->taxAmount,
            'tax_rate' => $this->taxEnabled ? $this->store->getTaxRate() : 0,
            'tax_enabled' => $this->taxEnabled,
        ]);

        foreach ($this->cart as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => ($item['discount_amount'] ?? 0) + ($item['bundle_discount'] ?? 0),
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            // Decrement variant stock if variant, otherwise product stock
            if (! empty($item['variant_id'])) {
                $this->variantService->decrementStock($item['variant_id'], $item['quantity']);
            } else {
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }
        }

        // Check for reorder alerts after stock decrement
        ReorderPointService::checkStockLevels();

        foreach ($payments as $payment) {
            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'payment_method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'reference' => $payment['reference'] ?? null,
            ]);
        }

        if ($this->appliedVoucher) {
            $this->discountService->incrementUsage($this->appliedVoucher);
        }

        if ($this->selectedCustomer) {
            if ($this->redeemPoints > 0) {
                $this->pointService->redeemPoints($this->selectedCustomer, $this->redeemPoints, $transaction->id);
            }

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal, $this->selectedCustomer?->membershipTier);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
            $this->selectedCustomer->recalculateTier();
            $transaction->update(['points_earned' => $pointsEarned]);
        }

        FinancialRecord::create([
            'type' => 'sales',
            'amount' => $grandTotal,
            'profit' => $totalProfit,
            'transaction_id' => $transaction->id,
            'description' => 'Penjualan produk (Multi Payment)'.($this->selectedCustomer ? ' - '.$this->selectedCustomer->name : ''),
            'record_date' => now()->toDateString(),
        ]);

        return $transaction;
    }

    protected function createTransactionWithSplitBill(array $splits): Transaction
    {
        $subtotalBeforeDiscount = $this->getSubtotalBeforeDiscount();
        $subtotalAfterProductDiscount = $this->getSubtotal();
        $globalDiscountAmount = $this->getGlobalDiscountAmount();
        $voucherDiscountAmount = $this->getVoucherDiscountAmount();
        $discountFromPoints = $this->getDiscountFromPoints();
        $bundleDiscount = $this->bundleDiscount;
        $grandTotal = max(0, $subtotalAfterProductDiscount - $globalDiscountAmount - $voucherDiscountAmount - $discountFromPoints - $bundleDiscount);
        $totalProfit = collect($this->cart)->sum(fn ($item) => $item['profit'] * $item['quantity']);

        $discountId = null;
        if ($this->appliedVoucher) {
            $discountId = $this->appliedVoucher->id;
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'shift_id' => ShiftService::getActiveShift(Auth::id())?->id,
            'customer_id' => $this->selectedCustomerId,
            'discount_id' => $discountId,
            'total' => $grandTotal,
            'subtotal_before_discount' => $subtotalBeforeDiscount,
            'discount_amount' => $this->getProductDiscountAmount() + $globalDiscountAmount + $voucherDiscountAmount,
            'voucher_code' => $this->appliedVoucher?->code,
            'payment_method' => 'split',
            'cash_amount' => null,
            'change_amount' => null,
            'points_earned' => 0,
            'points_redeemed' => $this->redeemPoints,
            'discount_from_points' => $discountFromPoints,
            'is_split' => true,
            'total_splits' => count($splits),
        ]);

        foreach ($this->cart as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => ($item['discount_amount'] ?? 0) + ($item['bundle_discount'] ?? 0),
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            // Decrement variant stock if variant, otherwise product stock
            if (! empty($item['variant_id'])) {
                $this->variantService->decrementStock($item['variant_id'], $item['quantity']);
            } else {
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }
        }

        // Check for reorder alerts after stock decrement
        ReorderPointService::checkStockLevels();

        foreach ($splits as $split) {
            SplitBill::create([
                'transaction_id' => $transaction->id,
                'split_number' => $split['number'],
                'subtotal' => $split['amount'],
                'payment_method' => $split['payment_method'],
                'amount_paid' => $split['amount'],
                'reference' => $split['reference'] ?? null,
                'notes' => null,
            ]);

            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'payment_method' => $split['payment_method'],
                'amount' => $split['amount'],
                'reference' => $split['reference'] ?? null,
            ]);
        }

        if ($this->appliedVoucher) {
            $this->discountService->incrementUsage($this->appliedVoucher);
        }

        if ($this->selectedCustomer) {
            if ($this->redeemPoints > 0) {
                $this->pointService->redeemPoints($this->selectedCustomer, $this->redeemPoints, $transaction->id);
            }

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal, $this->selectedCustomer?->membershipTier);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
            $this->selectedCustomer->recalculateTier();
            $transaction->update(['points_earned' => $pointsEarned]);
        }

        FinancialRecord::create([
            'type' => 'sales',
            'amount' => $grandTotal,
            'profit' => $totalProfit,
            'transaction_id' => $transaction->id,
            'description' => 'Penjualan produk (Split Bill - '.count($splits).' bagian)'.($this->selectedCustomer ? ' - '.$this->selectedCustomer->name : ''),
            'record_date' => now()->toDateString(),
        ]);

        return $transaction;
    }

    protected function finalizeTransaction(Transaction $transaction): void
    {
        $this->lastTransactionId = $transaction->id;

        Notification::make()
            ->title('Transaksi berhasil')
            ->success()
            ->send();

        $this->dispatch('transaction-completed', [
            'transactionId' => $transaction->id,
            'templateId' => $this->selectedTemplateId,
            'transactionData' => $this->getTransactionData($transaction->id),
        ]);

        $this->reset(['cart', 'cashAmount', 'paymentMethod', 'selectedCategoryId', 'searchQuery', 'selectedCustomerId', 'redeemPoints', 'usePoints', 'voucherCode', 'appliedVoucher', 'voucherError']);
        $this->showSuccessModal = true;
    }

    public function getTransactionData($transactionId)
    {
        return Transaction::with('items.product', 'customer', 'payments', 'splitBills')->find($transactionId);
    }

    public function getTemplatesProperty()
    {
        return $this->availableTemplates->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'template_data' => $template->template_data,
            ];
        });
    }

    public function initiateDigitalPayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->body('Tidak ada item yang bisa dibayar.')
                ->danger()
                ->send();

            return;
        }

        $transaction = $this->createPendingTransaction();

        if ($transaction) {
            $this->dispatch('openPaymentModal', [
                'transactionId' => $transaction->id,
                'method' => 'qris',
            ]);
        }
    }

    protected function createPendingTransaction(): ?Transaction
    {
        try {
            $subtotal = $this->getSubtotalBeforeDiscount();
            $discountAmount = $this->discountAmount;
            $total = $this->getTotal();

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'customer_id' => $this->customerId ?: null,
                'total' => $total,
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $discountAmount,
                'voucher_code' => $this->voucherCode ?: null,
                'payment_method' => 'digital',
                'payment_gateway_status' => 'pending',
                'is_split' => false,
                'subtotal_before_tax' => $this->getSubtotalBeforeTax(),
                'tax_amount' => $this->getTaxAmount(),
                'tax_rate' => $this->getTaxRate(),
                'tax_enabled' => $this->isTaxEnabled(),
            ]);

            foreach ($this->cart as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Failed to create pending transaction', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Gagal membuat transaksi')
                ->body('Terjadi kesalahan saat membuat transaksi.')
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Handle digital payment - create pending transaction and open payment modal.
     */
    protected function handleDigitalPayment(): void
    {
        try {
            $subtotal = $this->getSubtotalBeforeDiscount();
            $discountAmount = $this->getProductDiscountAmount() + $this->getGlobalDiscountAmount()
                + $this->getVoucherDiscountAmount() + ($this->bundleDiscount ?? 0);
            $total = $this->grandTotal;

            $shift = ShiftService::getActiveShift(Auth::id());

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'shift_id' => $shift?->id,
                'customer_id' => $this->selectedCustomerId ?: null,
                'discount_id' => $this->appliedVoucher?->id,
                'total' => $total,
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $discountAmount,
                'voucher_code' => $this->appliedVoucher?->code,
                'payment_method' => 'digital',
                'payment_gateway_status' => 'pending',
                'is_split' => false,
                'subtotal_before_tax' => $subtotal - $discountAmount,
                'tax_amount' => $this->taxAmount ?? 0,
                'tax_rate' => $this->taxEnabled ? ($this->store?->tax_rate ?? 0) : 0,
                'tax_enabled' => $this->taxEnabled ?? false,
            ]);

            // Create transaction items
            foreach ($this->cart as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['final_price'] ?? $item['selling_price'],
                    'original_price' => $item['original_price'] ?? $item['selling_price'],
                    'discount_amount' => ($item['discount_amount'] ?? 0) + ($item['bundle_discount'] ?? 0),
                    'discount_id' => $item['discount_id'] ?? null,
                    'profit' => $item['profit'],
                    'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
                ]);
            }

            // Initiate payment via PaymentController
            $paymentController = app(\App\Http\Controllers\PaymentController::class);
            $paymentRequest = new \Illuminate\Http\Request(['method' => 'qris']);
            $response = $paymentController->initiatePayment($paymentRequest, $transaction);
            $result = $response->getData(true);

            if ($result['success'] ?? false) {
                // Determine payment method (qris or invoice)
                $paymentMethod = $result['payment_url'] ?? null ? 'invoice' : 'qris';

                // Dispatch event to show payment modal
                $this->dispatch('showPaymentModal', [
                    'transaction_id' => $transaction->id,
                    'payment_method' => $paymentMethod,
                    'qr_image_url' => $result['qr_image_url'] ?? null,
                    'payment_url' => $result['payment_url'] ?? null,
                    'amount' => $result['amount'] ?? $transaction->total,
                    'reference' => $result['reference'] ?? null,
                    'expires_at' => $result['expires_at'] ?? null,
                ]);

                // Update transaction with payment gateway details
                $transaction->update([
                    'payment_gateway_reference' => $result['reference'] ?? null,
                    'payment_gateway_transaction_id' => $result['transaction_id'] ?? null,
                    'payment_gateway_expires_at' => isset($result['expires_at'])
                        ? now()->setTimestamp($result['expires_at'] / 1000)
                        : now()->addHours(24),
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Failed to initiate payment');
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle digital payment', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Gagal Memproses Pembayaran Digital')
                ->body('Terjadi kesalahan: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function onDigitalPaymentSuccess(int $transactionId): void
    {
        $transaction = Transaction::find($transactionId);

        if ($transaction) {
            $this->lastTransactionId = $transactionId;
            $this->cart = [];
            $this->customerId = null;
            $this->voucherCode = null;
            $this->discountAmount = 0;
            $this->showSuccessModal = true;
            $this->showCart = true;

            Notification::make()
                ->title('Pembayaran Berhasil')
                ->body('Transaksi telah berhasil dibayar.')
                ->success()
                ->send();

            $this->dispatch('printReceipt', $transactionId);
        }
    }
}
