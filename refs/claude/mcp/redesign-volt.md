# VOLT — test-site redesign, built through our own MCP

**Date:** 8 October 2026 · **Site:** elementor.junior.ninja · **Built by:** the FluentCart builder abilities

## Why redesign

The site carried the Elementor "Handmade Ceramics" kit — sage green, Nunito, soft
rounded cards. The catalog is ten sneakers. The design and the products were
telling different stories, which made it a poor demo and a poor test: a ceramics
palette hides whether our widgets are actually being styled or just inheriting.

Second reason, and the stronger one: the old kit CSS was written by an AI agent
that also set `card_elements`, and the two contradicted each other. Rebuilding is
how we prove the conflict is gone rather than papering over it.

## The design

**VOLT** — athletic retail. Near-black, warm paper, one electric accent.

| Token | Value | Role |
|---|---|---|
| Ink | `#0B0B0F` | headings, primary buttons, text on light |
| Volt | `#D9FF00` | the accent — hover fills, badges, focus rings |
| Slate | `#6E7079` | body copy |
| Flare | `#FF4D17` | sale badges only |
| Paper | `#F4F4F0` | page background |
| Concrete | `#E7E7E1` | card surface |

Type: **Archivo** 800 for headings (tight tracking, uppercase eyebrows),
**Inter** 400 for body. Radius 2px throughout — sharp, not soft.

## Two rules the old CSS broke

1. **No `order:` on card children.** Card order is `card_elements`' job. The old
   kit pinned `order: 1..4` on `.fct-product-card` children, which silently beat
   the widget setting — the bug that took three diagnoses. The new CSS sets no
   `order` anywhere; the arrangement comes from the repeater.
2. **No `[data-id="..."]` selectors.** The old kit had eleven of them, tied to
   homepage element ids. They are dead the moment the page is rebuilt, and they
   are invisible to anyone reading the design. The new CSS is semantic only.

Both are checked by `validate-storefront` after the build.

## Coverage

The redesign doubles as the end-to-end test: every widget placed, every layout
repeater configured, every builder ability called. Matrix in
[`redesign-volt-coverage.md`](redesign-volt-coverage.md).
