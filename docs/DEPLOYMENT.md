# Production Deployment Runbook

This runbook is the required release sequence for Invoice Drafts SaaS. The application uses one central commercial database plus one isolated database and private storage context per tenant.

## Production runtime

Recommended baseline:

- PHP 8.3+
- MySQL 8.0/8.4
- Redis for production cache and queues
- Chromium or Google Chrome for PDF export
- Noto Arabic-script fonts, preferably Noto Naskh Arabic / Noto Sans Arabic
- a process supervisor for queue workers
- cron access for Laravel's scheduler
- HTTPS on the central domain and all tenant subdomains

The application remains server-rendered and does not require an external frontend CDN.

## Required environment guards

Production must use:

- `APP_ENV=production`
- `APP_DEBUG=false`
- an `https://` `APP_URL`
- a valid `APP_KEY`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_DOMAIN` unset, preserving host-only sessions between central and tenant domains
- a non-`sync` queue connection
- a persistent cache store
- valid `CENTRAL_DOMAIN` and `TENANT_BASE_DOMAIN`
- a working `PDF_CHROMIUM_BINARY` or Chromium/Chrome in a standard Linux path
- an executable `mysqldump` or `MYSQLDUMP_BINARY`

For production logging, a useful stack is `LOG_STACK=daily,stderr`, with `LOG_DAILY_DAYS` set to the desired retention.

## Before every release

1. Put the deployment into the site's normal maintenance/release procedure so writes are controlled during schema changes.
2. Confirm the working tree is the intended tagged/merged release.
3. Create a backup:
   ```bash
   php artisan invoice:backup
   ```
4. Verify the generated bundle:
   ```bash
   php artisan invoice:backup-verify /absolute/path/to/backup
   ```
5. Copy the verified backup to encrypted off-host storage before applying destructive or schema-changing work.
6. Run the release doctor against the existing production runtime:
   ```bash
   php artisan invoice:doctor
   ```

Do not proceed if a required doctor check fails.

## Application deployment

A typical optimized install is:

```bash
composer install --no-dev --prefer-dist --classmap-authoritative --no-interaction
php artisan optimize:clear
php artisan migrate --force
```

The first migration command applies only central migrations.

## Tenant migrations

Never assume the central migration updates tenant databases.

First inspect tenant migration state:

```bash
php artisan invoice:migrate-tenants --dry-run
```

For a staged rollout, target one tenant by slug or UUID:

```bash
php artisan invoice:migrate-tenants --tenant=customer-slug --dry-run
php artisan invoice:migrate-tenants --tenant=customer-slug
```

When the staged tenant is healthy, apply all ready tenants:

```bash
php artisan invoice:migrate-tenants
```

Every applied tenant migration records a central provisioning/audit event. A failure in one tenant is reported without silently marking the whole fleet successful.

## Cache, routes and workers

After migrations:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Ensure at least one supervised queue worker is running for the configured queue connection.

Laravel's scheduler must run once per minute from cron:

```cron
* * * * * cd /path/to/invoice && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `invoice:sync-subscriptions` hourly with overlap protection.

## Health and smoke verification

The platform has two health concepts:

- `/up` — lightweight Laravel liveness
- `/health/ready` on the central domain — database/storage/cache/PDF/font/config readiness

The readiness response intentionally omits internal diagnostic messages in production. Operators can see the detailed reason with:

```bash
php artisan invoice:doctor
```

Also verify:

- central operator login
- one active tenant login
- tenant isolation by opening a different tenant host
- client list
- create/edit an invoice draft
- English and one RTL preview
- PDF generation and re-download
- subscription lock behavior on a non-active test tenant

## Request tracing

Every central and tenant web request receives an `X-Request-ID` response header. The same ID is included in the Laravel logging context. Support should request this ID when investigating a failed browser request.

A valid upstream request ID is preserved; otherwise the application creates a UUID.

## Rollback

Application-code rollback is separate from database rollback. Do not automatically run Laravel migration rollbacks across tenant databases.

If a release changed data/schema incompatibly:

1. stop writes
2. retain the failed environment for investigation
3. restore the pre-release verified backup into a clean recovery environment
4. validate the recovery using the restore runbook
5. switch traffic only after central and tenant smoke checks pass

See [BACKUP_RESTORE.md](BACKUP_RESTORE.md).
