# Phase 3 — Tenant Workspace Status

Phase 3 turns the SaaS foundation into the working invoice application used inside each tenant.

## Workspace flow

1. The tenant owner signs in on the tenant domain.
2. Subscription access is checked centrally.
3. Incomplete tenants are redirected to mandatory company onboarding.
4. Completed tenants land directly on the Invoices list.
5. Clients, invoice drafts and staff are available according to server-side tenant permissions.

There is intentionally no analytics dashboard between login and invoice work.

## Company onboarding

Company onboarding is resumable and stores:

- company and secondary/local name
- address and city/province
- phone and WhatsApp
- email and website
- reference/registration number
- default invoice language
- AFN as the tenant currency

The tenant cannot enter the invoice workspace until required onboarding fields are complete.

## Clients

Clients are tenant-private records containing identity, contact and reference details. Search is available by client name, company, phone, email and code.

A client already referenced by an invoice cannot be physically removed through the workspace. Invoice customer values are snapshotted so later client edits never silently rewrite an existing invoice.

## Invoice drafts

The invoice workspace includes:

- direct Invoices landing page
- searchable invoice list
- create/edit forms
- multiple invoice lines
- quantities up to three decimal places
- unit labels
- unit prices
- per-line percentage discounts
- invoice-level percentage or fixed discounts
- notes and terms
- 20 seeded template choices
- English/Dari/Pashto document-language selection
- duplicate action with a new invoice number
- controlled draft deletion
- version history
- activity history

## Numbering

Invoice numbers use a database-locked yearly sequence in the form:

`INV-YYYY-000001`

The sequence is generated inside a transaction with row locking so concurrent users cannot receive the same number.

## Calculation policy

The browser shows live preview totals for usability, but those totals are never trusted.

The server recalculates every invoice using scaled integer decimal arithmetic:

- quantity scale: 3 decimal places
- monetary scale: 2 decimal places
- discount percentage scale: 4 decimal places
- half-up rounding at monetary boundaries

The server rejects negative prices, zero/negative quantities, discounts outside 0–100%, excess decimal precision and fixed discounts larger than the subtotal.

## Historical integrity

Each saved invoice contains independent customer and company snapshots. Each update creates a new numbered version snapshot containing invoice fields and lines.

Changing a company profile or client record therefore does not mutate an already-saved invoice version.

## Staff and permissions

The owner/admin permission model is enforced by middleware on the server. Tenant staff can be assigned:

- Admin
- Staff
- Read-only

Permissions govern client viewing/management, draft viewing/management/deletion, template/settings access, PDF export and staff management.

The original owner account cannot be modified from ordinary staff management.

## Low-bandwidth UI

The Phase 3 tenant workspace is server-rendered Blade with a responsive layout and no external CDN dependency. This keeps the initial workspace usable on slower or unstable connections.

## Isolation tests

The dedicated MySQL tenancy test now verifies that the Phase 3 workspace schema is created independently in tenant databases and that client records created in one tenant are not visible in another.
