# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Simpel POS** is a simple, modern Point of Sale system built with Laravel 12, Filament 3, and Livewire 3.

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.2)
- **Admin Panel**: Filament 3
- **Frontend**: Livewire 3 + Tailwind CSS 4
- **Database**: SQLite (default), MySQL/PostgreSQL supported
- **Testing**: Pest 3
- **Code Style**: Laravel Pint

### Development Server
The application is served by Laravel Herd at `https://simpel-pos.test` (or `http://simpel-pos.test`).

---

## Common Commands

```bash
# Install dependencies
composer install && npm install

# Run migrations with seeders
php artisan migrate --seed

# Start development (server, queue, logs, vite)
composer run dev

# Run tests
php artisan test
php artisan test --filter=testName
php artisan test tests/Feature/PosTest.php

# Format code
vendor/bin/pint --dirty    # Check modified files
vendor/bin/pint            # Fix all files

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Build assets (if Vite manifest error)
npm run build
```

---

## High-Level Architecture

### Core Directory Structure

```
app/
├── Filament/
│   ├── Resources/      # CRUD resources (Product, Transaction, etc.)
│   ├── Pages/          # Custom pages (POS, Reports)
│   └── Widgets/        # Dashboard widgets
├── Http/Controllers/   # API and web controllers
├── Livewire/           # Livewire components
├── Models/             # Eloquent models (35+)
├── Observers/          # Model observers
├── Policies/           # Authorization policies
├── Services/           # Business logic services (20+)
└── Enums/              # PHP enums (PaymentStatus, etc.)
```

### Key Services

| Service | Responsibility |
|---------|---------------|
| `PointService` | Loyalty points (Rp 10.000 = 1 point, redeem 1 point = Rp 1.000, max 50%) |
| `MembershipTierService` | Bronze/Silver/Gold tiers with point multipliers |
| `DiscountService` | Product/category/global/voucher discounts with stacking |
| `ReturnService` | Returns/refunds with stock updates and points reversal |
| `StoreCreditService` | Customer store credits from returns (expiry: 180 days) |
| `TaxService` | PPN tax calculation (configurable rate, default 10%) |
| `ShiftService` | Cashier shifts (morning/evening) with opening/closing cash |
| `ExpenseService` | Operational expenses by category |
| `StaffPerformanceService` | Sales metrics per cashier |
| `VariantService` | Product variants (size, color, flavor) |
| `BundleService` | Product bundles with special pricing |
| `ReorderPointService` | Low stock alerts with severity levels |
| `InventoryValuationService` | FIFO/LIFO/Weighted Average valuation |
| `PurchasePriceHistoryService` | Track purchase price trends |
| `PaymentGatewayService` | QRIS/Invoice via Mayar/Midtrans |
| `ReceiptTemplateService` | Customizable receipt templates |
| `ReceiptRenderer` | ESC/POS thermal printer codes |
| `DebtService` | Supplier debt and payments |
| `StockService` | Stock movements and history |

### Key Models

- `Product`, `ProductVariant`, `ProductBundle`, `BundleItem`, `Category`
- `Transaction`, `TransactionItem`, `TransactionPayment`, `SplitBill`, `SuspendedTransaction`
- `Customer`, `CustomerPoint`, `MembershipTier`, `StoreCredit`
- `Discount`, `ReceiptTemplate`, `PrinterConfig`, `AppSettings`
- `Supplier`, `PurchaseOrder`, `PurchaseOrderItem`, `SupplierDebt`, `DebtPayment`
- `StockAdjustment`, `StockOpname`, `StockHistory`
- `FinancialRecord`, `Expense`, `ExpenseCategory`, `Shift`, `EndOfDay`
- `ProductReturn`, `ProductReturnItem`, `ReorderAlert`

---

## Filament Resources

Filament resources are located in `app/Filament/Resources/`. Each resource has:
- Main resource class (Form/Table definition)
- Pages: `List[Resource]`, `Create[Resource]`, `Edit[Resource]`, `View[Resource]`

### Creating New Resources

Use `php artisan make:filament-resource ModelName --generate` or create manually following existing patterns.

---

## Laravel Boost MCP Tools

This project uses Laravel Boost MCP server. Key tools:

- `search-docs` - Search Laravel ecosystem docs (Laravel, Filament, Livewire, Pest, Tailwind)
- `tinker` - Execute PHP in Laravel context
- `database-query` - Run read-only SQL queries
- `browser-logs` - Read frontend console logs
- `list-artisan-commands` - List available Artisan commands
- `get-absolute-url` - Generate valid project URLs
- `last-error` - Get last backend error

---

## Coding Conventions

### PHP
- Use curly braces for all control structures
- PHP 8 constructor property promotion
- Explicit return types on all methods
- Prefer PHPDoc over comments

### Database
- Use Eloquent relationships, avoid `DB::`
- Prevent N+1 queries with eager loading
- Include all column attributes when modifying migrations

### Validation
- Create Form Request classes (not inline validation)
- Check sibling Form Requests for conventions

### Livewire
- Single root element per component
- Use `wire:model.live` for real-time updates
- Use `wire:loading`, `wire:target` for loading states
- Use `$this->dispatch()` for events

### Tailwind CSS v4
- Use `@import "tailwindcss";` (not `@tailwind` directives)
- Use `@theme` directive for custom theme values
- Avoid deprecated utilities:
  - Use `shrink-*` / `grow-*` (not `flex-shrink-*` / `flex-grow-*`)
  - Use `bg-black/*` for opacity (not `bg-opacity-*`)

### Enums
- Use TitleCase keys: `MorningShift`, `Bronze`, `Completed`

---

## Testing

All tests use Pest. Tests are in `tests/Feature/` and `tests/Unit/`.

```bash
# Create tests
php artisan make:test --pest Feature/FeatureName
php artisan make:test --pest --unit Unit/UnitTest

# Run tests
php artisan test
php artisan test --filter=testName
```

### Test Conventions
- Use model factories with states
- Use `expect()` assertions
- Use datasets for repeated test data
- Test happy paths, failure paths, and edge cases

---

## Troubleshooting

### Vite Manifest Error
```
Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest
```
**Solution**: Run `npm run build` or `npm run dev`

### Bluetooth Printer Issues
- Requires Web Bluetooth API (Chrome/Edge only)
- Service UUID: `000018f0-0000-1000-8000-00805f9b34fb`

---

## Default Credentials (from seeder)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `superadmin@pos.test` | `password` |
| Admin | `admin@pos.test` | `password` |
| Manager | `manager@pos.test` | `password` |
| Cashier | `kasir@pos.test` | `password` |

---

## Important Notes

- **Do not create documentation files** unless explicitly requested
- **Do not change dependencies** without approval
- **Do not create new base directories** without approval
- **Check sibling files** for conventions before creating new files
- **Run tests** to verify changes work
- **Run `vendor/bin/pint --dirty`** before finalizing changes
