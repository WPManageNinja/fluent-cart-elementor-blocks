=== Elementor Blocks for FluentCart ===
Contributors: wpmanageninja
Tags: fluentcart, elementor, ecommerce, checkout, cart, shop
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Elementor Blocks for FluentCart lets you build checkout flows, cart interactions, and product layouts visually in Elementor while using FluentCart’s native commerce logic and assets.

== Description ==
Elementor Blocks for FluentCart is the add-on you need to design FluentCart checkout, cart, and product experiences directly inside Elementor without relying on theme templates or custom code.
If you are using FluentCart and Elementor and want full control over checkout layouts, add-to-cart actions, mini cart placement, product carousels, category lists, or full shop archives, this add-on provides purpose-built Elementor widgets that render FluentCart’s native output.
All widgets load FluentCart’s required CSS and JavaScript assets automatically and remain fully compatible with FluentCart products, variations, subscriptions, coupons, order bumps, taxes, and payment flows.
This add-on focuses on layout and styling control in Elementor while FluentCart continues to handle cart state, pricing rules, and checkout processing.

== Checkout & Cart Widgets ==
Add to Cart Widget
Renders FluentCart’s native add-to-cart output inside Elementor with full variation awareness.
Includes Elementor controls for button text, typography, colors, backgrounds, borders, spacing, shadows, and hover states.
Automatically loads FluentCart button assets and markup.
Buy Now Widget
Triggers FluentCart’s Buy Now action instead of adding items to the cart.
Optional modal checkout for in-context purchasing.
Shares the same styling and variation controls as the Add to Cart widget.
Mini Cart Widget
Displays the FluentCart mini cart trigger and cart drawer inside Elementor layouts.
Retrieves live cart item counts before rendering.
Style controls for icons, text, backgrounds, borders, radius, spacing, shadows, and hover states.
Checkout Widget
Embeds the full FluentCart checkout experience as an Elementor widget.
Supports one or two column layouts with adjustable widths and gaps.
Repeater-based form sections and order summary configuration.
Extensive style controls for form fields, buttons, summary boxes, coupons, payment blocks, and validation states.
Ensures FluentCart Vite assets load even without an active cart session.

== Catalog & Shop Widgets ==
Product Carousel Widget
Swiper-based product slider powered by FluentCart products.
Responsive controls for slides per view, spacing, autoplay, looping, arrows, and pagination.
Repeater-based card layout ordering and multiple price formats.
Automatically enqueues Swiper and FluentCart carousel assets.
Product Categories List Widget
Displays FluentCart product categories as a list or dropdown.
Options for product counts, hierarchy nesting, and empty category visibility.
Style controls for typography, spacing, counts, and dropdown elements.
Uses FluentCart’s lightweight Vite asset pipeline.
Products Widget
Renders a full FluentCart product table inside Elementor.
Supports grid and list layouts, pagination or infinite scroll, sorting, and filtering.
Repeater-based layout control for shop UI components.
Automatically loads FluentCart product archive CSS and JavaScript bundles.

== Features ==
Elementor widgets for FluentCart checkout, cart, and shop layouts
Variation-aware Add to Cart and Buy Now widgets
Mini cart widget with live cart state
Full FluentCart checkout embedded in Elementor
Repeater-based checkout form and order summary builder
Product carousel powered by FluentCart products
Product categories list widget with list and dropdown layouts
Full shop archive widget with filtering and pagination
Custom Elementor controls for FluentCart products and variations
Automatic loading of FluentCart CSS and JavaScript assets
Compatible with FluentCart pricing rules, coupons, subscriptions, and payments

== Requirements ==
FluentCart installed and activated
Elementor installed and activated

== Installation ==
Upload the plugin files to the /wp-content/plugins/ directory, or install the plugin through the WordPress Plugins screen.
Activate the plugin through the Plugins screen.
Ensure FluentCart and Elementor are active.
Open any page in Elementor and add FluentCart widgets from the widget panel.

== Frequently Asked Questions ==
Does this replace FluentCart’s default templates?
No. This add-on renders FluentCart’s native output inside Elementor widgets. FluentCart continues to control cart and checkout logic.
Will this work with subscriptions and variable products?
Yes. All widgets are compatible with FluentCart variations and subscription products.
Are FluentCart assets loaded automatically?
Yes. Required CSS and JavaScript assets are enqueued automatically when widgets are rendered.

== Changelog ==

= 1.0.3 (August 14, 2026) =
- Adds Template Library: 8 professionally designed store page templates (Shop, Single Product, Product Category, Cart, Checkout, Thank You, Customer Dashboard, Campaign Landing) seeded automatically into Elementor's Insert Template → My Templates modal with preview thumbnails.
- Adds Version-gated template seeding that self-heals on every admin load, never duplicates items, updates plugin-seeded layouts in place on release upgrades, and never touches user-created templates.
- Adds Cart widget: renders the full FluentCart cart (item rows, quantities, totals, empty state) with a Style tab for Item Row and Checkout Button.
- Adds Order Receipt widget: real-order editor preview (latest order or a chosen Order ID, read-only), section show/hide toggles, custom confirmation title and message, custom action button texts, and a scoped Style tab (Confirmation, Headings, Paragraph, Links, Item Table, View Order Button).
- Adds FluentCart short codes ({{order.customer.full_name}}, {{order.invoice_no}}, …) in the Order Receipt custom texts, with a {{:}} picker in the Message editor toolbar fed from core's short code registry.
- Adds Customer Dashboard widget: renders the customer account area; the editor canvas presents core's loading skeleton as a labeled layout wireframe.
- Adds Related Products widget Style tab: Heading, Grid (columns via core's CSS variable + gap), Card, Product Title, Price, and Button (covers Add To Cart, Buy Now and View Options variants).
- Adds Product Info widget style coverage: Package Description section, Stock badge controls (per-state backgrounds, padding, radius), and independent Buy Now / Add To Cart button sections.

= 1.0.2 (June 30, 2026) =
- Adds `show_thumbnail` control to the Search Bar widget.
- Adds Content controls to the Mini Cart widget.
- Adds Product loader to the ShopApp widget and improves filter label customization.
- Adds Lazy loading for Advanced Variation assets in widget rendering and editor preview.
- Fixes The Show Icon toggle in the Customer Dashboard Button widget.
- Fixes Missing CSS loading for the Customer Dashboard Button widget.
- Fixes `price_format` formatting in the ShopApp widget.
- Fixes Duplicate Select2 instances in ProductSelectControl.
- Fixes The Store Logo widget not passing dimensions to the renderer.
- Fixes Product widgets (Price, Stock, SKU, and Excerpt) not syncing with the selected variation.
- Fixes Stale stock badges for variations without stock data.
- Fixes Potential XSS in stock labels by using `textContent`.
- Fixes Empty wrapper blocks when product fields have no content.
- Removes Package type controls and output from the Product Package Description widget.
- Removes Non-functional order controls from the ShopApp widget.
- Cleans Widget labels, updates widget icons, and adds a FluentCart brand badge across all widgets.
- Appends "(FluentCart)" to widget titles for better clarity in the Elementor panel.
- Replaces The EU VAT field with a Business Details section in the Checkout widget.

= 1.0.1 (May 13, 2026) =
- Adds Product SKU widget for Elementor Theme Builder
- Adds Product Content widget for Elementor Theme Builder
- Adds Product Package Description widget for Elementor Theme Builder
- Fixes Product Excerpt widget not rendering in Theme Builder
- Adds Related Products widget for Theme Builder
- Adds Product Description widget for single product templates
- Adds Product Search Bar widget
- Adds Store Logo widget
- Adds Customer Dashboard Button widget
- Enhanced Product Info widget with SKU display and description support
- Adds Drag-and-drop reordering for Product Info widget summary sections
- Adds EU VAT section support in Checkout widget
- Improved Checkout editor preview with dummy renderer
- Fixes variation, thumbnail, and tab clicks not working when the Product Info widget is rendered inside an Elementor popup