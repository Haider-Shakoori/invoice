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
- Expired subscriptions are locked non-destructively; tenant data is retained

## Architecture

- Laravel 12 baseline (requirement is Laravel 11+)
- one shared codebase
- one central commercial database
- one database per tenant
- stancl/tenancy
- MySQL/MariaDB, utf8mb4
- Redis when available
- Blade-oriented low-bandwidth tenant workspace
- domain-scoped tenant sessions
- tenant-isolated media/PDF storage
- Chromium/Chrome PDF renderer for selectable multilingual text

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Tenant workspace

After company onboarding, users land directly on **Invoices** rather than an analytics dashboard. Phase 3 provides client management, invoice draft creation/editing, transaction-safe numbering, authoritative server calculations, customer/company snapshots, version/activity history, duplication, and role-based staff access.

See [docs/PHASE3_STATUS.md](docs/PHASE3_STATUS.md).

## Localization and PDF templates

The system ships with 20 visual designs based on the supplied template preview PDF. The tenant UI supports English, Dari and Pashto, with mirrored RTL composition for Dari/Pashto. App language and invoice export language are independent.

Invoice preview/export supports:
- all 20 built-in A4 templates
- English, Dari and Pashto
- AFN and USD
- optional invoice/line discounts
- optional tax and additional charges
- tenant-private logo, signature and stamp assets
- private PDF storage, export history and authorized re-download
- immutable export metadata including invoice version, template, locale, SHA-256 and file size

Changing a template or export locale never changes stored invoice numbering or totals.

See [docs/INVOICE_TEMPLATES.md](docs/INVOICE_TEMPLATES.md) and [docs/PHASE4_STATUS.md](docs/PHASE4_STATUS.md).

## Delivery phases

1. Foundation: tenancy, auth, provisioning, central/tenant schemas, operator roles.
2. Commercial SaaS: trial, activation/renewal, manual payments, central receipts, sellers/commissions.
3. Tenant workspace: onboarding, clients, invoice editor, calculations, numbering, snapshots, staff and version history.
4. Localization/PDF: English/Dari/Pashto, RTL-safe rendering, 20 templates, preview/export.
5. Release quality: security/isolation review, visual PDF stress matrix, backups, performance, deployment.

Phases 1–5 are implemented. Phase 5 is in final CI/PR verification before merge.


## Production operations

Release operations include readiness checks, audited tenant migrations, verified central/tenant backup bundles, request tracing and multi-page PDF stress coverage.

See:
- [Phase 5 status](docs/PHASE5_STATUS.md)
- [Production deployment runbook](docs/DEPLOYMENT.md)
- [Backup and restore runbook](docs/BACKUP_RESTORE.md)
- [Performance and accessibility review](docs/PERFORMANCE_ACCESSIBILITY.md)
