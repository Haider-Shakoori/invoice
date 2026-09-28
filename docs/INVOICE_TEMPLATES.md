# Built-in Invoice Templates

The supplied visual-preview PDF is the source of truth for the built-in designs.

| # | Name | Layout intent |
|---|---|---|
| 01 | Executive Navy | Navy masthead, strong metadata hierarchy, ruled items and emphasized totals |
| 02 | Minimal White | Airy monochrome layout, hairline rules, understated presentation |
| 03 | Classic Ledger | Traditional formal structure with boxed/ruled ledger treatment |
| 04 | Modern Indigo | Side-by-side information cards, indigo accents, modern total emphasis |
| 05 | Emerald Business | Green edge treatment, whitespace, compact financial summary |
| 06 | Corporate Blue | Strong blue business masthead and disciplined document grid |
| 07 | Warm Sand | Soft beige header, warm dividers, signature-focused footer |
| 08 | Charcoal Pro | Dark charcoal banner, bold number, clean lower body |
| 09 | Editorial | Large typographic title, asymmetric metadata, editorial rules |
| 10 | Compact Trade | Dense but readable trade layout for many/long lines |
| 11 | Signature | Client framing with prominent signature/stamp region |
| 12 | Borderline | Framed outline, sectional hairlines, clear subtotal box |
| 13 | Azure Wave | Curved azure top accent with a professional table |
| 14 | Slate Grid | Balanced card-like information blocks and slate treatment |
| 15 | Gold Accent | Premium letterhead with restrained gold highlights |
| 16 | Mono Statement | Black-and-white print-optimized statement layout |
| 17 | Split Header | Colored side rail, stacked identifiers, wide item table |
| 18 | Letterhead | Traditional company letterhead and footer contact composition |
| 19 | Soft Blue | Pale blue sections, rounded client block, clear totals |
| 20 | Precision | Utility design with numbered rows, decimals and notes block |

## Implemented shared behavior

Every built-in template uses the same invoice data and server totals while supporting:

- company logo or no-logo mode
- optional stamp and signature
- line and invoice discounts
- optional tax label/rate/amount
- optional additional charges
- notes/terms
- A4 Chromium/Chrome printing
- repeating table-header CSS for pagination
- AFN and USD
- English, Dari and Pashto
- RTL document composition for Dari/Pashto
- mixed Latin/Arabic-script content
- deterministic export filenames containing invoice number, version, locale and template number
- tenant-private generated PDF storage

Changing a template or export locale never alters stored invoice data, numbering or totals.

## Automated Phase 4 matrix

Phase 4 contains an automated 20 templates × 3 locales = 60-combination HTML-render matrix. It verifies:

- all twenty template keys render
- English is LTR and Dari/Pashto are RTL
- translated invoice headings are present
- mixed English/Dari/Pashto content is preserved
- invoice number and authoritative server total are present
- previewing another template/locale does not mutate invoice number, total, stored locale, selected template or version

A real Chromium smoke test also creates a PDF containing mixed English/Dari/Pashto text and validates the generated PDF header, SHA-256 and size.

## Phase 5 visual stress acceptance

Phase 5 will add visual/stress acceptance fixtures for:

- long descriptions
- two-page and five-page invoices
- logo/signature/stamp combinations
- AFN and USD
- discounts, optional tax and zero tax
- clipping/overlap checks
- production font availability and Arabic-script shaping on the deployment target
