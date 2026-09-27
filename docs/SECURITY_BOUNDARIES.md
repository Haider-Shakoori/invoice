# Security and Isolation Boundaries

## Authentication

- Central operators use the dedicated `central` guard and `admin_users` provider.
- Tenant users use the tenant-scoped `web` guard and tenant `users` table.
- Inactive accounts are rejected during authentication.
- Sessions use host-only cookies by default; `SESSION_DOMAIN` should remain unset unless a reviewed deployment explicitly requires otherwise.

## Tenant identification

Tenant identification is prioritized ahead of Laravel authentication middleware. Unknown hosts must fail closed before an unauthenticated redirect can occur.

## Data placement

Central:
- tenant infrastructure
- business/subscription/commercial data
- platform operators
- provisioning events

Tenant-only:
- users/roles/permissions
- business profile and branding
- customers
- invoice drafts and lines
- invoice templates/preferences
- exports and media

## Permissions

Built-in tenant roles:
- Owner: all tenant permissions
- Admin: all tenant permissions
- Staff: clients, drafts and PDF export
- Read-only: view clients and drafts

Permissions are enforced server-side through `tenant.permission` middleware; hiding UI controls alone is not considered authorization.

## Provisioning recovery

Failed provisioning is non-destructive. `ResumeTenantProvisioning` reruns tenant migrations idempotently, reseeds baseline configuration, restores/updates the owner account, ensures the tenant domain exists, and marks the tenant ready only after all steps succeed. It never drops a populated tenant database as a retry strategy.

## Files and queues

The tenancy package bootstrappers isolate database, cache, filesystem and queue context. Private logos, signatures, stamps and generated PDFs must remain on tenant-scoped storage and require authorization for download.
