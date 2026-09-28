# Phase 4 — Localization & PDF Status

Phase 4 provides the multilingual presentation and document-export layer for Invoice Drafts SaaS.

## Tenant interface localization

The tenant interface now supports:

- English (`en`)
- Dari (`fa`)
- Pashto (`ps`)

The selected application language is stored in the tenant session. Dari and Pashto render the workspace with RTL document direction while numeric values remain isolated LTR where needed.

The application locale and an invoice's document/export locale are independent. Changing the UI language does not rewrite an invoice. Previewing/exporting another document language also does not mutate the invoice.

## Twenty built-in designs

The supplied 20-page visual-preview PDF is the design source. The application maps the twenty designs to distinct template families rather than treating them as color-only variants.

Implemented templates:

1. Executive Navy
2. Minimal White
3. Classic Ledger
4. Modern Indigo
5. Emerald Business
6. Corporate Blue
7. Warm Sand
8. Charcoal Pro
9. Editorial
10. Compact Trade
11. Signature
12. Borderline
13. Azure Wave
14. Slate Grid
15. Gold Accent
16. Mono Statement
17. Split Header
18. Letterhead
19. Soft Blue
20. Precision

A tenant template gallery exposes the full catalog and can preview a selected invoice using any design.

## PDF rendering

PDF generation uses installed Chromium/Chrome rather than a rasterized screenshot. The generated PDF retains normal browser text layout and mixed-script Unicode content.

Configuration:

- `PDF_CHROMIUM_BINARY` — optional explicit Chrome/Chromium path
- `PDF_TIMEOUT_SECONDS` — render timeout
- `PDF_FONT_FAMILY` — LTR font stack
- `PDF_RTL_FONT_FAMILY` — Dari/Pashto font stack

The renderer checks common Linux Chrome/Chromium locations when an explicit path is not configured.

## Private exports

Generated PDFs are stored on tenant-scoped private storage. Each export records:

- invoice
- invoice version in metadata
- selected template
- selected locale
- filename
- SHA-256
- byte size
- exporting user
- export timestamp

Authorized users can re-download prior exports. Export activity is also added to the invoice activity stream.

## Company branding

Company settings support tenant-private:

- logo
- signature image
- stamp image

Branding files use unique filenames. Replacing a current branding asset does not delete the old file so historical invoice snapshots can still resolve the asset path they captured.

Company settings remain editable after onboarding but are protected by the `settings.manage` permission.

## Invoice presentation fields

The Phase 4 invoice editor supports:

- AFN and USD
- line discounts
- invoice-level percent/fixed discounts
- optional additional-charge label/amount
- optional tax label/rate
- notes and terms

The browser shows live totals for convenience, but the server remains authoritative. The server computes tax after invoice discount and additional charges.

## Automated acceptance

Final Phase 4 quality run:

- security audit: passed
- Pint style: passed
- PHPUnit quality suite: 29 passed, 521 assertions
- Chromium mixed-script PDF smoke test: passed
- 20 × 3 render matrix: 60 combinations passed
- MySQL tenant-isolation suite: 6 passed, 33 assertions

The dedicated MySQL suite verifies separate tenant databases/storage/session boundaries independently of the SQLite/general quality suite.

## Remaining release work

Phase 5 handles production-readiness work that should not be conflated with the functional Phase 4 renderer:

- visual stress fixtures for long/multi-page invoices
- deployment-host Chromium and Arabic font verification
- backup/restore drill
- security/authorization review
- performance and low-bandwidth profiling
- accessibility/responsive review
- observability and deployment checklist
