# Template Library — Idea

**Status:** Built (PRs #32–#47, pending merge) · **Date:** 2026-08 · **Owner:** Makdia

## Problem

Merchants installing FluentCart + Elementor start from a blank canvas. Building a
store's core pages (shop, product, cart, checkout, thank-you, account) means
hand-assembling 10+ widgets per page with sensible settings — an hour of work
that every store repeats, and most do worse than a designer would. The Divi
addon already ships designed templates; the Elementor addon shipped none.

## Goal

One-click, professionally designed store page layouts inside Elementor's own
**Insert Template → My Templates** modal — installed automatically, updated
automatically with plugin releases, never touching user-created templates.

## Scope

Eight bundled templates matching the Divi addon slug-for-slug:

| Slug | Page |
|---|---|
| `fc-shop-app` | Shop grid with filters |
| `fc-single-product` | Single product + related products |
| `fc-product-category` | Category archive (auto-scoped shop) |
| `fc-cart` | Cart page |
| `fc-checkout` | Checkout page |
| `fc-thank-you` | Order receipt / thank-you |
| `fc-customer-dashboard` | Customer account area |
| `fc-campaign-landing` | Promo landing page (6 sections) |

Plus the widgets the templates needed that didn't exist: full **Cart**,
**Order Receipt**, **Customer Dashboard**.

## Decisions of record

- **Bundled-only, no CDN** (decided 2026-08-12): templates ship inside the
  plugin ZIP; new templates arrive via plugin releases + a version bump. A CDN
  remote manifest and an in-editor browsing modal were designed and explicitly
  dropped — Elementor's native My Templates modal is the UI. Extension filters
  (`fluent_cart_elementor/template_library/templates`, `…/version`) remain if
  this ever changes.
- **Seed into Elementor's native library** (`elementor_library` CPT) rather
  than a custom post type or custom modal — zero new UI to maintain.
- **Never touch user templates**: ownership is tracked by a private meta
  marker, never by title.

## Success criteria

- All 8 templates appear in My Templates with preview thumbnails after plugin
  activation, with no user action.
- Re-activation / re-load never duplicates items; a plugin update with a
  version bump refreshes layouts in place.
- A user template named identically to ours is never modified or replaced.
