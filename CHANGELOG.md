## [2.19.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.18.0...v2.19.0) (2026-03-12)


### ✨ Features

* add user-stores pivot table for multi-store assignment ([0ff9866](https://github.com/ryansutrisno/simple-pos-laravel/commit/0ff986611a503349cba2c55bfed300896a71a400))

## [2.18.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.17.1...v2.18.0) (2026-03-12)


### ✨ Features

* implement multi-store support ([ea6b4b6](https://github.com/ryansutrisno/simple-pos-laravel/commit/ea6b4b600b86eb199a96283fd81df0ca33a6a9c6))

## [2.17.1](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.17.0...v2.17.1) (2026-03-12)


### 🐛 Bug Fixes

* remove maxLength validation from API Key fields ([4481838](https://github.com/ryansutrisno/simple-pos-laravel/commit/44818386eab153ba9677cde6b1b445fdae0b6f09))


### ♻️ Refactoring

* migrate payment gateway from Store to PaymentGatewayConfig ([947379c](https://github.com/ryansutrisno/simple-pos-laravel/commit/947379cc32106ff242cd9255d9525012c3c99ed6))
* remove payment gateway columns from stores table ([7e76349](https://github.com/ryansutrisno/simple-pos-laravel/commit/7e76349a891df9e457acbd634b56bf034da95a92))
* update POS to use PaymentGatewayConfig instead of Store ([142e465](https://github.com/ryansutrisno/simple-pos-laravel/commit/142e465d02e33dc77e2da0428018255ca2eb207a))


### 🔧 Maintenance

* configure Claude MCP settings and update documentation ([926058e](https://github.com/ryansutrisno/simple-pos-laravel/commit/926058e7d847348a96cfd2d538d1156ee024d9b1))

## [2.17.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.16.0...v2.17.0) (2026-03-12)


### ✨ Features

* **settings:** add app_logo and favicon columns to app_settings ([143c7df](https://github.com/ryansutrisno/simple-pos-laravel/commit/143c7df146b08eb0953eeac5553e901a368e7a33))
* **settings:** add dynamic APP_NAME from database ([add10e0](https://github.com/ryansutrisno/simple-pos-laravel/commit/add10e08427b6b85003caf6f0f7a9782997dab3e))
* **settings:** add dynamic brandLogo from AppSettings ([21bbc62](https://github.com/ryansutrisno/simple-pos-laravel/commit/21bbc62b25d33c89fd1e271d9660625108871654))


### 🐛 Bug Fixes

* **settings:** add forStore scope to PaymentGatewayConfig ([110b525](https://github.com/ryansutrisno/simple-pos-laravel/commit/110b525886ace00d9a5cecc26542f886c34f63a0))
* **settings:** add missing import for LoadAppSettings middleware ([3023861](https://github.com/ryansutrisno/simple-pos-laravel/commit/3023861057718e8ab465b715467c8539f37268c0))
* **settings:** add missing import for SettingsOverviewWidget ([3ccf05a](https://github.com/ryansutrisno/simple-pos-laravel/commit/3ccf05a6c433476c763d307e139648fedf6c2340))
* **settings:** add redirect after save to refresh brand ([e099ee9](https://github.com/ryansutrisno/simple-pos-laravel/commit/e099ee9b05d16ef8d1c30aa4199ef50a8aa25edb))
* **settings:** clear cache and update config immediately after save ([08fe22a](https://github.com/ryansutrisno/simple-pos-laravel/commit/08fe22aff9050a0aede167b6bbe408603f9fe42d))
* **settings:** use dynamic brandName from AppSettings ([c413088](https://github.com/ryansutrisno/simple-pos-laravel/commit/c413088a5a24490e5350d057430522ab278f4246))

## [2.16.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.8...v2.16.0) (2026-03-10)


### ✨ Features

* **settings:** add app_settings migration ([52c1aa8](https://github.com/ryansutrisno/simple-pos-laravel/commit/52c1aa80ec81f3f1e74a07442f98d5daf0b2037b))
* **settings:** add AppSettings model with singleton pattern ([5870cd3](https://github.com/ryansutrisno/simple-pos-laravel/commit/5870cd3fbf3e8e7038b713c2f0ee3b7fa0a59f31))
* **settings:** add AppSettings seeder ([baba1fc](https://github.com/ryansutrisno/simple-pos-laravel/commit/baba1fc54a74a3ed35b929e667b3fabb0b6ee074))
* **settings:** add AppSettingsResource Filament ([311c4d9](https://github.com/ryansutrisno/simple-pos-laravel/commit/311c4d9945f641abf4d4322de378a786f4935292))
* **settings:** add AppSettingsService, PrinterConfigService, PaymentGatewayService ([780d3e1](https://github.com/ryansutrisno/simple-pos-laravel/commit/780d3e156c3459fd7fa5ae50afcf4229098b5bbe))
* **settings:** add Filament resource pages for printer and payment configs ([87dea53](https://github.com/ryansutrisno/simple-pos-laravel/commit/87dea53dc4ef74ca937ac7237e818df23f46bec0))
* **settings:** add MidtransGateway support ([d0a807b](https://github.com/ryansutrisno/simple-pos-laravel/commit/d0a807bf31cf6745787aa94789f865d596153dba))
* **settings:** add PaymentGatewayConfigResource Filament ([ccb9204](https://github.com/ryansutrisno/simple-pos-laravel/commit/ccb9204f12eb7b4ab6e2f365b214d5c3744ec2c5))
* **settings:** add printer_configs migration ([57234be](https://github.com/ryansutrisno/simple-pos-laravel/commit/57234be72f123dde2056ac9d2a99c90881adb122))
* **settings:** add PrinterConfig model with relationships ([648aef6](https://github.com/ryansutrisno/simple-pos-laravel/commit/648aef613c2b4534be436098e0b4cb066327c3ad))
* **settings:** add PrinterConfigResource Filament ([3661fb9](https://github.com/ryansutrisno/simple-pos-laravel/commit/3661fb9a4bb92caeee8914c01a6be1d621931a40))
* **settings:** add SettingsOverviewWidget to dashboard ([6ef9dc8](https://github.com/ryansutrisno/simple-pos-laravel/commit/6ef9dc8241ec34c3aa839f4085cb19bfc99aba22))
* **settings:** extend payment_gateway_configs with provider_config ([af8c963](https://github.com/ryansutrisno/simple-pos-laravel/commit/af8c963e213395ecdefe9c9343ef6a666fab6e65))
* **settings:** extend PaymentGatewayConfig with provider enum ([40ca6e0](https://github.com/ryansutrisno/simple-pos-laravel/commit/40ca6e0f988a5ef999f3439fb1dc8aa69eb40c5e))
* **settings:** integrate AppSettings ke POS ([8d90d89](https://github.com/ryansutrisno/simple-pos-laravel/commit/8d90d897381c861e53f0491d5c8076f614de5252))
* **settings:** integrate PrinterConfig ke Receipt ([e954ded](https://github.com/ryansutrisno/simple-pos-laravel/commit/e954dedf0287c4c8fba0a22896d1cd0bd269b63c))


### 📚 Documentation

* **settings:** add evidence for task 4 (redundant, already in task 2) ([a1be401](https://github.com/ryansutrisno/simple-pos-laravel/commit/a1be4017f463a34d25bc6ae0824aa0d656418b91))

## [2.15.8](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.7...v2.15.8) (2026-03-10)


### 🐛 Bug Fixes

* **inventory-valuation-report:** enhance table row hover state consistency ([8f70052](https://github.com/ryansutrisno/simple-pos-laravel/commit/8f70052ddccc2a08265d026c094dcb5cd4d417da))

## [2.15.7](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.6...v2.15.7) (2026-03-10)


### 🐛 Bug Fixes

* improve table row hover visibility in dark mode ([faf300c](https://github.com/ryansutrisno/simple-pos-laravel/commit/faf300c5246b218b03579b1ab108679eadcffbcc))

## [2.15.6](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.5...v2.15.6) (2026-03-10)


### 🐛 Bug Fixes

* inventory valuation table dark mode support ([9b2d157](https://github.com/ryansutrisno/simple-pos-laravel/commit/9b2d1576b4632be6483169298ffaba000e22132c))

## [2.15.5](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.4...v2.15.5) (2026-03-10)


### 🐛 Bug Fixes

* add null check for payment_gateway_status visibility ([ee88ef3](https://github.com/ryansutrisno/simple-pos-laravel/commit/ee88ef3cb898aa4f1143329b1fdf9a17dd0e6466))
* sales report dark mode and responsive layout ([b61a59b](https://github.com/ryansutrisno/simple-pos-laravel/commit/b61a59bb8d71a978bc3cb3b86e14017c8f769adb))

## [2.15.4](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.3...v2.15.4) (2026-03-10)


### 🐛 Bug Fixes

* add null check for payment_gateway_status visibility ([ea52054](https://github.com/ryansutrisno/simple-pos-laravel/commit/ea520540b690fdb5364c5f4cfb27faf1a162c4cc))

## [2.15.3](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.2...v2.15.3) (2026-03-08)


### 🐛 Bug Fixes

* transaction list improvements for digital payments ([12cb53a](https://github.com/ryansutrisno/simple-pos-laravel/commit/12cb53a5e7aa9d4824f7bdb54b4ef8e60a5d34e3))

## [2.15.2](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.1...v2.15.2) (2026-03-08)


### 🐛 Bug Fixes

* add JavaScript Livewire listener for showPaymentModal event ([7056bba](https://github.com/ryansutrisno/simple-pos-laravel/commit/7056bbae6e219c5e1107df0d6bb4b7f68c7f618b))

## [2.15.1](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.15.0...v2.15.1) (2026-03-08)


### 🐛 Bug Fixes

* use x-on for payment modal event listener ([262676d](https://github.com/ryansutrisno/simple-pos-laravel/commit/262676dfe653e7b14a19f76466964d9ea4fc0c75))

## [2.15.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.14.1...v2.15.0) (2026-03-08)


### ✨ Features

* add complete payment method breakdown to End of Day report ([d2ae4bb](https://github.com/ryansutrisno/simple-pos-laravel/commit/d2ae4bbd98d4e0fef37ba47c645bf8ba859ea166))

## [2.14.1](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.14.0...v2.14.1) (2026-03-07)


### 📚 Documentation

* add Payment Gateway feature to README ([09cb93d](https://github.com/ryansutrisno/simple-pos-laravel/commit/09cb93db50f199d755f096418d884f7ad9b8f7d4))
* update PaymentGatewayService documentation ([4d8c511](https://github.com/ryansutrisno/simple-pos-laravel/commit/4d8c5116c96dd17db69310f4beb10c9020e71ed5))
* update README clone command and improve top-staff widget styling ([edaa1c9](https://github.com/ryansutrisno/simple-pos-laravel/commit/edaa1c902395553ad9c620b4158301ab029ca885))

## [2.14.0](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.13.2...v2.14.0) (2026-03-07)


### ✨ Features

* implement Mayar payment gateway integration ([474a9ba](https://github.com/ryansutrisno/simple-pos-laravel/commit/474a9ba6ccf4dc5ae82cf16d7eacfb820540d5db))

## [2.13.2](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.13.1...v2.13.2) (2026-03-07)


### 🐛 Bug Fixes

* **receipt-template:** fix menu visibility and add preview functionality ([8b13e41](https://github.com/ryansutrisno/simple-pos-laravel/commit/8b13e41b9d7a8b676972983dc1ee551ead51b322))

## [2.13.1](https://github.com/ryansutrisno/simple-pos-laravel/compare/v2.13.0...v2.13.1) (2026-03-07)


### 🔧 Maintenance

* setup semantic-release with commit links support ([0a92b0a](https://github.com/ryansutrisno/simple-pos-laravel/commit/0a92b0a4c017700c7196daebc2c799a0f7e1db82))

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.13.0] - 2026-03-07

### Added

#### Purchase Price History Report
- PurchasePriceHistoryService for tracking purchase price changes
- PurchasePriceHistoryReport page in Filament under Laporan menu
- Filter by product, supplier, and date range
- Summary cards: lowest price, highest price, average price, total transactions
- Price trend table showing monthly price movements
- Shows historical purchase prices from received purchase orders

#### Services
- PurchasePriceHistoryService:
  - `getPriceHistory($productId, $supplierId, $startDate, $endDate)` - Get price history
  - `getPriceTrend($productId, $supplierId)` - Get monthly price trends
  - `getLatestPrice($productId, $supplierId)` - Get most recent purchase price
  - `getProductsWithHistory()` - Get products with purchase history
  - `getSuppliersWithHistory()` - Get suppliers with purchase history

## [2.12.0] - 2026-03-06

### Added

#### Inventory Valuation Report
- InventoryValuationService with FIFO, LIFO, and Weighted Average calculation methods
- InventoryValuationReport page in Filament under Laporan menu
- Reference date selection for valuation (e.g., end of year for tax reporting)
- Category filtering for targeted reports
- Summary cards showing total products, total quantity, and total inventory value
- Detailed table with product, SKU, quantity, unit cost, and total value
- Excel export functionality
- Excludes products with 0 stock by default
- Default method: Weighted Average

#### Services
- InventoryValuationService:
  - `getInventoryValue($method, $referenceDate, $categoryId)` - Main method
  - `getProductCost($product, $method, $referenceDate)` - Cost per product
  - `getFIFOCost()` - First In First Out calculation
  - `getLIFOCost()` - Last In First Out calculation
  - `getWeightedAverageCost()` - Weighted Average calculation

#### Exports
- InventoryValuationExport for Excel download

## [2.11.0] - 2026-03-05

### Added

#### Membership Tier System
- MembershipTier model with customizable tiers
- Three default tiers: Bronze (1.0x), Silver (1.5x), Gold (2.0x)
- Point multiplier based on customer tier
- Auto tier assignment based on total_spent
- Auto tier recalculation after each transaction
- Customizable tier benefits (JSON array)
- Tier color picker in admin panel
- Customer view shows tier and progress to next tier

#### Database Changes
- New membership_tiers table (name, slug, min_spent, multiplier, color, benefits, sort_order)
- Added membership_tier_id foreign key to customers table

#### Filament Resources
- MembershipTierResource with CRUD operations
- CustomerResource updated with tier selection
- ViewCustomer page shows tier information and progress

#### POS Integration
- Points calculation uses tier multiplier
- Customer tier loaded with membershipTier relationship
- Tier recalculated after each transaction

## [2.10.0] - 2026-03-04

### Added

#### Bulk Import Products System
- ProductImport class using Laravel Excel (maatwebsite/excel v3.1)
- Excel template with heading row support (Indonesian column names)
- Drag & drop file upload in Filament admin panel
- Downloadable template file (product_import_template.xlsx)
- Progress bar for tracking import progress
- Validation per row with detailed error messages
- Auto-generate SKU format: SKU-YYYYMMDD-XXXX
- Auto-create category if not exists
- Chunk processing for better performance (100 rows per batch)
- Error handling with detailed message: "Baris {row}: {error_message}"
- BulkImportProducts page in Filament under Manajemen Produk

#### Database Changes
- Added sku field to products table (unique, nullable)
- Made category_id nullable in products table

#### Command
- `php artisan app:generate-product-import-template` - Generate template file

### Changed
- Product model: added sku to fillable array

### Tests
- BulkImportProductTest with 5 test cases

## [2.9.0] - 2026-03-03

### Added

#### Product Variants System
- ProductVariant model for managing product variations (size, color, flavor, etc.)
- Variant attributes stored as JSON for flexibility
- Separate pricing per variant (base price + variant adjustment)
- Separate stock tracking per variant
- SKU generation with variant suffixes (e.g., TSHIRT-001-RED-L)
- Variant selection modal in POS with stock availability
- Transaction items track variant_id for detailed reporting
- VariantService for variant operations and calculations

#### Product Bundles System
- ProductBundle model for creating product packages
- BundleItem model for items within a bundle
- Special bundle pricing (total lower than individual items)
- Auto-apply bundle when all items present in cart
- Bundle availability notification in POS
- Bundle summary widget on dashboard
- BundleService for bundle operations and validation

#### Reorder Point Auto-Alert System
- ReorderAlert model for low stock notifications
- Per-product reorder point configuration (min/max stock)
- Automatic alert creation when stock below reorder point
- Alert severity levels: low, medium, high, critical
- LowStockAlertWidget on dashboard with color-coded badges
- ManageReorderAlerts page for viewing and dismissing alerts
- Alert filtering by severity and product
- ReorderPointService for alert management

#### Dashboard Widgets Enhancement
- LowStockAlertWidget - Real-time low stock notifications
- BundleSummaryWidget - Active bundles overview
- ProductStatsWidget - Product and variant statistics
- Fixed widget permissions using existing Shield permissions
- Optimized widget layout with proper columnSpan

### Database Tables
- `product_variants` - Product variation data with stock and pricing
- `product_bundles` - Bundle headers with special pricing
- `bundle_items` - Items included in bundles
- `reorder_alerts` - Low stock alert records

### Models
- `ProductVariant` - Product variant with attributes, stock, pricing
- `ProductBundle` - Bundle definition with items relationship
- `BundleItem` - Bundle line items with quantity
- `ReorderAlert` - Alert records with severity levels

### Filament Resources
- ProductVariantResource - Variant management with product linkage
- ProductBundleResource - Bundle creation and management
- Updated ProductResource - Added variant repeater for inline variant management

### Filament Pages
- ManageReorderAlerts - Alert management page with filtering

### Services
- `VariantService` - Variant operations, validation, stock management
- `BundleService` - Bundle validation, price calculation, auto-apply logic
- `ReorderPointService` - Alert creation, management, and auto-check

### POS Integration
- Variant selection modal when adding products with variants
- Bundle auto-apply with visual notification
- Variant stock display in product grid
- Bundle pricing calculation in cart

### Tests
- VariantServiceTest (9 test cases) - Variant operations and validation
- BundleServiceTest (12 test cases) - Bundle validation and calculations
- ReorderPointServiceTest (6 test cases) - Alert logic and severity
- ProductVariantTest (6 test cases) - Feature tests for variant CRUD
- ProductBundleTest (4 test cases) - Feature tests for bundle CRUD
- ReorderAlertTest (3 test cases) - Feature tests for alert management
- PosVariantBundleTest (7 test cases) - POS integration tests

### Factories & Seeders
- ProductVariantFactory - Variant test data generation
- ProductBundleFactory - Bundle test data generation
- BundleItemFactory - Bundle item test data generation
- ReorderAlertFactory - Alert test data generation
- VariantBundleDemoSeeder - Demo data for variants and bundles

### Changed
- TransactionItem model: added variant_id column
- Product model: added variants(), bundles(), reorderAlerts() relationships
- Product model: added reorder_point, reorder_quantity, track_stock fields
- POS component: integrated variant selection and bundle auto-apply
- DatabaseSeeder: includes VariantBundleDemoSeeder

## [2.8.0] - 2026-03-01

### Added

#### Expense Tracking System
- Expense model with expense number generation (EXP-YYYYMMDD-XXXX format)
- ExpenseCategory model for categorizing expenses
- Expense recording with amount, description, date, and attachment
- Link expenses to shifts for shift-based expense tracking
- Expense reporting by category and date range
- ExpenseService for expense operations

#### Shift Management System
- Shift model for cashier shift management
- Shift types: morning (pagi) and evening (sore)
- Opening cash tracking when starting shift
- Closing cash tracking with expected cash calculation
- Cash difference calculation (surplus/shortage)
- Shift status tracking (open/closed)
- Link transactions to shifts
- Link expenses to shifts
- Shift summary report (sales, transactions, expenses)
- ShiftService for shift operations
- Open/close shift validation (prevent multiple open shifts)

#### Staff Performance Report
- StaffPerformanceService for performance metrics
- Sales performance by user (total sales, transaction count)
- Average transaction value per staff
- Items sold count per staff
- Top staff ranking with detailed metrics
- Date range filtering for performance reports
- StaffPerformanceReport page in Filament

### Database Tables
- `expense_categories` - Expense category definitions
- `expenses` - Expense records with shift linkage
- `shifts` - Shift management (opening/closing cash, status)

### Filament Resources
- ExpenseResource - Expense management with category filtering
- ExpenseCategoryResource - Expense category management
- ShiftResource - Shift management with open/close actions

### Filament Pages
- StaffPerformanceReport - Staff performance analytics page

### Services
- `ExpenseService` - Expense tracking and reporting
- `ShiftService` - Shift management and cash tracking
- `StaffPerformanceService` - Staff performance metrics

### Widgets
- ExpenseSummaryWidget - Daily expense summary on dashboard
- TopStaffWidget - Top performing staff display
- CurrentShiftWidget - Current shift status indicator

### Tests
- ExpenseTest with 13 test cases covering:
  - Expense creation and relationships
  - Expense service methods
  - Category filtering
- ShiftTest with 19 test cases covering:
  - Shift creation and management
  - Open/close shift operations
  - Shift service methods
  - Scope filtering

## [2.7.1] - 2026-03-01

### Fixed

#### Null Safety Improvements
- Add null-safe operators (`?->`) to tax-related property accessors in POS component
- Provide sensible defaults for tax rate (10.00) and tax name ("PPN") when store is unavailable
- Fix tax visibility check in transaction table to handle null records gracefully
- Ensure consistent null handling across transaction and POS components to prevent runtime errors

#### UI/UX Improvements
- Update navigation sort order for ProcessReturn (3) and EndOfDayReport (2) pages
- Remove redundant `navigationGroup` from EndOfDayReport for cleaner navigation structure
- Improve responsive filter layout with breakpoint-specific column spans (2 columns on mobile, 4 on desktop)
- Refactor debt report view with condensed button markup for better readability

#### Layout & Navigation
- Fix button positioning and spacing in reports
- Optimize navigation hierarchy for better user flow
- Improve responsive design across report pages

## [2.7.0] - 2026-02-27

### Added

#### Backup & Restore System
- Spatie Laravel Backup package integration (v9.4)
- Automatic daily database backup at 02:00 AM
- Automatic backup cleanup at 03:00 AM
- Manual backup creation via Filament admin panel (System → Backup & Restore)
- Full backup option (database + application files)
- Restore database from backup with confirmation dialog
- Download backup files
- Delete backup files (single or bulk)
- Backup management UI with file listing (name, size, date, age)
- Backup retention policy (7 days default)
- Backup storage in `storage/app/backups/Laravel/`

#### Console Commands
- `backup:run` - Create backup (with --only-db option for database only)
- `backup:restore {file}` - Restore database from backup file
- `backup:clean` - Clean old backups based on retention policy

#### Configuration
- `config/backup.php` - Spatie backup configuration
- `config/filesystems.php` - Added 'backups' disk
- `routes/console.php` - Scheduled backup tasks (daily at 02:00 and 03:00)

### Filament Pages
- Backups - Backup management page under System menu with:
  - List all backups with details
  - Download backup files
  - Restore database from backup
  - Delete backups
  - Create manual backups
  - Cleanup old backups

### Storage
- Backup files stored in `storage/app/backups/Laravel/`
- Zip format containing database dump and optional files
- No database tables required (filesystem-based)

### Dependencies
- `spatie/laravel-backup` - Backup and restore functionality

## [2.6.0] - 2026-02-26

### Added

#### Tax (PPN) System
- Tax configuration in Store resource (enable/disable, rate, name)
- TaxService for tax calculations
- Tax fields on Store model (tax_enabled, tax_rate, tax_name)
- Tax fields on Transaction model (tax_amount, tax_rate, tax_enabled, subtotal_before_tax)
- Tax calculation in POS checkout flow
- Display tax amount column in Transaction resource
- Change calculation with tax consideration
- Database migration for tax columns
- Unit tests for TaxService (11 test cases)

### Database Tables
- Added tax fields to `stores` table
- Added tax fields to `transactions` table

## [2.5.0] - 2026-02-23

### Added

#### Return/Refund System
- ProductReturn model with return number generation (RTN-YYYYMMDD-XXXX)
- ProductReturnItem model for return line items
- Return types: Full Return, Partial Return, Exchange
- Return reasons: Damaged, Wrong Item, Not As Expected, Other
- Refund methods: Cash, Store Credit, Original Payment
- Return deadline validation (configurable per store, default 7 days)
- Product returnable flag (is_returnable on products)
- quantity_returned tracking on TransactionItem

#### Store Credit System
- StoreCredit model for customer credit balance
- Store credit earning from returns
- Store credit usage for transactions
- Credit expiry tracking (configurable days or never expires)
- Auto-expire scheduled task (runs daily)
- Customer store_credit_balance field

#### Points Handling on Returns
- Automatic points reversal for earned points
- Automatic points return for redeemed points
- Proportional calculation based on returned quantity

#### Return Receipt
- Return receipt template in seeder
- Return receipt printing via Bluetooth
- Return receipt preview functionality

#### Return Report
- ReturnReport page with date filtering
- Summary statistics (total returns, refunds, exchanges)
- Detailed return listing

#### API Endpoints
- `GET /api/returns/{id}` - Get return data for printing
- `GET /api/returns/{id}/receipt` - Get return receipt preview

### Changed
- Transaction model: added returns() relationship
- TransactionItem model: added quantity_returned, returnItems() relationship
- Product model: added is_returnable field, isReturnable() method
- Customer model: added store_credit_balance, storeCredits(), returns() relationships
- Customer model: added reversePoints(), returnPoints(), addStoreCredit(), useStoreCredit() methods
- FinancialRecord model: added product_return_id field
- Store model: added return_deadline_days, enable_store_credit, store_credit_expiry_days, store_credit_never_expires fields
- StockMovementType enum: added Return case
- ShieldSeeder: added ProductReturn and StoreCredit permissions for all roles

### Database Tables
- `product_returns` - Return transaction headers
- `product_return_items` - Return transaction line items
- `store_credits` - Customer credit balance tracking

### Filament Resources
- ProductReturnResource - Return viewing with print functionality
- StoreCreditResource - Store credit viewing with status tracking

### Filament Pages
- ProcessReturn - Return processing page
- ReturnReport - Return report page

### Services
- `ReturnService` - Return/refund business logic
- `StoreCreditService` - Store credit management

### Console Commands
- `ExpireStoreCredits` - Daily task to expire credits

### Tests
- ReturnServiceTest with 40 test cases covering:
  - generateReturnNumber (4 tests)
  - validateReturnEligibility (5 tests)
  - calculateRefund (3 tests)
  - createReturn (5 tests)
  - Points handling (4 tests)
  - Refund processing (2 tests)
  - StoreCreditService (17 tests)

## [2.4.0] - 2026-02-19

### Added
- Supplier debt relationship on PurchaseOrder model
- debt() method to establish one-to-one relationship with SupplierDebt model

### Changed
- PurchaseOrder model: import HasOne relation, add debt() method

## [2.3.2] - 2026-02-19

### Fixed
- Correct permission check for bulk delete action in PurchaseOrderResource

## [2.3.1] - 2026-02-19

### Fixed
- Add missing $this context to discount calculation methods in POS component

## [2.3.0] - 2026-02-18

### Added

#### Hold/Suspend Transaction
- SuspendedTransaction model with unique suspension key
- Max 5 suspended transactions per cashier
- Resume suspended transaction with cart restoration
- Preserve customer, voucher, and discount data on suspend

#### Multi Payment
- TransactionPayment model for multiple payment methods
- Split payment across cash, transfer, and QRIS
- Payment reference tracking for transfers
- Total paid calculation from all payments

#### Split Bill
- SplitBill model for dividing transactions
- Multiple payers with different payment methods
- Automatic amount distribution
- Track each split with subtotal and payment details

#### Barcode Scanner
- Auto-focus barcode input field
- Scan and add product to cart
- Product lookup by barcode
- Error handling for invalid barcodes

### Changed
- Transaction model: added is_split, total_splits columns
- User model: added suspendedTransactions relationship
- POS component: integrated hold, multi-payment, split bill, and barcode scanning

### Database Tables
- `suspended_transactions` - Pending transaction storage
- `transaction_payments` - Multiple payment records
- `split_bills` - Split bill records

### Models
- `SuspendedTransaction` - Hold/suspend functionality
- `TransactionPayment` - Multi-payment support
- `SplitBill` - Split bill support

### Tests
- PosEnhancementTest with 26 test cases

## [2.2.0] - 2026-02-17

### Added

#### Discount System
- Discount model with multiple types (percentage/fixed)
- Product discounts (per-product discount)
- Category discounts (all products in category)
- Global discounts (site-wide promotions)
- Voucher/coupon codes (manual redemption)
- Stackable discounts (multiple discounts per transaction)
- DiscountResource with CRUD operations in Filament
- DiscountFactory for testing

#### Discount Features
- DiscountService for discount calculations
- Automatic product discount application in POS
- Voucher code input and validation
- Discount breakdown display in checkout
- Minimum purchase requirement
- Maximum discount limit (for percentage)
- Usage limit per discount
- Date range validity

### Changed
- Transaction model: added discount_id, subtotal_before_discount, discount_amount, voucher_code columns
- TransactionItem model: added original_price, discount_amount, discount_id columns
- POS component: integrated discount calculations and voucher UI
- Product model: added discounts relationship
- Category model: added discounts relationship
- ShieldSeeder: added Discount permissions for all roles

### Database Tables
- `discounts` - Discount configurations
- `discount_product` - Product-discount pivot table
- `discount_category` - Category-discount pivot table

### Filament Resources
- DiscountResource - Discount management

### Services
- `DiscountService` - Discount calculations and validation

### Tests
- DiscountTest with 18 test cases

## [2.1.0] - 2026-02-16

### Added

#### Customer Management
- Customer model with profile data (name, phone, email, address)
- Customer points and statistics tracking
- CustomerResource with CRUD operations in Filament
- Points history tracking per customer
- Transaction history per customer
- CustomerFactory for testing

#### Loyalty Points System
- PointService for point calculations
- Earn points: Rp 10.000 = 1 point
- Redeem points: 1 point = Rp 1.000
- Minimum redeem: 10 points
- Maximum redeem: 50% of transaction
- Point redemption in POS checkout
- Automatic point earning after transaction

#### POS Enhancements
- Customer selection during checkout
- Customer search by name/phone
- Point redemption option with validation
- Display available points
- Display points to be earned

### Changed
- Transaction model: added customer_id, points_earned, points_redeemed, discount_from_points columns
- POS component: integrated customer selection and points functionality
- TransactionResource: added customer column and filter
- ShieldSeeder: added Customer permissions for all roles
- DatabaseSeeder: added 5 sample customers

### Database Tables
- `customers` - Customer profiles with points and stats
- `customer_points` - Points transaction history (earn/redeem/adjust)

### Filament Resources
- CustomerResource - Customer management with view pages

### Services
- `PointService` - Loyalty point calculations and management

### Tests
- CustomerTest with 21 test cases covering CRUD, points, and validations

## [2.0.0] - 2026-02-14

### Added

#### Core Setup
- Fresh Laravel application setup with PHP 8.2
- Pest testing framework integration
- Filament v3 admin panel integration

#### Product Management
- Category model with CRUD operations
- Product model with stock tracking
- Low stock threshold alerts
- Product barcode support
- Product activation/deactivation

#### Point of Sale (POS) System
- Livewire-based POS interface
- Shopping cart functionality
- Real-time stock validation
- Multiple payment method support (cash, qris, transfer)
- Transaction processing with automatic stock deduction
- Profit calculation per transaction item

#### Receipt Printing
- Bluetooth thermal printer integration
- Customizable receipt templates
- ESC/POS encoding support
- Receipt preview functionality
- Per-store template configuration

#### Inventory Management
- Supplier management system
- Purchase Order (PO) workflow
- Purchase Order Item tracking
- Supplier debt management
- Debt payment tracking
- Stock adjustment with approval workflow
- Stock Opname (Stock Take) functionality
- Stock history tracking

#### Financial Records
- Automatic financial record creation on checkout
- Profit tracking per transaction
- Financial dashboard charts

#### Reporting System
- Sales Report with date filtering
- Purchase Report with supplier filtering
- Profit & Loss Report
- Stock Card Report
- Debt Report
- End of Day (Cashier Closing) Report
- PDF export for all reports
- Excel export for all reports

#### Dashboard Widgets
- Stats Overview (Categories, Products, Stock)
- Sales Chart Widget
- Profit Chart Widget
- Transactions Chart Widget
- Financial Records Chart
- Top Products Widget
- Payment Method Chart Widget
- Low Stock Alert Widget

#### Authentication & Authorization
- Filament Shield integration for role-based access control
- Spatie Laravel Permission integration
- Pre-defined roles:
  - `super_admin` - Full system access
  - `admin` - Administrative access
  - `manager` - Operational management access
  - `kasir` - Cashier access (POS + transactions)
  - `panel_user` - Basic panel access
- Permission-based access for all Resources, Pages, and Widgets
- User management with role assignment
- Policies for all models

### Technical Details

#### Database Tables
- `users` - User accounts
- `categories` - Product categories
- `products` - Product inventory
- `transactions` - Sales transactions
- `transaction_items` - Transaction line items
- `financial_records` - Financial tracking
- `stores` - Store configuration
- `receipt_templates` - Receipt template storage
- `suppliers` - Supplier information
- `purchase_orders` - Purchase order headers
- `purchase_order_items` - Purchase order line items
- `supplier_debts` - Supplier debt tracking
- `debt_payments` - Debt payment records
- `stock_adjustments` - Stock adjustment headers
- `stock_adjustment_items` - Stock adjustment items
- `stock_opnames` - Stock take headers
- `stock_opname_items` - Stock take items
- `stock_histories` - Stock movement history
- `end_of_days` - Cashier closing records
- `roles` - User roles (Shield)
- `permissions` - User permissions (Shield)
- `role_has_permissions` - Role-permission mapping
- `model_has_roles` - Model-role mapping
- `model_has_permissions` - Model-permission mapping

#### Filament Resources
- UserResource - User management with role assignment
- CategoryResource - Category CRUD
- ProductResource - Product management
- TransactionResource - Transaction viewing
- SupplierResource - Supplier management
- PurchaseOrderResource - Purchase order management
- SupplierDebtResource - Supplier debt tracking
- DebtPaymentResource - Debt payment records
- StockAdjustmentResource - Stock adjustment management
- StockOpnameResource - Stock take management
- FinancialRecordResource - Financial records viewing
- ReceiptTemplateResource - Receipt template management
- StoreResource - Store configuration
- RoleResource (Shield) - Role and permission management

#### Filament Pages
- Dashboard
- POS (Point of Sale)
- Sales Report
- Purchase Report
- Profit & Loss Report
- Stock Card Report
- Debt Report
- End of Day Report

#### Services
- `ReportService` - Report data generation
- `StockService` - Stock operations
- `ReceiptTemplateService` - Template management
- `ReceiptRenderer` - ESC/POS receipt rendering

#### Tests
- Comprehensive test suite using Pest
- Feature tests for all major functionality
- Shield permission tests (11 test cases)

### Changed
- Indonesian localization for Filament Shield
- Custom navigation labels ("Manajemen Role", "Role")

### Dependencies
- Laravel Framework v12
- Filament v3.3
- Livewire v3
- Spatie Laravel Permission v6
- Filament Shield v3
- Maatwebsite Excel
- Barryvdh DomPDF
- Flowframe Laravel Trend
- Blade UI Kit Heroicons

---

## Release Summary

| Version | Date | Description |
|---------|------|-------------|
| 2.10.0 | 2026-03-04 | Bulk import products from Excel with drag & drop and validation |
| 2.9.0 | 2026-03-03 | Product variants, product bundles, and reorder point alert system |
| 2.8.0 | 2026-03-01 | Expense tracking, shift management, and staff performance reports |
| 2.7.1 | 2026-03-01 | Bug fixes: null safety improvements, UI refinements, navigation fixes |
| 2.7.0 | 2026-02-27 | Backup & restore system with automatic daily backups |
| 2.6.0 | 2026-02-26 | Tax (PPN) system for stores and transactions |
| 2.5.0 | 2026-02-23 | Return/refund system with store credit |
| 2.4.0 | 2026-02-19 | Supplier debt relationship on PurchaseOrder |
| 2.3.2 | 2026-02-19 | Fix permission check for bulk delete action |
| 2.3.1 | 2026-02-19 | Fix discount calculation methods in POS |
| 2.3.0 | 2026-02-18 | Hold/suspend transaction, multi payment, split bill, barcode scanner |
| 2.2.0 | 2026-02-17 | Discount system with product, category, global, and voucher discounts |
| 2.1.0 | 2026-02-16 | Customer management with loyalty points system |
| 2.0.0 | 2026-02-14 | Initial release with complete POS system |

### Feature Breakdown

```
├── Core System
│   ├── Product Management
│   ├── Category Management
│   └── Store Configuration
├── Point of Sale
│   ├── Cart System
│   ├── Transaction Processing
│   ├── Receipt Printing
│   ├── Customer Selection & Points
│   ├── Discount & Voucher
│   ├── Hold/Suspend Transaction
│   ├── Multi Payment
│   ├── Split Bill
│   └── Barcode Scanner
├── Inventory
│   ├── Supplier Management
│   ├── Purchase Orders
│   ├── Supplier Debts
│   ├── Stock Adjustments
│   └── Stock Opname
├── Customer
│   ├── Customer Database
│   ├── Loyalty Points
│   └── Purchase History
├── Discount
│   ├── Product Discounts
│   ├── Category Discounts
│   ├── Global Discounts
│   └── Voucher/Coupons
├── Return
│   ├── Full Return
│   ├── Partial Return
│   ├── Exchange
│   ├── Store Credit
│   └── Return Report
├── Tax (PPN)
│   ├── Tax Configuration
│   ├── Tax Calculation
│   └── Tax Display
├── Reports
│   ├── Sales Report
│   ├── Purchase Report
│   ├── Profit & Loss
│   ├── Stock Card
│   ├── Debt Report
│   └── End of Day
├── Dashboard
│   ├── Stats Overview
│   ├── Sales Chart
│   ├── Profit Chart
│   └── Alert Widgets
└── Access Control
    ├── Role Management
    ├── Permission Management
    └── User Management
├── Backup & Restore
│   ├── Automatic Daily Backup
│   ├── Manual Backup Creation
│   ├── Restore from Backup
│   ├── Download Backup Files
│   └── Backup Cleanup
├── Expense Tracking
│   ├── Expense Categories
│   ├── Expense Recording
│   ├── Shift-linked Expenses
│   └── Expense Reporting
├── Shift Management
│   ├── Morning/Evening Shifts
│   ├── Opening Cash Tracking
│   ├── Closing Cash Tracking
│   ├── Cash Difference Calculation
│   └── Shift Summary Reports
├── Staff Performance
│   ├── Sales by Staff
│   ├── Transaction Count
│   ├── Average Transaction Value
│   └── Top Staff Rankings
├── Product Variants
│   ├── Variant Management
│   ├── Separate Variant Pricing
│   ├── Per-Variant Stock Tracking
│   └── POS Variant Selection
├── Product Bundles
│   ├── Bundle Creation
│   ├── Bundle Item Management
│   ├── Special Bundle Pricing
│   └── Auto-Apply in POS
├── Reorder Alerts
│   ├── Reorder Point Configuration
│   ├── Auto-Alert Generation
│   ├── Severity Levels
│   └── Dashboard Low Stock Widget
```
