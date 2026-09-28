# Phase 1 Status

## Implementation checklist

- [x] Laravel 12 application foundation
- [x] stancl/tenancy configuration and context bootstrappers
- [x] central domain vs tenant domain routing
- [x] separate central and tenant authentication guards
- [x] central operator login and environment-backed bootstrap command
- [x] tenant login with inactive-account protection
- [x] registration validation
- [x] reserved and duplicate subdomain checks
- [x] central tenancy/business/provisioning schema
- [x] isolated tenant database creation and tenant migrations
- [x] tenant users/company/settings/template schema
- [x] 20 built-in template definitions
- [x] Owner/Admin/Staff/Read-only roles and server-side permissions
- [x] provisioning audit events
- [x] non-destructive failed-provisioning recovery
- [x] tenant-scoped database/cache/filesystem/queue bootstrappers
- [x] unknown tenant hosts fail closed with 404
- [x] central/tenant authentication boundary test
- [x] real MySQL cross-tenant database isolation test
- [x] tenant filesystem isolation test
- [x] queue tenant-context propagation/reversion test
- [x] provisioning recovery preserves existing tenant data
- [x] Composer lock file committed for reproducible installs
- [x] Composer security audit in CI
- [x] Laravel Pint enforcement in CI
- [x] PHPUnit quality suite
- [x] dedicated MySQL tenancy integration job

## Merge gate

Phase 1 is ready to merge only when the current pull-request head passes both CI jobs:

1. `quality` — Composer install/audit, Pint, PHPUnit.
2. `tenancy-integration` — real MySQL tenant database, filesystem, queue-context, session-boundary and recovery tests.

The tenant product boundary remains invoice-only. Inventory, POS, accounting, warehouse, procurement, payroll and similar ERP modules are intentionally outside this application.
