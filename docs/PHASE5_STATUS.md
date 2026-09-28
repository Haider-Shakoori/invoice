# Phase 5 — Release Quality & Production Readiness Status

Phase 5 turns the completed invoice product into an operable release with production health checks, audited tenant migrations, verified backups, security regression coverage and real multi-page PDF stress validation.

## Release readiness

The application provides:

- `php artisan invoice:doctor`
- `php artisan invoice:doctor --json`
- central-domain `/health/ready`
- Laravel `/up` liveness

The doctor verifies:

- central database connectivity
- private storage read/write
- cache read/write
- Chromium/Chrome availability
- Arabic-script font availability
- production configuration guards

Production configuration is rejected by the doctor when critical settings are unsafe, including debug mode, synchronous queues, missing app key, insecure/non-host-only sessions or a non-HTTPS application URL.

The public production readiness endpoint returns check statuses without exposing internal database/storage exception details. Detailed diagnostics remain available to operators through the CLI doctor.

## Request tracing and logging

Every central and tenant request receives an `X-Request-ID`.

- a valid upstream ID is preserved
- otherwise a UUID is generated
- the request ID, host, method and path are added to Laravel log context
- the ID is returned in the HTTP response

A daily log driver with configurable retention is available for production in addition to stderr logging.

## Security and authorization review

Phase 5 adds route-level regression tests that ensure sensitive operations keep their server-side authorization middleware, including:

- company settings
- invoice PDF export
- prior-export download
- invoice deletion
- staff management
- central commercial mutations

Existing tenant-isolation tests continue to verify separate databases, separate private file roots, tenant-aware queue payloads and central-vs-tenant authentication boundaries.

Production health output was also hardened against diagnostic information disclosure.

## Tenant-wide migration process

`invoice:migrate-tenants` applies tenant migrations one tenant at a time.

Capabilities:

- fleet-wide operation
- target by tenant UUID or slug
- `--dry-run` migration-status inspection
- ready-tenants-only selection
- central provisioning/audit event per applied tenant
- explicit per-tenant success/failure results

The dedicated MySQL integration suite verifies both dry-run and audited migration application.

## Backup integrity

`invoice:backup` creates a private bundle containing:

- central MySQL dump
- every tenant MySQL dump
- every tenant private file, including branding and generated PDF exports
- manifest metadata
- SHA-256 and byte size for every backed-up file

`invoice:backup-verify` verifies the bundle and rejects missing, altered or unsafe-path entries.

Phase 5 includes:

- synthetic tamper-detection tests
- a real MySQL integration test that provisions tenants, creates tenant-private data/files, creates the backup bundle and verifies the hashes

Restore remains an explicit operator procedure rather than a destructive application command. See [BACKUP_RESTORE.md](BACKUP_RESTORE.md).

## PDF stress acceptance

The Chromium pipeline is tested beyond the Phase 4 one-page matrix.

Automated release tests generate:

- a representative English invoice that must span at least two A4 pages
- a large Pashto/RTL invoice that must span at least five A4 pages

The test uses `pdfinfo` to verify A4 pagination and, when available, `pdftotext` to verify that invoice identifiers/content remain extractable.

The existing 20-template × 3-locale render matrix and mixed-script PDF smoke test remain in the suite.

## Low-bandwidth and accessibility review

The tenant workspace remains server-rendered Blade with no external frontend CDN dependency.

Release-quality UI work includes:

- responsive one-column mobile fallbacks
- semantic main/navigation regions
- keyboard skip-to-content link
- visible `:focus-visible` treatment
- document `lang` and `dir` switching
- RTL layout for Dari/Pashto
- LTR isolation for numeric values
- pagination on primary client/invoice lists

PDF generation remains server-side so low-end client devices do not need to render/export documents locally.

See [PERFORMANCE_ACCESSIBILITY.md](PERFORMANCE_ACCESSIBILITY.md).

## Scheduling and production operations

Laravel's scheduler runs commercial subscription synchronization hourly with overlap protection. Production must run `php artisan schedule:run` each minute and supervised queue workers for the selected non-sync queue driver.

See [DEPLOYMENT.md](DEPLOYMENT.md).

## Acceptance

The final implementation gate before documentation cleanup passed:

- Composer security audit: passed
- Pint: passed
- quality suite: **42 passed / 590 assertions**
- release doctor: **ready**
- real Chromium PDF tests: passed
- multi-page A4 stress tests: passed
- route authorization audit: passed
- backup tamper tests: passed
- dedicated MySQL suite: **8 passed / 51 assertions**
- real MySQL tenant backup bundle: passed
- audited tenant release migration: passed

The MySQL scenarios are intentionally skipped in the general SQLite quality process and are executed separately against MySQL 8.4.

## Release state

With Phase 5 implemented, the original five-phase implementation roadmap is complete. Deployment to a specific production host remains an environment-specific operation and must follow the deployment and backup/restore runbooks.
