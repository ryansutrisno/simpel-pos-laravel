# Simpel POS

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/Filament-3.x-purple?style=for-the-badge" alt="Filament">
  <img src="https://img.shields.io/badge/Tailwind-4.x-cyan?style=for-the-badge&logo=tailwindcss" alt="Tailwind">
</p>

A simple, modern Point of Sale (POS) system built with Laravel 12, Filament 3, and Livewire 3.

## Features

- **Product Management**: Manage products with categories, stock tracking, and pricing
- **Transaction Processing**: Complete POS system with cart management and checkout
- **Customer Management**: Customer database with loyalty points, purchase history, and point redemption
- **Loyalty Points System**: Earn points (Rp 10.000 = 1 point), redeem points (1 point = Rp 1.000), max 50% of transaction
- **Discount System**: Product discounts, category discounts, global discounts, and voucher/coupon codes with stackable options
- **Financial Records**: Automatic profit tracking and financial reporting
- **Receipt Templates**: Customizable receipt templates with multiple formatting options
- **Bluetooth Printing**: Web Bluetooth API integration for thermal printers
- **Dashboard**: Real-time statistics and charts for sales and financial data
- **Multi-Payment Support**: Combine multiple payment methods (cash, transfer, QRIS) in one transaction
- **Hold/Suspend Transaction**: Save pending transactions, resume later (max 5 per cashier)
- **Split Bill**: Divide transaction for multiple payers with separate payments
- **Barcode Scanner**: Auto-focus input, scan barcode to add products to cart
- **Inventory Management**: Supplier management, purchase orders, stock adjustments, and stock opname
- **Comprehensive Reports**: Sales, purchase, profit/loss, stock card, debt, and end of day reports
- **Return/Refund System**: Full return, partial return, and exchange with multiple refund methods (cash, store credit, original payment)
- **Store Credit**: Customer credit balance from returns with expiry tracking
- **Tax (PPN) System**: Configure tax rate, enable/disable per store, automatic calculation in transactions
- **Backup & Restore**: Automatic daily database backups with manual backup options, restore from backup, and backup management UI
- **Role-Based Access Control**: Filament Shield integration with 5 predefined roles
- **Expense Tracking**: Record operational expenses with categories (utilities, salaries, supplies, etc.)
- **Shift Management**: Manage cashier shifts (morning/evening) with opening/closing cash tracking
- **Staff Performance Report**: Track sales performance per cashier with transaction metrics
- **Product Variants**: Manage product variations (size, color, flavor) with separate stock and pricing per variant
- **Product Bundles**: Create product packages with special bundle pricing and auto-apply in POS
- **Reorder Point Alerts**: Automatic low stock notifications with severity levels and dashboard widget
- **Bulk Import Products**: Import hundreds of products from Excel with drag & drop, template download, and validation
- **Membership Tier System**: Bronze/Silver/Gold tiers with point multipliers, auto tier assignment based on total spent
- **Inventory Valuation Report**: Calculate inventory value for tax reporting with FIFO, LIFO, and Weighted Average methods
- **Purchase Price History**: Track purchase price changes over time with trends and supplier filtering
- **Payment Gateway Integration**: Accept digital payments via Mayar (QRIS, Invoice) with real-time status updates and webhook support
- **Multi-Store Support**: Manage multiple stores with per-store stock tracking, separate transactions, store switcher for super admins, and automatic data scoping

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2)
- **Admin Panel**: Filament 3
- **Frontend**: Livewire 3 + Tailwind CSS 4
- **Database**: SQLite (default), MySQL/PostgreSQL supported
- **Testing**: Pest 3
- **Code Style**: Laravel Pint

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- Laravel Herd (recommended) or any PHP web server

### First-Time Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url> simpel-pos
   cd simpel-pos
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   # Create SQLite database (default)
   touch database/database.sqlite
   
   # Or configure MySQL/PostgreSQL in .env
   # DB_CONNECTION=mysql
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=simpel_pos
   # DB_USERNAME=your_username
   # DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**
   ```bash
   # Run migrations with seeders to populate default data
   php artisan migrate --seed
   ```
   
**Default data seeded includes:**
     - Super Admin user (email: `superadmin@pos.test`, password: `password`, role: `super_admin`)
     - Admin user (email: `admin@pos.test`, password: `password`, role: `admin`)
     - Manager user (email: `manager@pos.test`, password: `password`, role: `manager`)
     - Kasir user (email: `kasir@pos.test`, password: `password`, role: `kasir`)
     - 5 roles with permissions (super_admin, admin, manager, kasir, panel_user)
     - 5 sample customers
     - Default receipt templates

6. **Build frontend assets**
   ```bash
   npm run build
   ```

## Local Development

### Start Development Server

Using Laravel Herd (recommended):
```bash
# The app is automatically available at https://simpel-pos.test
```

Using PHP built-in server:
```bash
php artisan serve
# App available at http://localhost:8000
```

### Development Workflow

1. **Start all services** (server, queue, logs, vite):
   ```bash
   composer run dev
   ```

2. **Run tests**:
   ```bash
   # Run all tests
   php artisan test
   
   # Run specific test file
   php artisan test tests/Feature/PosTest.php
   
   # Run filtered tests
   php artisan test --filter=testCheckout
   ```

3. **Code formatting**:
   ```bash
   # Format all files
   vendor/bin/pint
   
   # Format only modified files
   vendor/bin/pint --dirty
   ```

4. **Clear caches** (if needed):
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

5. **Re-seed database** (if needed during development):
   ```bash
   # Fresh migration with seeders (WARNING: deletes all data)
   php artisan migrate:fresh --seed
   
   # Or run seeders only
   php artisan db:seed
   ```

### Accessing the Application

- **Admin Panel**: `https://simpel-pos.test/admin` (or `/admin` on local server)
- **POS Interface**: Available in Filament admin panel
- **API Endpoints**: 
  - `GET /api/transactions/{id}` - Get transaction data for printing
  - `GET /api/returns/{id}` - Get return data for printing
  - `GET /api/returns/{id}/receipt` - Get return receipt preview

## Production Deployment

### Pre-Deployment Checklist

1. **Set production environment variables**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   
   # Configure production database
   DB_CONNECTION=mysql
   # ... other database settings
   
   # Set secure app key
   APP_KEY=your-generated-key
   ```

2. **Optimize application**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Build production assets**:
   ```bash
   npm run build
   ```

4. **Set proper permissions**:
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Deployment Steps

1. **Deploy code to server**
   ```bash
   git pull origin main
   ```

2. **Install dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm ci
   ```

3. **Run migrations and seeders**
   ```bash
   # Run migrations with seeders for first-time deployment
   php artisan migrate --seed --force
   ```
   
**Note**: This will seed default data including:
    - Multiple users with roles (super_admin, admin, manager, kasir)
    - Default receipt templates

4. **Clear and cache configurations**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

### Post-Deployment

1. **Configure store settings** via admin panel

2. **Test Bluetooth printer connectivity** (if using)

3. **Assign roles to users** via User Management in admin panel

## Project Structure

```
app/
├── Filament/           # Filament admin resources and pages
│   ├── Resources/     # CRUD resources (Product, Category, Transaction, etc.)
│   ├── Pages/         # Custom pages (POS, Reports, etc.)
│   └── Widgets/       # Dashboard widgets (Sales Chart, Low Stock Alert, etc.)
├── Http/Controllers/   # API and web controllers
├── Livewire/          # Livewire components (POS, StoreSwitcher, etc.)
├── Models/            # Eloquent models
│   ├── Product.php
│   ├── ProductStock.php        # Per-store stock tracking
│   ├── ProductVariant.php     # Product variations
│   ├── ProductBundle.php      # Bundle headers
│   ├── BundleItem.php         # Bundle line items
│   ├── ReorderAlert.php       # Low stock alerts
│   ├── Concerns/
│   │   └── BelongsToStore.php # Multi-store trait
│   └── ...
├── Observers/         # Model observers
├── Scopes/            # Global scopes
│   └── StoreScope.php # Multi-store global scope
└── Services/          # Business logic services
    ├── CurrentStoreService.php # Multi-store context
    ├── StockService.php        # Stock with multi-store
    ├── VariantService.php     # Variant operations
    ├── BundleService.php      # Bundle management
    ├── ReorderPointService.php # Alert management
    └── ...

resources/
├── js/               # JavaScript (Bluetooth printer, etc.)
├── views/            # Blade templates
│   ├── livewire/     # Livewire component views
│   └── filament/     # Filament custom views
└── css/              # Tailwind CSS

database/
├── migrations/        # Database migrations
├── factories/         # Model factories for testing
└── seeders/          # Database seeders
```

## Key Services

### PointService
Handles loyalty point calculations:
- `calculateEarnedPoints($amount, $tier)` - Calculate points from transaction amount with optional tier multiplier
- `calculateRedeemValue($points)` - Calculate discount value from points
- `getMaxRedeemablePoints($points, $total)` - Get max redeemable points
- Earn rate: Rp 10.000 = 1 point
- Redeem rate: 1 point = Rp 1.000
- Minimum redeem: 10 points
- Maximum redeem: 50% of transaction total

### MembershipTierService
Handles membership tier operations:

### InventoryValuationService
Handles inventory valuation calculations:

### PurchasePriceHistoryService
Handles purchase price history tracking:
- `getPriceHistory($productId, $supplierId, $startDate, $endDate)` - Get price history with filters
- `getPriceTrend($productId, $supplierId)` - Get monthly price trends
- `getLatestPrice($productId, $supplierId)` - Get most recent purchase price
- `getProductsWithHistory()` - Get products with purchase history
- `getSuppliersWithHistory()` - Get suppliers with purchase history
- Shows historical prices from received purchase orders
- `getInventoryValue($method, $referenceDate, $categoryId)` - Get complete inventory valuation report
- `getProductCost($product, $method, $referenceDate)` - Calculate cost per product
- `getFIFOCost($product, $referenceDate)` - First In First Out cost method
- `getLIFOCost($product, $referenceDate)` - Last In First Out cost method
- `getWeightedAverageCost($product, $referenceDate)` - Weighted Average cost method (default)
- Supports reference date selection (e.g., December 31 for tax reporting)
- Excludes products with zero stock
- `getAllTiers()` - Get all active membership tiers
- `getTierForSpent($totalSpent)` - Get tier based on customer total spent
- `recalculateCustomerTier($customer)` - Recalculate and update customer tier
- `getNextTier($customer)` - Get the next tier a customer can achieve
- `getPointsToNextTier($customer)` - Calculate points/spend needed for next tier
- `calculatePointsWithTier($amount, $tier)` - Calculate points with tier multiplier
- `assignTierToAllCustomers()` - Bulk assign tiers to all existing customers

### DiscountService
Handles discount calculations:
- Product discounts (automatic on selected products)
- Category discounts (automatic for products in category)
- Global discounts (site-wide promotions)
- Voucher/coupon codes (manual input)
- Stackable discounts (multiple discounts per transaction)
- Min purchase validation, max discount limits, usage limits

### ReceiptTemplateService
Manages receipt template operations:
- `getActiveTemplate()` - Get active template for store
- `createTemplate()` - Create new template
- `updateTemplate()` - Update existing template
- `validateTemplateData()` - Validate template structure
- `renderReceipt()` - Render receipt using ReceiptRenderer

### ReceiptRenderer
Handles ESC/POS code generation for thermal printers:
- Supports header, body, footer sections
- Configurable alignment, font size, separators
- Barcode and QR code support

### ReturnService
Handles return/refund operations:
- `validateReturnEligibility()` - Check if transaction is within return deadline
- `calculateRefund()` - Calculate refund amounts including exchange values
- `createReturn()` - Process return with stock updates and financial records
- `handlePointsReversal()` - Reverse earned points on return
- `handlePointsReturn()` - Return redeemed points on return
- Return deadline: Configurable per store (default 7 days)

### StoreCreditService
Manages customer store credits:
- `earnCredit()` - Add credit to customer from return
- `useCredit()` - Use credit for transaction
- `getBalance()` - Get customer's current credit balance
- `checkAndExpireCredits()` - Auto-expire credits past expiry date
- Credit expiry: Configurable (default 180 days) or never expires

### TaxService
Handles tax calculations:
- `calculateTax($subtotal, $rate)` - Calculate tax amount from subtotal
- `calculateTotalWithTax($subtotal, $rate)` - Calculate total including tax
- `getTaxData($store, $subtotal)` - Get complete tax data for a transaction
- `calculateCartTax($items, $rate)` - Calculate tax for cart items
- Tax rate: Configurable per store (default 10%)
- Tax name: Configurable per store (default "PPN")

### Backup & Restore
Handles database backup and restore operations:
- Automatic daily backups via scheduled task (02:00 AM)
- Manual backup creation (database only or full backup)
- Restore database from backup file with confirmation
- Download and delete backup files
- Backup cleanup with retention policy (7 days)
- Storage location: `storage/app/backups/Laravel/`
- Commands:
  - `php artisan backup:run --only-db` - Backup database only
  - `php artisan backup:run` - Full backup (database + files)
  - `php artisan backup:restore {filename}` - Restore from backup
  - `php artisan backup:clean` - Clean old backups

### ExpenseService
Manages operational expense tracking:
- `generateExpenseNumber()` - Generate unique expense number (EXP-YYYYMMDD-XXXX format)
- `getTotalByCategory()` - Get expense totals grouped by category
- `getDailyExpense()` - Get total expenses for a specific date
- `getMonthlyExpense()` - Get total expenses for a month
- `getExpenseByShift()` - Get expenses linked to a specific shift
- Supports expense categories: utilities, salaries, supplies, etc.

### ShiftService
Manages cashier shift operations:
- `openShift()` - Open new shift with opening cash
- `closeShift()` - Close shift with closing cash and calculate difference
- `getActiveShift()` - Get user's currently active shift
- `hasActiveShift()` - Check if user has an open shift
- `getShiftSummary()` - Get shift summary (sales, transactions, expenses)
- `calculateExpectedCash()` - Calculate expected closing cash
- Shift types: morning (pagi), evening (sore)

### StaffPerformanceService
Tracks cashier performance metrics:
- `getSalesByUser()` - Get total sales by user in date range
- `getTransactionCountByUser()` - Get transaction count by user
- `getAverageTransactionValue()` - Get average transaction value
- `getItemsSoldByUser()` - Get total items sold by user
- `getTopStaff()` - Get top performing staff with rankings

### VariantService
Manages product variant operations:
- `createVariant()` - Create new product variant with SKU generation
- `updateVariant()` - Update variant attributes, price, stock
- `validateVariantData()` - Validate variant structure and uniqueness
- `getAvailableVariants()` - Get variants available for sale
- `calculateVariantPrice()` - Calculate price with variant adjustment
- `adjustStock()` - Adjust stock for specific variant

### BundleService
Manages product bundle operations:
- `createBundle()` - Create new product bundle
- `validateBundle()` - Validate bundle items and pricing
- `calculateBundlePrice()` - Calculate total vs bundle price
- `checkBundleAvailability()` - Check if all items available
- `autoApplyBundle()` - Auto-apply bundle in cart
- `getActiveBundles()` - Get all active bundles

### ReorderPointService
Manages reorder point alerts:
- `checkAndCreateAlerts()` - Check stock levels and create alerts
- `createAlert()` - Create new reorder alert
- `dismissAlert()` - Dismiss alert after reorder
- `getActiveAlerts()` - Get active alerts with severity
- `calculateSeverity()` - Determine alert severity level
- `getProductsNeedingReorder()` - Get products below reorder point

### PaymentGatewayService
Manages digital payment processing:
- `initiatePayment()` - Generate QRIS or Invoice payment
- `checkPaymentStatus()` - Check payment status (pending/paid/expired)
- `handleWebhook()` - Process payment gateway webhooks
- `cancelPayment()` - Cancel pending payment
- Supports payment providers: Mayar (active), Midtrans (planned)
- Payment methods: QRIS (dynamic), Invoice (link)
- Real-time status updates via polling
- Automatic transaction status update on payment success
- Webhook handling for payment callbacks
- Sandbox mode support for testing

### CurrentStoreService
Manages multi-store context:
- `get()` - Get current Store model
- `getId()` - Get current store ID
- `set(Store|int)` - Set current store (updates session + user.current_store_id)
- `hasStore()` - Check if store is set
- `isSuperAdmin()` - Check if user has super_admin role
- `canAccessAllStores()` - Check if user can access all stores
- Session-based store persistence
- Automatic user assignment to store

### StockService
Handles stock operations with multi-store support:
- `addStock($productId, $quantity, $storeId, $variantId)` - Add stock to specific store
- `subtractStock($productId, $quantity, $storeId, $variantId)` - Subtract stock from specific store
- `getStock($productId, $storeId, $variantId)` - Get stock for product in store
- `adjustStock($productId, $quantity, $reason, $storeId)` - Stock adjustment with reason
- Per-store stock tracking via ProductStock model
- Automatic low stock detection per store

## Troubleshooting

### Vite Manifest Error
```
Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest
```
**Solution**: Run `npm run build` or `npm run dev`

### Bluetooth Printer Not Connecting
- Ensure browser supports Web Bluetooth API (Chrome/Edge)
- Check printer is in pairing mode
- Verify service UUID: `000018f0-0000-1000-8000-00805f9b34fb`

### Tests Failing
```bash
# Clear config cache
php artisan config:clear

# Run with verbose output
php artisan test --verbose
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Credits

- **Ryan Sutrisno** - [GitHub](https://github.com/ryansutrisno)

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).
