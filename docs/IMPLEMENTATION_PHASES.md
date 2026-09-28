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
Status: implementation complete on the Phase 2 feature branch; awaiting CI/merge.

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
- mandatory resumable company onboarding
- direct landing on Invoices (no analytics dashboard)
- clients
- invoice draft list/editor
- safe numbering
- server calculation engine
- client/company snapshots
- staff permissions
- draft duplicate/delete/version audit

## Phase 4 — Localization & PDF
- complete English/Dari/Pashto application UI
- independent app locale vs export locale
- RTL-safe Chromium PDF path
- all 20 templates
- visual template gallery
- preview + PDF export
- 60 template/locale acceptance combinations

## Phase 5 — Release Quality
- full test suite
- cross-tenant isolation/security review
- backup/restore procedures
- lower-bandwidth optimization
- accessibility/responsive review
- production deployment checklist
- tenant-wide safe migration process
