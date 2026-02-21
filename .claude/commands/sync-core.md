# Sync with FluentCart Core

Compare FluentCart core's capabilities with what this Elementor add-on plugin currently provides. Identify gaps and ask the user which missing items to scaffold.

## Core Plugin Path

Read `CLAUDE.md` for the core plugin path (under "FluentCart Core Plugin"). Default: sibling directory `fluent-cart/` relative to this plugin.

## Steps

### 1. Inventory Core Shortcodes & Renderers

Read the core plugin's shortcode handlers and renderers:

**Shortcode handlers** — scan `{core_path}/app/Hooks/Handlers/ShortCodes/`:
- List every `*Handler.php` and `*ShortCode.php` / `*Shortcode.php` file
- For each, note the shortcode tag (the `SHORT_CODE` constant or `add_shortcode()` call)
- Categorize: product display, cart/checkout, customer, utility

**Renderers** — scan `{core_path}/app/Services/Renderer/`:
- List every renderer class
- Note which shortcode handler uses which renderer

**Bricks elements** — scan `{core_path}/app/Modules/Templating/Bricks/Elements/`:
- List all Bricks elements (these represent features core considers important enough for builder integration)

**AssetLoader methods** — read `{core_path}/app/Modules/Templating/AssetLoader.php`:
- List all public static methods (these are the available asset bundles)

### 2. Inventory This Plugin's Widgets

Scan `app/Modules/Integrations/Elementor/Widgets/`:
- List all general widgets and their slugs (`get_name()` return value)
- List all Theme Builder widgets in `Widgets/ThemeBuilder/`
- Note which core shortcode/renderer each widget wraps

### 3. Build the Gap Report

Create a comparison table. For each core shortcode/renderer, determine:

- **Covered** — This plugin has a matching Elementor widget
- **Missing** — Core has it, this plugin doesn't
- **N/A** — Not applicable for Elementor (e.g., receipt pages, customer login/registration that are full-page flows)

**Known mappings (current state):**

| Core Shortcode | Core Renderer | Elementor Widget | Status |
|---|---|---|---|
| `fluent_cart_products` (ShopAppHandler) | ShopAppRenderer | ShopAppWidget | Covered |
| ProductCardShortCode | ProductCardRender | ProductCardWidget | Covered |
| ProductCarouselShortCode | ProductCardRender (loop) | ProductCarouselWidget | Covered |
| ProductCategoriesListShortcode | ProductCategoriesListRenderer | ProductCategoriesListWidget | Covered |
| `fluent_cart_mini_cart` (MiniCartShortcode) | MiniCartRenderer | MiniCartWidget | Covered |
| `fluent_cart_add_to_cart` (AddToCartShortcode) | — | AddToCartWidget | Covered |
| `fluent_cart_direct_checkout` (DirectCheckoutShortcode) | — | BuyNowWidget | Covered |
| CheckoutPageHandler | CheckoutRenderer | CheckoutWidget | Covered |
| `fluent_cart_cart` (CartShortcode) | CartRenderer | — | **Check** |
| PricingTableShortCode | PricingTableRenderer | — | **Check** |
| SearchBarShortCode | SearchBarRenderer | — | **Check** |
| StoreLogoShortCode | StoreLogoRenderer | — | **Check** |
| CustomerDashboardButtonShortcode | CustomerDashboardButtonRenderer | — | **Check** |
| ProductImageShortCode | — | — | **Check** (may overlap with Theme Builder) |
| ProductTitleShortCode | — | — | **Check** (may overlap with Theme Builder) |
| SingleProductShortCode | ProductRenderer | — | **Check** |
| CustomerLoginHandler | — | — | **Check** |
| CustomerProfileHandler | — | — | **Check** |
| CustomerRegistrationHandler | — | — | **Check** |
| ReceiptHandler | ReceiptRenderer | — | **Check** |
| CheckoutShippingMethodsShortCode | ShippingMethodsRender | — | **Check** |

Also check for any NEW shortcodes/renderers/Bricks elements added to core since the last sync — files not in the table above.

### 4. Present Findings to User

Output the gap report as a clean table with these columns:
- Core Feature
- Core Handler/Renderer
- This Plugin
- Status (Covered / Missing / N/A)
- Notes

For items marked **Missing**, add a recommendation:
- **Recommended** — Core has both shortcode + renderer + Bricks element → strong signal this should be an Elementor widget too
- **Consider** — Core has shortcode + renderer but no Bricks element → might be useful
- **Skip** — Full-page flows (login, registration, profile, receipt) that don't make sense as drag-and-drop widgets

### 5. Ask User What to Scaffold

Present the **Missing + Recommended** items and ask the user which ones to create. For each selected item, output:
- Suggested widget name (e.g., `CartWidget`, `PricingTableWidget`)
- Suggested slug (e.g., `fluent_cart_cart`, `fluent_cart_pricing_table`)
- Which core renderer/shortcode it should wrap
- Which `AssetLoader::*` method to call

Do NOT scaffold the widgets in this agent — just produce the plan. The user can then use `/scaffold-widget WidgetName` for each one they approve.

### 6. Check for Core Changes Since Last Sync

Also look for:
- New files in core's `ShortCodes/` or `Renderer/` or `Bricks/Elements/` that don't appear in the mapping above
- New public methods in `AssetLoader.php` not documented in `refs/claude/` files
- New taxonomies or post types

Report any new discoveries so the user is aware of core evolution.