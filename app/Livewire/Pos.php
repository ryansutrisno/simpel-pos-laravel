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
use App\Services\DiscountService;
use App\Services\PointService;
use App\Services\ReceiptTemplateService;
use App\Services\ShiftService;
use App\Services\TaxService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
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

    public $taxEnabled = false;

    protected PointService $pointService;

    protected DiscountService $discountService;

    protected TaxService $taxService;

    protected $listeners = ['barcode-scanned' => 'processBarcode'];

    public function boot(PointService $pointService, DiscountService $discountService, TaxService $taxService): void
    {
        $this->pointService = $pointService;
        $this->discountService = $discountService;
        $this->taxService = $taxService;
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
        return $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;
    }

    public function getCustomerPointsProperty(): int
    {
        return $this->selectedCustomer?->points ?? 0;
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

        return max(0, $subtotal - $globalDiscount - $voucherDiscount - $pointsDiscount);
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

        return $this->pointService->calculateEarnedPoints($this->grandTotal);
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

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        $discountInfo = $this->discountService->calculateProductDiscount($product);

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'original_price' => $discountInfo['original_price'],
            'discount_amount' => $discountInfo['discount_amount'],
            'final_price' => $discountInfo['final_price'],
            'discount_id' => $discountInfo['discount']?->id,
            'quantity' => 1,
            'profit' => $discountInfo['final_price'] - $product->purchase_price,
        ];
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

        $subtotalBeforeDiscount = $this->getSubtotalBeforeDiscount();
        $subtotalAfterProductDiscount = $this->getSubtotal();
        $globalDiscountAmount = $this->getGlobalDiscountAmount();
        $voucherDiscountAmount = $this->getVoucherDiscountAmount();
        $discountFromPoints = $this->getDiscountFromPoints();
        $totalDiscountAmount = $globalDiscountAmount + $voucherDiscountAmount + $discountFromPoints;
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
            'discount_amount' => $this->getProductDiscountAmount() + $globalDiscountAmount + $voucherDiscountAmount,
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
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            Product::find($item['product_id'])->decrement('stock', $item['quantity']);
        }

        if ($this->appliedVoucher) {
            $this->discountService->incrementUsage($this->appliedVoucher);
        }

        if ($this->selectedCustomer) {
            if ($this->redeemPoints > 0) {
                $this->pointService->redeemPoints($this->selectedCustomer, $this->redeemPoints, $transaction->id);
            }

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
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
        $grandTotal = max(0, $subtotalAfterProductDiscount - $globalDiscountAmount - $voucherDiscountAmount - $discountFromPoints);
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
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            Product::find($item['product_id'])->decrement('stock', $item['quantity']);
        }

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

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
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
        $grandTotal = max(0, $subtotalAfterProductDiscount - $globalDiscountAmount - $voucherDiscountAmount - $discountFromPoints);
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
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['final_price'] ?? $item['selling_price'],
                'original_price' => $item['original_price'] ?? $item['selling_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_id' => $item['discount_id'] ?? null,
                'profit' => $item['profit'],
                'subtotal' => ($item['final_price'] ?? $item['selling_price']) * $item['quantity'],
            ]);

            Product::find($item['product_id'])->decrement('stock', $item['quantity']);
        }

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

            $pointsEarned = $this->pointService->calculateEarnedPoints($grandTotal);
            if ($pointsEarned > 0) {
                $this->pointService->earnPoints(
                    $this->selectedCustomer,
                    $pointsEarned,
                    $transaction->id,
                    'Poin dari transaksi #'.$transaction->id
                );
            }

            $this->selectedCustomer->updateStats($grandTotal);
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

    protected function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            default => $method,
        };
    }
}
