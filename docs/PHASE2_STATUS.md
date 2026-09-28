# Phase 2 — Commercial SaaS Status

Phase 2 implements the platform-side commercial lifecycle while keeping billing records out of tenant databases.

## Implemented

- centrally configurable 7-day trial
- default annual AFN plan
- 2,000 AFN setup + 3,000 AFN first-year activation
- 3,000 AFN annual renewal
- sequential platform invoice and receipt numbers
- manual cash, bank-transfer and hawala payments
- partial-payment support with overpayment prevention
- entitlement activation only after full invoice settlement
- paid activation preserves unused trial time
- early renewal extends the existing paid term
- seller/reseller records with configurable percentage or fixed commission
- seller commission ledger and paid state
- tenant activation requests
- central approve/reject activation workflow
- subscription audit events
- status synchronization for trials, paid terms and optional grace
- non-destructive lock after expiry
- central commercial summary and operator endpoints
- backfill/synchronization command for existing businesses
- automated commercial lifecycle tests

## Access policy

Trialing, active and configured-grace subscriptions may use protected tenant invoice features. Suspended, expired and cancelled subscriptions receive HTTP 402 from protected features while tenant databases, documents and media remain retained.

Tenant login, logout, subscription status and activation-request endpoints remain available when billing access is locked.

## Payment policy

Supported payment methods are cash, bank transfer and hawala. Partial payments are allowed. A subscription entitlement changes only when recorded payments settle the entire platform invoice, and overpayments are rejected.

Platform commercial invoices and receipts are separate from tenant-created invoice drafts. Tenant draft totals are never treated as SaaS revenue.
