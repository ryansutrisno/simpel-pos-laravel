# Work Plan: App Settings, Printer Config & Payment Gateway

## TL;DR

> **Quick Summary**: Membuat sistem pengaturan aplikasi global (multi-tenant) dengan App Settings, Printer Configuration, dan Payment Gateway (Mayar + Midtrans)
>
> **Deliverables**:
> - Model & migration `AppSettings` untuk global settings
> - Model & migration `PrinterConfig` untuk konfigurasi printer per toko
> - Extend `PaymentGatewayConfig` untuk support Midtrans
> - Filament Resource `AppSettingsResource` (global panel admin)
> - Filament Resource `PrinterConfigResource` (per store)
> - Update UI Payment Gateway Settings
> - Service classes untuk masing-masing fitur
> - Tests untuk semua komponen
>
> **Estimated Effort**: Large
> **Parallel Execution**: YES - 4 waves
> **Critical Path**: Task 1-4 (Database) → Task 5-7 (Models) → Task 8-10 (Resources) → Task F1-F4 (Verification)

---

## Context

### Original Request
Membuat pengaturan aplikasi/web yang terpisah dari pengaturan toko, dengan support:
1. App Settings (global): brand, timezone, email, dll
2. Printer Settings (per toko): model printer, connection type
3. Payment Gateway (per toko): Mayar + Midtrans

### Interview Summary
**Key Discussions**:
- Multi-tenant: Global settings untuk semua toko + per-store settings
- Printer: Simpan konfigurasi di database (model, connection type)
- Payment: Mayar (existing) + Midtrans (new)
- Prioritas: App Settings → Printer → Payment Gateway

**Research Findings**:
- Project: Laravel 12 + Filament 3 + Livewire 3
- Sudah ada `Store` model dengan pengaturan per toko
- Sudah ada `PaymentGatewayConfig` model untuk Mayar
- Belum ada tabel untuk App Settings global
- Belum ada tabel untuk Printer Config

### Metis Review
**Identified Gaps** (addressed):
- Perlu singleton pattern untuk AppSettings (hanya 1 row global)
- Printer config perlu support multiple printer per toko
- Payment gateway perlu enum untuk provider types

---

## Work Objectives

### Core Objective
Membuat sistem pengaturan terpisah: App Settings (global) + Printer Config (per store) + Payment Gateway (per store dengan multi-provider)

### Concrete Deliverables
- Database: migrations untuk `app_settings` dan `printer_configs`
- Models: `AppSettings`, `PrinterConfig`, extend `PaymentGatewayConfig`
- Services: `AppSettingsService`, `PrinterConfigService`, `PaymentGatewayService`
- Filament Resources: CRUD untuk AppSettings, PrinterConfig, PaymentGatewayConfig
- UI: Form settings yang user-friendly

### Definition of Done
- [ ] Semua migration berjalan tanpa error
- [ ] Semua model memiliki casts dan relationships yang benar
- [ ] Filament resources bisa CRUD dengan validasi
- [ ] Service classes punya methods yang reusable
- [ ] Semua tests pass

### Must Have
- App Settings global (singleton) dengan fields: app_name, timezone, date_format, currency, currency_format, email_from, email_from_name, maintenance_mode
- Printer Config per store dengan fields: store_id, name, model, connection_type, address/identifier, is_default, is_active
- Payment Gateway support Mayar & Midtrans dengan fields yang lengkap

### Must NOT Have (Guardrails)
- Tidak mengubah struktur tabel `stores` yang sudah ada
- Tidak menghapus data payment gateway Mayar yang sudah ada
- Tidak membuat breaking changes pada API yang sudah ada
- Tidak membuat UI yang kompleks/berlebihan, tetap simple dan functional

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES (Pest 3)
- **Automated tests**: Tests-after (setelah implementasi)
- **Framework**: Pest 3

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

- **Backend/Database**: Bash (php artisan migrate, tinker)
- **Filament UI**: Playwright — Navigate, interact, assert DOM, screenshot
- **API/Service**: Bash (curl/tinker) — Test methods, assert responses

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Database Foundation - Start Immediately):
├── Task 1: Migration AppSettings (singleton table)
├── Task 2: Migration PrinterConfig
├── Task 3: Migration extend PaymentGatewayConfig (add provider enum)
└── Task 4: Migration add soft deletes & indexes

Wave 2 (Models & Relationships):
├── Task 5: Model AppSettings dengan singleton pattern
├── Task 6: Model PrinterConfig dengan relationships
└── Task 7: Extend PaymentGatewayConfig enum provider

Wave 3 (Services & Business Logic):
├── Task 8: AppSettingsService (get, set, cache)
├── Task 9: PrinterConfigService (CRUD, test connection)
└── Task 10: PaymentGatewayService (initiate, status, webhook)

Wave 4 (Filament Resources):
├── Task 11: AppSettingsResource (global panel, singleton form)
├── Task 12: PrinterConfigResource (store relation, table + form)
├── Task 13: PaymentGatewayConfigResource (update untuk Midtrans)
└── Task 14: Widget Settings Overview di dashboard

Wave 5 (Integration & Polish):
├── Task 15: Update POS untuk gunakan AppSettings
├── Task 16: Update Receipt untuk gunakan PrinterConfig
├── Task 17: Update Transaction untuk gunakan PaymentGateway
└── Task 18: Seeder default data

Wave FINAL (Verification - 4 parallel agents):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)

Critical Path: Task 1-4 → Task 5-7 → Task 8-10 → Task 11-14 → Task 15-17 → F1-F4
Parallel Speedup: ~60% faster than sequential
Max Concurrent: 4 (Wave 1 & 4)
```

### Dependency Matrix
- **1-4**: — — 5-7, F1
- **5-7**: 1-4 — 8-10, 2
- **8-10**: 5-7 — 11-14, 3
- **11-14**: 8-10 — 15-18, 4
- **15-18**: 11-14 — F1-F4

### Agent Dispatch Summary
- **Wave 1**: **4** — All → `quick`
- **Wave 2**: **3** — All → `quick`
- **Wave 3**: **3** — All → `unspecified-high`
- **Wave 4**: **4** — All → `visual-engineering` (Filament)
- **Wave 5**: **4** — All → `unspecified-high`
- **FINAL**: **4** — F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep`

---

## TODOs

- [x] 1. Migration AppSettings (singleton table)

  **What to do**:
  - Buat migration `create_app_settings_table`
  - Fields: id, app_name (string), timezone (string, default 'Asia/Jakarta'), date_format (string, default 'd/m/Y'), time_format (string, default 'H:i'), currency (string, default 'IDR'), currency_format (string, default 'id_ID'), email_from (string), email_from_name (string), maintenance_mode (boolean, default false), created_at, updated_at
  - Unique constraint: hanya boleh ada 1 row (singleton pattern)
  - Indexes: maintenance_mode untuk filtering cepat

  **Must NOT do**:
  - Tidak pakai soft deletes (singleton tidak perlu)
  - Tidak buat foreign key ke stores (ini global settings)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`
    - Migration patterns dan best practices
  - **Skills Evaluated but Omitted**: `filament` (belum perlu untuk migration)

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (dengan Task 2, 3, 4)
  - **Blocks**: Task 5
  - **Blocked By**: None

  **References**:
  - `database/migrations/` - Lihat pattern migration existing
  - `config/app.php` - Timezone default Laravel
  - `app/Models/Store.php` - Contoh model dengan casts

  **Acceptance Criteria**:
  - [ ] Migration file tercreate dengan nama timestamp
  - [ ] `php artisan migrate` berhasil tanpa error
  - [ ] Tabel `app_settings` ada di database dengan struktur yang benar

  **QA Scenarios**:
  ```
  Scenario: Migration berhasil dijalankan
    Tool: Bash
    Steps:
      1. cd /Users/ryansutrisno/Sites/simpel-pos && php artisan migrate:fresh --path=database/migrations/xxxx_xx_xx_create_app_settings_table.php
      2. sqlite3 database/database.sqlite ".schema app_settings"
    Expected Result: Tabel app_settings muncul dengan semua columns yang benar
    Evidence: .sisyphus/evidence/task-1-migration-app-settings.txt
  ```

  **Commit**: YES
  - Message: `feat(settings): add app_settings migration`
  - Files: `database/migrations/xxxx_xx_xx_create_app_settings_table.php`

- [x] 2. Migration PrinterConfig

  **What to do**:
  - Buat migration `create_printer_configs_table`
  - Fields: id, store_id (foreign key ke stores), name (string), model (string, contoh: 'Xprinter XP-58', 'Epson TM-T82'), connection_type (enum: 'usb', 'bluetooth', 'network'), address (string - bisa USB port, MAC address, atau IP), port (integer, nullable - untuk network), is_default (boolean, default false), is_active (boolean, default true), settings (json, nullable - untuk config tambahan), created_at, updated_at, deleted_at (soft delete)
  - Foreign key: store_id cascade on delete
  - Indexes: store_id, is_active, is_default
  - Unique: store_id + name (tiap toko tidak boleh ada nama printer sama)

  **Must NOT do**:
  - Tidak validasi format address (tergantung connection type)
  - Tidak test koneksi di migration (itu di service layer)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`, `postgresql`
    - Migration patterns dan indexing
  - **Skills Evaluated but Omitted**: `filament`

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (dengan Task 1, 3, 4)
  - **Blocks**: Task 6
  - **Blocked By**: None (asumsi tabel stores sudah ada)

  **References**:
  - `database/migrations/xxxx_xx_xx_create_stores_table.php` - Contoh foreign key pattern
  - `app/Models/Store.php` - Contoh casts untuk boolean

  **Acceptance Criteria**:
  - [ ] Migration file tercreate
  - [ ] `php artisan migrate` berhasil
  - [ ] Tabel `printer_configs` ada dengan foreign key yang benar

  **QA Scenarios**:
  ```
  Scenario: Migration printer_configs berhasil
    Tool: Bash
    Steps:
      1. php artisan migrate:fresh
      2. sqlite3 database/database.sqlite ".schema printer_configs"
    Expected Result: Tabel printer_configs dengan foreign key ke stores
    Evidence: .sisyphus/evidence/task-2-migration-printer-configs.txt
  ```

  **Commit**: GROUP dengan Task 1

- [x] 3. Migration extend PaymentGatewayConfig (provider enum)

  **What to do**:
  - Buat migration `update_payment_gateway_configs_add_provider_enum`
  - Ubah column `provider` dari string menjadi enum: ['mayar', 'midtrans']
  - Tambah column `provider_config` (json, nullable) untuk simpan config spesifik provider (beda-beda struktur Mayar vs Midtrans)
  - Existing data: update provider 'mayar' jadi lowercase jika perlu
  - Index: store_id, provider, is_active

  **Must NOT do**:
  - Tidak hapus data existing
  - Tidak ubah struktur column yang sudah ada (api_key, config, dll)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`
  - **Skills Evaluated but Omitted**: None

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 7
  - **Blocked By**: None (asumsi tabel sudah ada)

  **References**:
  - `app/Models/PaymentGatewayConfig.php` - Lihat fields yang sudah ada
  - `database/migrations/` - Contoh migration alter table

  **Acceptance Criteria**:
  - [ ] Migration alter table berhasil
  - [ ] Data existing tidak hilang
  - [ ] Enum provider bisa 'mayar' atau 'midtrans'

  **QA Scenarios**:
  ```
  Scenario: Migration alter payment gateway berhasil
    Tool: Bash
    Steps:
      1. php artisan migrate:fresh --seed
      2. php artisan tinker --execute="echo App\Models\PaymentGatewayConfig::first()->provider;"
    Expected Result: Tidak error, provider tetap terbaca
    Evidence: .sisyphus/evidence/task-3-migration-payment-gateway.txt
  ```

  **Commit**: GROUP dengan Task 1-2

- [x] 4. Migration add soft deletes & indexes

  **What to do**:
  - Buat migration `add_soft_deletes_to_printer_configs` jika belum ada di Task 2
  - Pastikan semua tabel punya index yang diperlukan untuk performance
  - Update migration AppSettings: tambah index untuk maintenance_mode (jika belum)

  **Must NOT do**:
  - Tidak perlu soft deletes untuk AppSettings (singleton)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Wave 2
  - **Blocked By**: Task 1-3

  **Acceptance Criteria**:
  - [ ] Semua tabel migration berhasil
  - [ ] Indexes tercreate

  **QA Scenarios**:
  ```
  Scenario: Semua migration berjalan tanpa error
    Tool: Bash
    Steps:
      1. php artisan migrate:fresh
      2. php artisan migrate:status
    Expected Result: Semua migration status "Ran"
    Evidence: .sisyphus/evidence/task-4-migration-complete.txt
  ```

  **Commit**: GROUP dengan Wave 1

- [x] 5. Model AppSettings dengan singleton pattern

  **What to do**:
  - Buat model `AppSettings` di `app/Models/AppSettings.php`
  - Implementasi singleton pattern: hanya boleh ada 1 row di tabel
  - Methods: `getInstance()` (return AppSettings singleton), `get($key, $default)`, `set($key, $value)`, `all()`
  - Cast: maintenance_mode (boolean), timezone (string), dll
  - Auto-create instance jika belum ada (di method getInstance)

  **Must NOT do**:
  - Tidak pakai fillable yang kebuka semua, gunakan guarded atau validasi
  - Tidak allow create lebih dari 1 row

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`
  - **Skills Evaluated but Omitted**: None

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 1)
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 8
  - **Blocked By**: Task 1

  **References**:
  - `app/Models/Store.php` - Contoh model dengan casts()
  - `app/Models/PaymentGatewayConfig.php` - Contoh accessor/mutator

  **Acceptance Criteria**:
  - [ ] Model AppSettings bisa `AppSettings::getInstance()` return 1 instance
  - [ ] Method `get()` dan `set()` berfungsi
  - [ ] Auto-create instance jika belum ada

  **QA Scenarios**:
  ```
  Scenario: Singleton pattern berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = App\Models\AppSettings::getInstance(); \$s->set('app_name', 'Test'); echo \$s->get('app_name');"
    Expected Result: Output "Test"
    Evidence: .sisyphus/evidence/task-5-model-appsettings.txt
  
  Scenario: Tidak bisa buat lebih dari 1 instance
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="App\Models\AppSettings::create(['app_name' => 'Test2']); echo 'Created';"
    Expected Result: Error atau di-handle (tidak ada row baru)
    Evidence: .sisyphus/evidence/task-5-model-singleton-constraint.txt
  ```

  **Commit**: YES
  - Message: `feat(settings): add AppSettings model with singleton pattern`
  - Files: `app/Models/AppSettings.php`

- [x] 6. Model PrinterConfig dengan relationships

  **What to do**:
  - Buat model `PrinterConfig` di `app/Models/PrinterConfig.php`
  - Fillable: store_id, name, model, connection_type, address, port, is_default, is_active, settings
  - Casts: is_default (boolean), is_active (boolean), settings (array), connection_type (string/enum), port (integer)
  - Relationship: `store()` belongsTo Store
  - Scope: `active()`, `default()`, `forStore($storeId)`
  - Method: `isDefault()`

  **Must NOT do**:
  - Tidak validasi connection_type values di model (di Form Request/Filament)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 2)
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 9
  - **Blocked By**: Task 2

  **References**:
  - `app/Models/PaymentGatewayConfig.php` - Contoh relationship belongsTo
  - `app/Models/Store.php` - Contoh scope

  **Acceptance Criteria**:
  - [ ] Model PrinterConfig bisa CRUD
  - [ ] Relationship `store()` berfungsi
  - [ ] Scope `active()` dan `forStore()` berfungsi

  **QA Scenarios**:
  ```
  Scenario: CRUD PrinterConfig berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$p = App\Models\PrinterConfig::create(['store_id' => 1, 'name' => 'Printer 1', 'model' => 'XP-58', 'connection_type' => 'usb', 'address' => '/dev/usb/lp0']); echo \$p->id;"
    Expected Result: ID printer tercreate
    Evidence: .sisyphus/evidence/task-6-model-printerconfig.txt
  
  Scenario: Scope forStore berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="echo App\Models\PrinterConfig::forStore(1)->count();"
    Expected Result: Return count printer untuk store 1
    Evidence: .sisyphus/evidence/task-6-scope-forstore.txt
  ```

  **Commit**: GROUP dengan Task 5

- [x] 7. Extend PaymentGatewayConfig enum provider

  **What to do**:
  - Update model `PaymentGatewayConfig` di `app/Models/PaymentGatewayConfig.php`
  - Tambah enum atau const untuk provider: MAYAR, MIDTRANS
  - Method: `isMayar()`, `isMidtrans()`
  - Method: `getProviderConfig($key, $default)` untuk ambil config spesifik provider
  - Update casts: provider (string), provider_config (array)
  - Pastikan backward compatible dengan data existing

  **Must NOT do**:
  - Tidak hapus methods yang sudah ada
  - Tidak ubah struktur config existing

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 3)
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 10
  - **Blocked By**: Task 3

  **References**:
  - `app/Models/PaymentGatewayConfig.php` - File existing

  **Acceptance Criteria**:
  - [ ] Provider enum tersedia
  - [ ] Methods `isMayar()`, `isMidtrans()` berfungsi
  - [ ] Backward compatible dengan data existing

  **QA Scenarios**:
  ```
  Scenario: Provider methods berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$pg = App\Models\PaymentGatewayConfig::first(); echo \$pg->isMayar() ? 'Mayar' : 'Not Mayar';"
    Expected Result: Output sesuai provider
    Evidence: .sisyphus/evidence/task-7-extend-payment-gateway.txt
  ```

  **Commit**: GROUP dengan Wave 2

- [x] 8. AppSettingsService (get, set, cache)

  **What to do**:
  - Buat service `AppSettingsService` di `app/Services/AppSettingsService.php`
  - Methods:
    - `get($key, $default = null)` - ambil setting, pakai cache
    - `set($key, $value)` - set setting, clear cache
    - `all()` - ambil semua settings
    - `getAppName()` - helper untuk app_name
    - `getTimezone()` - helper untuk timezone
    - `isMaintenanceMode()` - helper untuk maintenance_mode
  - Cache key: `app_settings`
  - Cache duration: 1 jam (atau forever, clear on update)

  **Must NOT do**:
  - Tidak query database langsung tanpa cache (kecuali pertama kali)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `backend-dev-guidelines`
  - **Skills Evaluated but Omitted**: None

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 5)
  - **Parallel Group**: Wave 3
  - **Blocks**: Task 11, 15
  - **Blocked By**: Task 5

  **References**:
  - `app/Services/` - Lihat struktur folder services
  - `app/Models/AppSettings.php` - Model yang akan digunakan
  - `config/cache.php` - Cache configuration

  **Acceptance Criteria**:
  - [ ] Service bisa `get()` dan `set()` settings
  - [ ] Cache berfungsi (tidak query database tiap kali)
  - [ ] Helper methods berfungsi

  **QA Scenarios**:
  ```
  Scenario: Service get/set berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = new App\Services\AppSettingsService; \$s->set('app_name', 'MyPOS'); echo \$s->get('app_name');"
    Expected Result: Output "MyPOS"
    Evidence: .sisyphus/evidence/task-8-service-appsettings.txt
  
  Scenario: Cache berfungsi
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="cache()->flush(); \$s = new App\Services\AppSettingsService; \$s->get('app_name'); echo 'DB queries: ' . \DB::getQueryLog();"
    Expected Result: Query log hanya 1 kali (cache hit untuk call berikutnya)
    Evidence: .sisyphus/evidence/task-8-service-cache.txt
  ```

  **Commit**: YES
  - Message: `feat(settings): add AppSettingsService with caching`
  - Files: `app/Services/AppSettingsService.php`

- [x] 9. PrinterConfigService (CRUD, test connection)

  **What to do**:
  - Buat service `PrinterConfigService` di `app/Services/PrinterConfigService.php`
  - Methods:
    - `create($data)` - buat printer config
    - `update($id, $data)` - update printer config
    - `delete($id)` - soft delete printer config
    - `getByStore($storeId)` - ambil semua printer untuk store
    - `getDefault($storeId)` - ambil printer default untuk store
    - `setDefault($id)` - set printer sebagai default
    - `testConnection($id)` - test koneksi printer (return boolean atau error message)
  - Test connection: simulate atau gunakan library printer jika ada

  **Must NOT do**:
  - Tidak implementasi driver printer actual (terlalu kompleks), cukup simulate

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 6)
  - **Parallel Group**: Wave 3
  - **Blocks**: Task 12, 16
  - **Blocked By**: Task 6

  **References**:
  - `app/Models/PrinterConfig.php` - Model yang akan digunakan
  - `app/Services/` - Struktur folder services

  **Acceptance Criteria**:
  - [ ] Service bisa CRUD printer configs
  - [ ] Method `getDefault()` berfungsi
  - [ ] Method `testConnection()` return boolean

  **QA Scenarios**:
  ```
  Scenario: CRUD printer via service
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = new App\Services\PrinterConfigService; \$p = \$s->create(['store_id' => 1, 'name' => 'Test Printer', 'model' => 'XP-58', 'connection_type' => 'usb', 'address' => '/dev/usb/lp0']); echo \$p->id;"
    Expected Result: Printer tercreate
    Evidence: .sisyphus/evidence/task-9-service-printer-crud.txt
  
  Scenario: Set default printer
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = new App\Services\PrinterConfigService; \$s->setDefault(1); echo App\Models\PrinterConfig::find(1)->is_default;"
    Expected Result: Output "1" (true)
    Evidence: .sisyphus/evidence/task-9-service-set-default.txt
  ```

  **Commit**: GROUP dengan Task 8

- [x] 10. PaymentGatewayService (initiate, status, webhook)

  **What to do**:
  - Buat service `PaymentGatewayService` di `app/Services/PaymentGatewayService.php`
  - Methods:
    - `initiatePayment($storeId, $provider, $amount, $orderId, $customerData)` - initiate payment return QRIS/link
    - `checkStatus($paymentId, $provider)` - cek status payment
    - `handleWebhook($provider, $payload)` - handle webhook callback
    - `cancelPayment($paymentId, $provider)` - cancel pending payment
    - `getActiveConfig($storeId, $provider)` - ambil config aktif
  - Support Mayar dan Midtrans dengan logic terpisah
  - Gunakan existing PaymentGatewayConfig model

  **Must NOT do**:
  - Tidak implementasi API call actual (mock atau gunakan sandbox)
  - Tidak simpan API keys di kode (ambil dari database)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 7)
  - **Parallel Group**: Wave 3
  - **Blocks**: Task 13, 17
  - **Blocked By**: Task 7

  **References**:
  - `app/Models/PaymentGatewayConfig.php` - Model existing
  - `app/Services/` - Struktur folder services
  - Dokumentasi Mayar & Midtrans (external)

  **Acceptance Criteria**:
  - [ ] Service bisa `initiatePayment()` untuk Mayar dan Midtrans
  - [ ] Service bisa `checkStatus()`
  - [ ] Webhook handler structure tersedia

  **QA Scenarios**:
  ```
  Scenario: Initiate payment Mayar
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = new App\Services\PaymentGatewayService; \$result = \$s->initiatePayment(1, 'mayar', 100000, 'ORD-001', ['name' => 'Test']); echo isset(\$result['qr_code']) ? 'QR Generated' : 'Failed';"
    Expected Result: Output "QR Generated" (atau mock response)
    Evidence: .sisyphus/evidence/task-10-service-initiate-payment.txt
  
  Scenario: Get active config
    Tool: Bash (tinker)
    Steps:
      1. php artisan tinker --execute="\$s = new App\Services\PaymentGatewayService; \$config = \$s->getActiveConfig(1, 'mayar'); echo \$config ? 'Found' : 'Not Found';"
    Expected Result: Output sesuai ketersediaan config
    Evidence: .sisyphus/evidence/task-10-service-get-config.txt
  ```

  **Commit**: GROUP dengan Wave 3

- [x] 11. AppSettingsResource (global panel, singleton form)

  **What to do**:
  - Buat Filament Resource `AppSettingsResource` di `app/Filament/Resources/AppSettingsResource.php`
  - Panel: Global (tidak terikat store), hanya ada 1 page untuk edit settings
  - Form fields:
    - Section "Aplikasi": app_name, app_logo (file upload), favicon
    - Section "Regional": timezone (select), date_format (select), time_format (select), currency (select), currency_format (select)
    - Section "Email": email_from, email_from_name
    - Section "Maintenance": maintenance_mode (toggle)
  - Tidak ada table/list view, langsung redirect ke form edit
  - Navigation: "Pengaturan Aplikasi" di sidebar

  **Must NOT do**:
  - Tidak buat create page (hanya edit singleton)
  - Tidak buat delete action

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: `frontend-dev-guidelines`
  - **Skills Evaluated but Omitted**: None

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 8)
  - **Parallel Group**: Wave 4
  - **Blocks**: Task 15
  - **Blocked By**: Task 8

  **References**:
  - `app/Filament/Resources/` - Lihat pattern resource existing
  - `app/Filament/Resources/StoreResource.php` - Contoh resource dengan sections
  - Dokumentasi Filament v3: Forms, Resources

  **Acceptance Criteria**:
  - [ ] Resource bisa diakses di `/admin/app-settings`
  - [ ] Form bisa edit semua fields
  - [ ] Tidak ada create/delete actions

  **QA Scenarios**:
  ```
  Scenario: Buka halaman App Settings
    Tool: Playwright
    Steps:
      1. Navigate to /admin/app-settings/edit
      2. Assert page title contains "Pengaturan Aplikasi"
      3. Assert form fields visible (app_name, timezone, email_from, maintenance_mode)
    Expected Result: Page loaded, form visible
    Evidence: .sisyphus/evidence/task-11-filament-appsettings.png
  
  Scenario: Edit App Settings
    Tool: Playwright
    Steps:
      1. Fill app_name with "TestPOS"
      2. Select timezone "Asia/Jakarta"
      3. Toggle maintenance_mode ON
      4. Click Save
      5. Assert success message
    Expected Result: Settings tersimpan
    Evidence: .sisyphus/evidence/task-11-edit-appsettings.png
  ```

  **Commit**: YES
  - Message: `feat(settings): add AppSettingsResource Filament`
  - Files: `app/Filament/Resources/AppSettingsResource.php`

- [x] 12. PrinterConfigResource (store relation, table + form)

  **What to do**:
  - Buat Filament Resource `PrinterConfigResource` di `app/Filament/Resources/PrinterConfigResource.php`
  - List view: table dengan columns name, model, connection_type, is_default, is_active
  - Actions: Edit, Delete, Set as Default
  - Form:
    - Section "Informasi": store_id (select/relation), name, model
    - Section "Koneksi": connection_type (select: usb/bluetooth/network), address, port (show jika network)
    - Section "Status": is_default (toggle), is_active (toggle)
    - Section "Pengaturan Tambahan": settings (key-value repeater atau json editor)
  - Filter: by store, by connection_type
  - Navigation: "Printer" di sidebar bawah "Pengaturan"

  **Must NOT do**:
  - Tidak implementasi test connection via UI (cukup form)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: `frontend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 9)
  - **Parallel Group**: Wave 4
  - **Blocks**: Task 16
  - **Blocked By**: Task 9

  **References**:
  - `app/Filament/Resources/StoreResource.php` - Contoh resource
    
  **Acceptance Criteria**:
  - [ ] Table list printer configs
  - [ ] Form create/edit dengan semua fields
  - [ ] Action "Set as Default" berfungsi

  **QA Scenarios**:
  ```
  Scenario: Buka halaman Printer Config
    Tool: Playwright
    Steps:
      1. Navigate to /admin/printer-configs
      2. Assert table visible dengan columns name, model, connection_type
      3. Click "New Printer"
    Expected Result: Table loaded, create form visible
    Evidence: .sisyphus/evidence/task-12-filament-printer-list.png
  
  Scenario: Create Printer Config
    Tool: Playwright
    Steps:
      1. Fill name: "Printer Kasir"
      2. Fill model: "Xprinter XP-58"
      3. Select connection_type: "usb"
      4. Fill address: "/dev/usb/lp0"
      5. Toggle is_active ON
      6. Click Create
      7. Assert redirect ke list dan printer muncul di table
    Expected Result: Printer tercreate dan muncul di list
    Evidence: .sisyphus/evidence/task-12-create-printer.png
  ```

  **Commit**: GROUP dengan Task 11

- [x] 13. PaymentGatewayConfigResource (update untuk Midtrans)

  **What to do**:
  - Update existing `PaymentGatewayConfigResource` atau buat baru jika belum ada
  - Form fields:
    - Section "Provider": provider (select: mayar/midtrans)
    - Section "Konfigurasi Umum": is_active, is_sandbox
    - Section "Mayar Config" (show jika provider mayar): api_key, webhook_url
    - Section "Midtrans Config" (show jika provider midtrans): server_key, client_key, snap_url
  - List view: columns provider, is_active, is_sandbox
  - Actions: Edit, Delete
  - Navigation: "Payment Gateway" di sidebar

  **Must NOT do**:
  - Tidak hapus existing Mayar config

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: `frontend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 10)
  - **Parallel Group**: Wave 4
  - **Blocks**: Task 17
  - **Blocked By**: Task 10

  **References**:
  - `app/Filament/Resources/` - Resource existing
  - `app/Models/PaymentGatewayConfig.php` - Model

  **Acceptance Criteria**:
  - [ ] Resource bisa create/edit Mayar config
  - [ ] Resource bisa create/edit Midtrans config
  - [ ] Form conditional berfungsi (beda fields per provider)

  **QA Scenarios**:
  ```
  Scenario: Create Mayar config
    Tool: Playwright
    Steps:
      1. Navigate to /admin/payment-gateway-configs/create
      2. Select provider: "Mayar"
      3. Fill api_key
      4. Toggle is_active ON
      5. Click Create
    Expected Result: Config Mayar tercreate
    Evidence: .sisyphus/evidence/task-13-create-mayar.png
  
  Scenario: Create Midtrans config
    Tool: Playwright
    Steps:
      1. Navigate to /admin/payment-gateway-configs/create
      2. Select provider: "Midtrans"
      3. Fill server_key, client_key
      4. Toggle is_sandbox ON
      5. Click Create
    Expected Result: Config Midtrans tercreate
    Evidence: .sisyphus/evidence/task-13-create-midtrans.png
  ```

  **Commit**: GROUP dengan Task 11-12

- [x] 14. Widget Settings Overview di dashboard

  **What to do**:
  - Buat Filament Widget `SettingsOverview` di `app/Filament/Widgets/SettingsOverview.php`
  - Tampilkan di dashboard (admin panel home)
  - Cards:
    - App Name + Status (maintenance mode indicator)
    - Active Printers (count per store)
    - Active Payment Gateways (Mayar/Midtrans count)
  - Quick links ke masing-masing settings page

  **Must NOT do**:
  - Tidak buat chart/graph yang kompleks

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: `frontend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: YES (Wave 4)
  - **Parallel Group**: Wave 4
  - **Blocks**: None
  - **Blocked By**: Task 11-13

  **References**:
  - `app/Filament/Widgets/` - Widget existing
    
  **Acceptance Criteria**:
  - [ ] Widget muncul di dashboard
  - [ ] Cards menampilkan data yang benar

  **QA Scenarios**:
  ```
  Scenario: Widget di dashboard
    Tool: Playwright
    Steps:
      1. Navigate to /admin
      2. Assert widget "Settings Overview" visible
      3. Assert cards: App Name, Printers, Payment Gateways
    Expected Result: Widget visible dengan data
    Evidence: .sisyphus/evidence/task-14-widget-dashboard.png
  ```

  **Commit**: GROUP dengan Wave 4

- [x] 15. Update POS untuk gunakan AppSettings

  **What to do**:
  - Update Livewire POS component untuk gunakan AppSettingsService
  - Gunakan `app_name` dan `timezone` dari AppSettings
  - Handle `maintenance_mode` (tampilkan warning atau disable POS)
  - Update currency format sesuai settings
  - Files yang kemungkinan perlu update: `app/Livewire/Pos.php` atau POS component

  **Must NOT do**:
  - Tidak ubah logic POS yang fundamental

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `frontend-dev-guidelines`, `livewire-patterns`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 8, 11)
  - **Parallel Group**: Wave 5
  - **Blocks**: None
  - **Blocked By**: Task 8, 11

  **References**:
  - `app/Livewire/` - POS component existing
  - `app/Services/AppSettingsService.php` - Service yang akan digunakan

  **Acceptance Criteria**:
  - [ ] POS menggunakan timezone dari AppSettings
  - [ ] Currency format sesuai AppSettings
  - [ ] Maintenance mode ditangani

  **QA Scenarios**:
  ```
  Scenario: POS dengan AppSettings
    Tool: Playwright
    Steps:
      1. Set AppSettings timezone = "Asia/Jakarta"
      2. Buka halaman POS
      3. Assert timestamp di POS menggunakan timezone Jakarta
    Expected Result: Timezone sesuai settings
    Evidence: .sisyphus/evidence/task-15-pos-timezone.png
  ```

  **Commit**: YES
  - Message: `feat(settings): integrate AppSettings ke POS`
  - Files: `app/Livewire/Pos.php` atau file POS terkait

- [x] 16. Update Receipt untuk gunakan PrinterConfig

  **What to do**:
  - Update Receipt printing untuk gunakan PrinterConfig
  - Ambil printer default dari PrinterConfigService
  - Gunakan connection_type dan address untuk print
  - Update ReceiptTemplateService jika diperlukan
  - Files: `app/Services/ReceiptTemplateService.php`, `app/Services/ReceiptRenderer.php`

  **Must NOT do**:
  - Tidak ubah format receipt template

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 9, 12)
  - **Parallel Group**: Wave 5
  - **Blocks**: None
  - **Blocked By**: Task 9, 12

  **References**:
  - `app/Services/ReceiptTemplateService.php`
  - `app/Services/ReceiptRenderer.php`
  - `app/Services/PrinterConfigService.php`

  **Acceptance Criteria**:
  - [ ] Receipt service menggunakan printer config
  - [ ] Default printer diambil dari PrinterConfig

  **QA Scenarios**:
  ```
  Scenario: Receipt dengan PrinterConfig
    Tool: Bash (tinker)
    Steps:
      1. Buat printer config default
      2. Panggil ReceiptTemplateService
      3. Assert printer config ter-load
    Expected Result: Printer config ter-load
    Evidence: .sisyphus/evidence/task-16-receipt-printer.txt
  ```

  **Commit**: GROUP dengan Task 15

- [x] 17. Update Transaction untuk gunakan PaymentGateway

  **What to do**:
  - Update Transaction service untuk gunakan PaymentGatewayService
  - Integrate Midtrans payment option
  - Update UI POS untuk pilih payment gateway (Mayar/Midtrans)
  - Handle webhook Midtrans
  - Files: `app/Services/TransactionService.php`, POS component

  **Must NOT do**:
  - Tidak hapus support Mayar yang sudah ada

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: `backend-dev-guidelines`, `livewire-patterns`

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends Task 10, 13)
  - **Parallel Group**: Wave 5
  - **Blocks**: None
  - **Blocked By**: Task 10, 13

  **References**:
  - `app/Services/PaymentGatewayService.php`
  - `app/Models/Transaction.php`
  - POS Livewire component

  **Acceptance Criteria**:
  - [ ] Transaction bisa pakai Midtrans
  - [ ] Mayar tetap berfungsi
  - [ ] UI payment selection berfungsi

  **QA Scenarios**:
  ```
  Scenario: Transaction dengan Midtrans
    Tool: Bash (tinker)
    Steps:
      1. Buat Midtrans config
      2. Panggil PaymentGatewayService::initiatePayment dengan provider midtrans
      3. Assert return data valid
    Expected Result: Midtrans payment initiated
    Evidence: .sisyphus/evidence/task-17-transaction-midtrans.txt
  ```

  **Commit**: GROUP dengan Task 15-16

- [x] 18. Seeder default data

  **What to do**:
  - Update `database/seeders/DatabaseSeeder.php` atau buat seeder baru
  - Seed AppSettings default (app_name = "Simpel POS", timezone = "Asia/Jakarta", dll)
  - Seed sample PrinterConfig (optional, untuk development)
  - Seed PaymentGatewayConfig (sudah ada, pastikan tetap berfungsi)
  - Pastikan seeders bisa di-run dengan `php artisan migrate --seed`

  **Must NOT do**:
  - Tidak seed data production (API keys, dll)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: `backend-dev-guidelines`

  **Parallelization**:
  - **Can Run In Parallel**: YES (Wave 5)
  - **Parallel Group**: Wave 5
  - **Blocks**: F1-F4
  - **Blocked By**: Task 5, 6, 7

  **References**:
  - `database/seeders/DatabaseSeeder.php`
  - `database/seeders/` - Seeder existing

  **Acceptance Criteria**:
  - [ ] Seeder AppSettings berfungsi
  - [ ] Seeder PrinterConfig berfungsi (optional)
  - [ ] `php artisan migrate:fresh --seed` berhasil tanpa error

  **QA Scenarios**:
  ```
  Scenario: Run seeders
    Tool: Bash
    Steps:
      1. php artisan migrate:fresh --seed
      2. php artisan tinker --execute="echo App\Models\AppSettings::getInstance()->app_name;"
    Expected Result: Output "Simpel POS"
    Evidence: .sisyphus/evidence/task-18-seeder.txt
  ```

  **Commit**: GROUP dengan Wave 5

---

## Final Verification Wave

### F1. Plan Compliance Audit — `oracle`
- Verifikasi semua Must Have sudah diimplementasi
- Cek Must NOT Have tidak ada pelanggaran
- Bandingkan deliverables dengan plan
- Output: Compliance report

### F2. Code Quality Review — `unspecified-high`
- Run `tsc --noEmit` (jika ada) + `vendor/bin/pint`
- Run `php artisan test`
- Cek anti-patterns (AI slop)
- Output: Quality report

### F3. Real Manual QA — `unspecified-high`
- Test CRUD AppSettings
- Test CRUD PrinterConfig
- Test Payment Gateway Mayar & Midtrans
- Screenshot evidence
- Output: QA report dengan evidence

### F4. Scope Fidelity Check — `deep`
- Bandingkan diff dengan plan
- Cek tidak ada scope creep
- Verifikasi semua acceptance criteria
- Output: Fidelity report

---

## Commit Strategy

### Wave 1-2 (Foundation)
```
feat(settings): add migrations and models for app settings, printer config, payment gateway

- Add app_settings migration (singleton)
- Add printer_configs migration
- Extend payment_gateway_configs migration
- Add AppSettings model with singleton pattern
- Add PrinterConfig model
- Extend PaymentGatewayConfig with provider enum
```

### Wave 3 (Services)
```
feat(settings): add service classes for settings management

- Add AppSettingsService with cache
- Add PrinterConfigService with test connection
- Add PaymentGatewayService for Mayar & Midtrans
```

### Wave 4-5 (UI & Integration)
```
feat(settings): add Filament resources and integration

- Add AppSettingsResource (global panel)
- Add PrinterConfigResource
- Update PaymentGatewayConfigResource
- Integrate settings ke POS, Receipt, Transaction
```

---

## Success Criteria

### Verification Commands
```bash
# Test migrations
php artisan migrate:fresh --seed

# Test models
cd /Users/ryansutrisno/Sites/simpel-pos && php artisan tinker --execute="echo App\Models\AppSettings::get() ? 'OK' : 'FAIL';"

# Test services
php artisan test --filter=Settings

# Test Filament resources
# (Akses /admin/app-settings, /admin/printer-configs via Playwright)
```

### Final Checklist
- [ ] Semua migration berjalan tanpa error
- [ ] Model AppSettings bisa get/set singleton
- [ ] Model PrinterConfig bisa CRUD
- [ ] PaymentGatewayConfig support Mayar & Midtrans
- [ ] Filament resources bisa diakses dan digunakan
- [ ] Services punya methods yang reusable
- [ ] Semua tests pass
- [ ] Tidak ada breaking changes
