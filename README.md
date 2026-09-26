# ERPSaaS — B2B Operations, Manufacturing & Financial Cockpit

**ERPSaaS** is a multi-tenant Enterprise Resource Planning (ERP) platform architected for commercial B2B operations, batch manufacturing, agro-processing, wholesale distribution, and multi-entity financial management.

---

## 🌟 Core Architecture & Capabilities

- **Strict Multi-Tenancy**: Complete data isolation across tenants powered by `TenantScope` and `BelongsToTenant` Eloquent traits. Every query, customer code sequence, and financial record is strictly scoped to the active tenant workspace.
- **Sequential Per-Tenant Customer Numbering**: Each tenant's customer account sequence automatically begins at `1` (`CUST-0001`) with compound unique constraints `UNIQUE(tenant_id, customer_code)`.
- **End-to-End Operational Pipeline**:
  $$\text{CRM / Leads} \longrightarrow \text{Samples \& Trials} \longrightarrow \text{Quotations} \longrightarrow \text{Sales Orders} \longrightarrow \text{Dispatches (LR)} \longrightarrow \text{Tax Invoices} \longrightarrow \text{Collections / AR}$$
- **Advance Payments against PO**: Integrated advance receipt tracking against purchase orders with automatic balance deduction on generated tax invoices.
- **Dynamic White-Labeling**: Tenant-specific letterheads, GSTIN, bank credentials, addresses, and authorized signatures dynamically populated on quotes, invoices, and PDF downloads.
- **Executive Command Center**: Action-first daily decision cockpit highlighting urgent collections, pending dispatches, unbilled orders, and scheduled automated cron digests.
- **Role-Based Access Control (RBAC)**: Fine-grained permissions powered by Spatie Laravel-Permission (`owner`, `admin`, `manager`, `operator`, `viewer`) with Laravel Fortify multi-factor authentication (2FA) and Passkeys.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend Framework** | Laravel 11 / PHP 8.4+ |
| **Database** | MySQL 8.0 (Production: `saas_erp` / Testing: `saas_erp_test`) |
| **Frontend UI** | Blade Templates + Alpine.js v3 + Tailwind CSS v4 |
| **PDF Generation** | Barryvdh Laravel DomPDF |
| **Spreadsheet Engine** | Maatwebsite Laravel Excel / PhpSpreadsheet |
| **Authentication & RBAC** | Laravel Fortify + Spatie Laravel-Permission |
| **Test Suite** | Pest PHP + PHPUnit with Strict Production Database Guard |

---

## 📁 Clean Directory Structure

```text
├── app/
│   ├── Actions/Fortify/          # Auth workflows (register, password reset, 2FA)
│   ├── Console/Commands/         # Daily priority queue & executive digest commands
│   ├── Exports/                  # Multi-sheet Excel workbook exports
│   ├── Helpers/                  # INR formatting and status helpers
│   ├── Http/
│   │   ├── Controllers/          # RESTful domain controllers (Customer, Order, Invoice, etc.)
│   │   └── Middleware/           # Tenant context & RBAC middleware
│   ├── Models/                   # Domain Eloquent models with BelongsToTenant
│   ├── Scopes/                   # Global TenantScope isolation
│   └── Services/                 # Analytics, TenantManager, Logistics tracking, Crypto
├── config/                       # Application, auth, database, fortify, permission configs
├── database/
│   ├── migrations/               # Schema migrations including per-tenant compound indexes
│   └── seeders/                  # Production & test database seeders
├── resources/
│   ├── css/                      # Tailwind CSS v4 styling
│   ├── js/                       # Vite asset bundles
│   └── views/                    # Responsive Blade templates & Bento UI components
├── routes/
│   ├── console.php               # Scheduled cron definitions
│   └── web.php                   # Authenticated ERP web routes
└── tests/
    ├── Feature/                  # Feature & integration test suites
    ├── Unit/                     # Safety guards & unit tests
    └── TestCase.php              # Automated test harness with prohibited DB safety guard
```

---

## 🛡️ Database Safety Guard

To prevent data loss or accidental manipulation of production databases during testing, `tests/TestCase.php` and `tests/Pest.php` enforce a strict runtime guard:

```php
$prohibitedDatabases = ['saas_erp'];

if ($connection === 'mysql' && in_array(strtolower($database), $prohibitedDatabases, true)) {
    throw new RuntimeException(
        "SAFETY ERROR: Tests are attempting to use a production ERP database [Database: {$database}]."
    );
}
```

All automated tests execute exclusively against the dedicated test database (`saas_erp_test`).

---

## 🧪 Running Tests

Execute the complete automated test suite with Pest:

```powershell
php artisan test
```

---

## 🚀 Deployment & Local Setup

1. **Clone repository & install dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Migrate database**:
   ```bash
   php artisan migrate
   ```

4. **Build assets**:
   ```bash
   npm run build
   ```

5. **Start server**:
   ```bash
   php artisan serve
   ```
