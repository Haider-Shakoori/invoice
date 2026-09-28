# Implementation Roadmap

## Phase 1 — Foundation
Status: complete and merged.

- Laravel application scaffold
- central + tenant migration separation
- stancl/tenancy configuration
- central operator auth boundary
- tenant auth boundary
- domain isolation and safe unknown-host behavior
- provisioning service and audit trail
- initial tenant roles/permissions
- storage/cache/queue tenancy context
- schema/isolation tests

## Phase 2 — Commercial SaaS
Status: complete and merged.

- signup + requested subdomain
- configurable 7-day trial
- 5,000 AFN first activation split into setup + first year
- 3,000 AFN annual renewal
- manual cash/bank/hawala payments
- platform invoice/receipt records
- sellers/resellers and commission ledger
- tenant activation requests + central review workflow
- expiry/renewal synchronization
- non-destructive lock/retrieval policy
- commercial audit log
- automated lifecycle tests

See [PHASE2_STATUS.md](PHASE2_STATUS.md).

## Phase 3 — Tenant Workspace
Status: complete and merged.

- mandatory resumable company onboarding
- direct landing on Invoices (no analytics dashboard)
- client management
- invoice draft list/editor
- concurrency-safe yearly numbering
- server calculation engine using scaled decimal arithmetic
- customer/company snapshots
- staff accounts and server-side permissions
- draft duplicate/delete/version/activity history
- low-bandwidth responsive Blade workspace
- tenant-isolation and draft lifecycle tests

See [PHASE3_STATUS.md](PHASE3_STATUS.md).

## Phase 4 — Localization & PDF
Status: complete and merged.

- complete English/Dari/Pashto tenant UI dictionaries
- independent app locale vs document/export locale
- mirrored RTL workspace and invoice composition for Dari/Pashto
- Chromium/Chrome PDF rendering with real mixed-script PDF smoke coverage
- all 20 source-derived template families
- visual template gallery
- HTML document preview + private PDF export
- tenant-private logo/signature/stamp assets
- authorized export history/re-download
- AFN/USD, optional tax and additional-charge support
- 20 templates × 3 document locales = 60-combination render matrix
- export/template/locale changes do not mutate invoice number, totals, locale or version

See [PHASE4_STATUS.md](PHASE4_STATUS.md).

## Phase 5 — Release Quality & Production Readiness
Status: implementation complete on the Phase 5 feature branch; final CI/PR merge is pending.

- server-side route authorization regression audit
- cross-tenant MySQL isolation suite
- real two-page and five-page A4 PDF stress fixtures
- production Chromium/font validation in CI and release doctor
- verified central + per-tenant database/private-file backup bundles
- backup tamper detection and controlled restore runbook
- low-bandwidth/performance review
- keyboard/accessibility and responsive baseline review
- production deployment runbook
- audited tenant-wide dry-run/apply migration process
- request-ID tracing, daily logging and readiness health checks
- hourly subscription synchronization with overlap protection

See [PHASE5_STATUS.md](PHASE5_STATUS.md), [DEPLOYMENT.md](DEPLOYMENT.md) and [BACKUP_RESTORE.md](BACKUP_RESTORE.md).
