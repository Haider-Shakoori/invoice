# Phase 1 Status

## Implemented on feat/phase-1-foundation

- Laravel 12 application foundation
- stancl/tenancy configuration
- central domain vs tenant domain routing
- separate central and tenant authentication guards
- central operator login
- tenant login
- registration request validation
- reserved and duplicate subdomain checks
- central tenancy/business/provisioning schema
- tenant users/company/settings/template schema
- 20 built-in template definitions
- tenant provisioning action
- provisioning audit events
- tenant-scoped filesystem/cache/queue bootstrappers
- safe unknown-host test
- GitHub Actions quality workflow

## Still required before Phase 1 is complete

- make provisioning idempotent/retry-safe for partially completed tenants
- central operator seeder/bootstrap command
- tenant Owner/Admin/Staff/Read-only permission enforcement
- domain/session boundary hardening tests
- explicit cross-tenant DB read/write isolation tests
- tenant-scoped file/download isolation tests
- queue tenancy-context test
- provisioning rollback/retry tests
- run CI successfully against the complete application scaffold
- resolve any Pint/PHPUnit/Composer issues discovered by CI

No Phase 1 feature should be considered release-ready until the CI suite runs successfully.
