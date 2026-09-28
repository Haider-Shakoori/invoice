# Invoice Drafts SaaS

A multilingual, invoice-only SaaS for Afghan businesses.

## Product boundary

Tenant features are intentionally limited to:
- company onboarding and profile
- clients
- invoice drafts
- 20 built-in invoice templates
- English, Dari and Pashto UI/PDF output
- tenant settings and account access

The tenant application does **not** include stock, inventory, POS, accounting, payment collection, sales analytics, warehouses, procurement, payroll, attendance, or ERP modules.

## Commercial model

- 7-day trial by default (centrally configurable)
- First activation: 5,000 AFN total
  - 2,000 AFN setup/activation
  - 3,000 AFN first paid year
- Renewal: 3,000 AFN/year
- Manual payment recording: cash, bank transfer, hawala
- Central platform invoices/receipts are separate from tenant-created invoice drafts

## Architecture

- Laravel 12 baseline (requirement is Laravel 11+)
- one shared codebase
- one central commercial database
- one database per tenant
- stancl/tenancy
- MySQL/MariaDB, utf8mb4
- Redis when available
- Blade/Livewire-oriented server-rendered UI
- domain-scoped tenant sessions
- tenant-isolated media/PDF storage

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Invoice templates

The system ships with 20 visual designs based on the supplied template preview PDF. Template selection is a presentation choice only; it never changes invoice data, totals, numbering, or client snapshots.

See [docs/INVOICE_TEMPLATES.md](docs/INVOICE_TEMPLATES.md).

## Delivery phases

1. Foundation: tenancy, auth, provisioning, central/tenant schemas, operator roles.
2. Commercial SaaS: trial, activation/renewal, manual payments, central receipts, sellers/commissions.
3. Tenant workspace: onboarding, clients, invoice editor, calculations, numbering, authorization.
4. Localization/PDF: English/Dari/Pashto, RTL-safe rendering, 20 templates, export.
5. Release quality: security/isolation tests, PDF matrix, backups, performance, deployment.

## Current branch

`feat/phase-1-foundation` establishes the architecture and implementation contract before application scaffolding and migrations are added.
