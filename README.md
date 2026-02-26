# Elementor Blocks for FluentCart

[![Download Latest](https://img.shields.io/badge/Download-Latest-blue?style=for-the-badge&logo=github)](https://github.com/WPManageNinja/fluent-cart-elementor-blocks/releases/latest/download/fluent-cart-elementor-blocks.zip)

Elementor Blocks for FluentCart lets you build checkout flows, cart interactions, and product layouts visually in Elementor while using FluentCart's native commerce logic and assets.

## Description

Elementor Blocks for FluentCart is the add-on you need to design FluentCart checkout, cart, and product experiences directly inside Elementor without relying on theme templates or custom code.

If you are using FluentCart and Elementor and want full control over checkout layouts, add-to-cart actions, mini cart placement, product carousels, category lists, or full shop archives, this add-on provides purpose-built Elementor widgets that render FluentCart's native output.

All widgets load FluentCart's required CSS and JavaScript assets automatically and remain fully compatible with FluentCart products, variations, subscriptions, coupons, order bumps, taxes, and payment flows.

This add-on focuses on layout and styling control in Elementor while FluentCart continues to handle cart state, pricing rules, and checkout processing.

## Features

- ✅ **Elementor widgets for FluentCart checkout, cart, and shop layouts**
- ✅ **Variation-aware Add to Cart and Buy Now widgets**
- ✅ **Mini cart widget with live cart state**
- ✅ **Full FluentCart checkout embedded in Elementor**
- ✅ **Repeater-based checkout form and order summary builder**
- ✅ **Product carousel powered by FluentCart products**
- ✅ **Product categories list widget with list and dropdown layouts**
- ✅ **Full shop archive widget with filtering and pagination**
- ✅ **Custom Elementor controls for FluentCart products and variations**
- ✅ **Automatic loading of FluentCart CSS and JavaScript assets**
- ✅ **Compatible with FluentCart pricing rules, coupons, subscriptions, and payments**

## Checkout & Cart Widgets

### Add to Cart Widget
Renders FluentCart's native add-to-cart output inside Elementor with full variation awareness. Includes Elementor controls for button text, typography, colors, backgrounds, borders, spacing, shadows, and hover states. Automatically loads FluentCart button assets and markup.

### Buy Now Widget
Triggers FluentCart's Buy Now action instead of adding items to the cart. Optional modal checkout for in-context purchasing. Shares the same styling and variation controls as the Add to Cart widget.

### Mini Cart Widget
Displays the FluentCart mini cart trigger and cart drawer inside Elementor layouts. Retrieves live cart item counts before rendering. Style controls for icons, text, backgrounds, borders, radius, spacing, shadows, and hover states.

### Checkout Widget
Embeds the full FluentCart checkout experience as an Elementor widget. Supports one or two column layouts with adjustable widths and gaps. Repeater-based form sections and order summary configuration. Extensive style controls for form fields, buttons, summary boxes, coupons, payment blocks, and validation states. Ensures FluentCart Vite assets load even without an active cart session.

## Catalog & Shop Widgets

### Product Carousel Widget
Swiper-based product slider powered by FluentCart products. Responsive controls for slides per view, spacing, autoplay, looping, arrows, and pagination. Repeater-based card layout ordering and multiple price formats. Automatically enqueues Swiper and FluentCart carousel assets.

### Product Categories List Widget
Displays FluentCart product categories as a list or dropdown. Options for product counts, hierarchy nesting, and empty category visibility. Style controls for typography, spacing, counts, and dropdown elements. Uses FluentCart's lightweight Vite asset pipeline.

### Products Widget
Renders a full FluentCart product table inside Elementor. Supports grid and list layouts, pagination or infinite scroll, sorting, and filtering. Repeater-based layout control for shop UI components. Automatically loads FluentCart product archive CSS and JavaScript bundles.

## Installation

### Prerequisites

- WordPress 6.0 or higher
- PHP 7.4 or higher
- [FluentCart](https://wordpress.org/plugins/fluent-cart/) plugin installed and activated
- [Elementor](https://wordpress.org/plugins/elementor/) installed and activated

### Install & Activate

1. **Download the Plugin**
   - Visit the [latest release](https://github.com/WPManageNinja/fluent-cart-elementor-blocks/releases/latest)
   - Download the `Source code (zip)` file

2. **Upload to WordPress**
   - Go to your WordPress admin dashboard
   - Navigate to **Plugins > Add New**
   - Click **Upload Plugin**
   - Select the downloaded zip file and click **Install Now**

3. **Activate the Plugin**
   - After installation, click **Activate Plugin**
   - Alternatively, go to **Plugins** and click "Activate" below the plugin name

4. **Start Building**
   - Open any page in Elementor
   - Add FluentCart widgets from the widget panel
   - Design your checkout, cart, and shop layouts visually

## Updates

To update the Elementor Blocks for FluentCart addon:

1. **Check for Updates**
   - Watch the [GitHub repository](https://github.com/WPManageNinja/fluent-cart-elementor-blocks) for new releases
   - Check the [Releases page](https://github.com/WPManageNinja/fluent-cart-elementor-blocks/releases) for the latest version

2. **Download the New Version**
   - Visit the [latest release](https://github.com/WPManageNinja/fluent-cart-elementor-blocks/releases/latest)
   - Download the `Source code (zip)` file

3. **Install the Update**
   - Go to **Plugins > Add New > Upload Plugin**
   - Upload the new zip file
   - WordPress will automatically replace the old version with the new one
   - Reactivate the plugin if prompted

> **Note:** Since this addon is distributed via GitHub releases (not the WordPress Plugin Directory), updates must be installed manually using the steps above.

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- FluentCart plugin installed and activated
- Elementor plugin installed and activated

## Frequently Asked Questions

### Does this replace FluentCart's default templates?

No. This add-on renders FluentCart's native output inside Elementor widgets. FluentCart continues to control cart and checkout logic.

### Will this work with subscriptions and variable products?

Yes. All widgets are compatible with FluentCart variations and subscription products.

### Are FluentCart assets loaded automatically?

Yes. Required CSS and JavaScript assets are enqueued automatically when widgets are rendered.

## Changelog

### 1.0.0
- Initial release
- Checkout & Cart Widgets (Add to Cart, Buy Now, Mini Cart, Checkout)
- Catalog & Shop Widgets (Product Carousel, Categories List, Products)
- Automatic asset loading
- Full FluentCart compatibility

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/WPManageNinja/fluent-cart-elementor-blocks).

## License

GPLv2 or later. See LICENSE file for details.
