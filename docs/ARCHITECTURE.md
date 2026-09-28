# Architecture

## Invariants

1. One application codebase.
2. One central commercial database.
3. One separate database per tenant.
4. Tenant customer contacts, invoice drafts, lines, exports and media never live in the central database.
5. Central operators cannot browse tenant-private invoice data through the central application.
6. Unknown tenant hosts fail closed with a safe 404.
7. Tenant ID is a stable validated slug and tenant domains are collision-safe.
8. Every cache, file, queue job, PDF export and session carries tenant context.

## Central database

Planned tables:
- tenants
- domains
- businesses
- admin_users
- plans
- subscriptions
- platform_invoices
- platform_invoice_lines
- platform_payments
- seller_commissions
- activation_requests
- subscription_audit_events
- provisioning_events
- support_notes

Commercial reporting must use platform invoices/payments only. Tenant draft totals are never platform revenue.

## Tenant database

Planned tables:
- users
- roles
- permissions
- business_profiles
- customers
- document_sequences
- invoice_drafts
- invoice_lines
- invoice_versions
- invoice_templates
- template_preferences
- document_exports
- document_activity
- settings
- media_assets

No product, stock, supplier, purchase, warehouse, payment, transaction, expense or sales-ledger tables are permitted in the initial tenant product.

## Provisioning workflow

Provisioning must be retryable and auditable:

1. reserve requested subdomain
2. create central business/commercial record
3. create tenant
4. create tenant database
5. run tenant migrations
6. seed baseline settings + 20 template definitions
7. create first owner user with hashed password
8. attach domain
9. initialize tenant storage/cache context
10. mark tenant ready

Failures are recorded and safely retryable. A populated tenant database is never deleted as an automatic recovery step.

## Authentication boundaries

Central operator authentication and tenant authentication are separate. Tenant accounts cannot access central routes. Sessions and CSRF cookies are domain-scoped.

## Storage

Tenant logos, signatures, stamps, generated PDFs and export metadata are tenant-scoped. Downloads must be authorized through a tenant-aware controller or signed expiring URL; PDFs must not be exposed through predictable public paths.

## PDF rendering

Use HTML print through a Chromium-class renderer for complex-script shaping and RTL support. PDF text should remain selectable/searchable where practical. English is LTR; Dari and Pashto use independently mirrored RTL layouts.

## Calculation policy

All authoritative totals are recalculated server-side using decimal arithmetic. The client may display live calculations but submitted totals are never trusted. Currency-aware rounding is centralized in a calculation service.

## Historical integrity

Client identity/contact values and company/template-relevant values are snapshotted into the invoice/version model so later profile edits do not silently rewrite earlier exports.
