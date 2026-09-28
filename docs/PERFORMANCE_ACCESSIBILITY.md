# Performance, Low-Bandwidth and Accessibility Review

## Low-bandwidth architecture

The tenant application intentionally uses server-rendered Blade rather than a large client application.

Current characteristics:

- no external frontend CDN is required
- no SPA hydration/bootstrap payload is required
- navigation and CRUD pages work through normal HTML requests
- invoice/client lists are paginated
- invoice calculations are authoritative on the server
- browser JavaScript in the invoice editor is only a convenience preview
- PDFs are rendered by the server, not the user's phone/browser
- tenant media and PDFs are private rather than fetched from third-party hosts

This is appropriate for unstable or slower network conditions because the application remains usable without downloading a large JavaScript framework bundle.

## Deployment-level performance controls

Production should provide:

- HTTP/2 or HTTP/3 where supported
- gzip or Brotli for HTML/CSS/JS/text responses
- PHP OPcache
- Laravel configuration, route and view caches
- Redis or another persistent low-latency cache
- supervised asynchronous queue workers
- database indexes already defined by migrations
- application/database hosts with low network latency to each other

Do not proxy generated tenant-private PDFs through a public CDN unless the authorization model is explicitly preserved.

## Operational measurements

After deployment, record at minimum:

- p50/p95 tenant HTML response time
- p95 central commercial response time
- PDF render duration
- PDF render failure rate
- queue age/depth
- database connection errors
- backup duration and size
- tenant migration duration
- readiness failures

Use the response `X-Request-ID` to correlate application errors with support reports.

## Accessibility baseline

The workspace includes:

- semantic navigation and main content regions
- a keyboard skip-to-content link
- visible keyboard focus styling
- responsive layouts
- HTML language metadata
- automatic RTL document direction for Dari/Pashto
- LTR isolation for invoice numbers and monetary/numeric values
- text-based controls rather than image-only navigation

The PDF templates keep text selectable/searchable through Chromium where the installed fonts support the script.

## Responsive review

The workspace collapses from sidebar + content to a single-column layout on narrower screens. Tables are placed in horizontal overflow containers rather than forcing the entire page beyond the viewport.

Template gallery columns reduce progressively to one card on small screens.

## Release boundary

This review establishes the production baseline; it is not a claim of formal WCAG certification or a substitute for measurement on the actual hosting/network environment. Production monitoring should be used to set and enforce numeric performance objectives after real traffic is available.
