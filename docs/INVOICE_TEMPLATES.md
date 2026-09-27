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

## Shared requirements

Every template must support:
- company logo or no-logo mode
- optional stamp and signature
- optional line/invoice discounts
- optional tax label/rate/amount
- optional additional charges
- notes/terms
- A4 printing
- multi-page invoices
- repeating table headers
- page numbers
- AFN and USD
- English, Dari and Pashto
- proper RTL composition for Dari/Pashto, not simple text alignment
- mixed Latin/Arabic-script content
- predictable filenames such as `INV-2026-0001-ps.pdf`

Changing a template or export locale must never alter stored invoice data, numbering or totals.

## Validation matrix

Release acceptance requires 20 templates × 3 document locales = 60 combinations, with fixtures covering:
- mixed Pashto/Dari/English data
- long descriptions
- logo/signature
- two-page and five-page invoices
- AFN and USD
- discount
- optional tax and zero tax
- no clipped/overlapping content
- unchanged document number
- matching server totals
