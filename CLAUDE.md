# Claude Code Instructions — FluentCart Elementor Blocks

## How This Plugin Works

This is a **companion/add-on plugin** for [FluentCart](https://fluentcart.com). It adds Elementor page builder widgets so users can drag-and-drop FluentCart elements (shop grids, product cards, carousels, checkout, mini cart, etc.) into their pages.

### No Admin Menu
This plugin has **no admin menu, settings page, or UI of its own**. It registers Elementor widgets — all configuration happens inside the Elementor editor. The plugin activates silently and waits for the `fluentcart_loaded` hook before bootstrapping (see `boot/app.php`), so **FluentCart core must be active** for anything to work.

### Plugin Bootstrap Flow
1. `fluentcart-elementor-blocks.php` → loads Composer autoloader → calls `boot/app.php`
2. `boot/app.php` → listens for `fluentcart_loaded` action → creates `Application` instance
3. `Application` → loads config, hooks, bindings, REST routes
4. `app/Hooks/actions.php` → instantiates `ElementorIntegration` which registers all widgets, controls, categories, and Theme Builder support

### Elementor Integration (`ElementorIntegration::register()`)
- Registers a **"FluentCart" widget category** in Elementor
- Registers **8 general widgets**: AddToCart, BuyNow, MiniCart, ShopApp, ProductCard, ProductCarousel, ProductCategoriesList, Checkout
- Registers **8 Theme Builder widgets** (requires Elementor Pro): ProductTitle, ProductGallery, ProductPrice, ProductStock, ProductExcerpt, ProductBuySection, ProductInfo, RelatedProducts
- Registers custom controls: ProductSelectControl, ProductVariationSelectControl
- Integrates with Elementor Pro Theme Builder (custom document types, conditions for `fluent-products` post type)

## Build & Dev Commands

| Command | What it does |
|---|---|
| `npm run dev` | Starts Vite dev server (port 4230) with HMR. Switches `Vite.php` enqueuer to DEVELOPMENT_MODE |
| `npm run build` | Production Vite build → outputs to `assets/`. Switches `Vite.php` to PRODUCTION_MODE. Generates `config/vite_config.php` with manifest |
| `npm run build:zip` | Runs `npm run build` then `npm run pack` — builds assets and creates a distributable ZIP |
| `npm run pack` / `npm run zip` | Runs `resources/dev/build.sh` — creates `builds/fluent-cart-elementor-block.zip` with whitelisted files only |

### How `build:zip` Works
1. **`npm run build`** — Runs `resources/vite/vite.js --build` (switches Vite.php to production mode) then `vite build` (compiles JS from `resources/elementor/` → `assets/`, copies images, generates manifest → `config/vite_config.php`)
2. **`npm run pack`** — Runs `resources/dev/zip.js` which spawns `resources/dev/build.sh`:
   - **Whitelist approach**: Only includes `app/`, `assets/`, `boot/`, `config/`, `database/`, `language/`, `vendor/`, `fluent-cart-elementor-blocks.php`, `readme.txt`, `composer.json`, `index.php`
   - Excludes `.DS_Store`, `.git*` files
   - By default excludes `fakerphp` and `FakerRoutes.php` (pass `--faker` flag to include them)
   - Output: `builds/fluent-cart-elementor-block.zip` — ready for WordPress plugin upload
   - The ZIP preserves the folder name as the root directory for correct WordPress installation

### Vite Asset Pipeline
- Entry points: `resources/elementor/*.js` (product-variation-select-control, product-carousel-elementor, product-select-control)
- Output dir: `assets/`
- Dev server: `localhost:4230`
- `resources/vite/vite.js` toggles `DEVELOPMENT_MODE`/`PRODUCTION_MODE` in `app/Utils/Enqueuer/Vite.php` before each build/dev run
- On build, manifest is moved from `assets/.vite/manifest.json` to `assets/manifest.json` AND converted to a PHP array in `config/vite_config.php`

## Browser Testing (Playwright MCP)

When testing widgets in the browser, check your **auto memory** for `base_url`, `admin_user`, and `admin_pass`. If not set, ask the user once and store them in memory.

### Environment

| Item | How to get |
|---|---|
| Base URL | From memory (`base_url`). Ask user if missing. |
| Admin URL | `{base_url}wp-admin/` |
| Admin credentials | From memory (`admin_user` / `admin_pass`). Ask user if missing. |
| Test page (Elementor) | From memory (`elementor_test_page_id`). If missing, ask user: provide an existing post ID **or** let Claude create one (see below). Store the ID in memory. |
| Elementor editor URL | `{base_url}wp-admin/post.php?post={elementor_test_page_id}&action=elementor` |
| Screenshots dir | `.playwright-mcp/` (inside project root, use subdirs per test) |

### Testing Workflow

1. **Login** — Navigate to `{base_url}wp-admin/`. If redirected to login, fill credentials from memory and submit.
2. **Open Elementor editor** — Navigate to the Elementor editor URL above. Wait for the editor to load (look for widget panel or "Edit" heading).
3. **Add a widget** — Use Elementor JS API via `browser_evaluate`:
   ```js
   async (page) => {
     const frame = page.locator('#elementor-preview-iframe').contentFrame();
     await frame.evaluate(() => {
       $e.run('document/elements/create', {
         container: elementor.getPreviewContainer(),
         model: { elType: 'widget', widgetType: 'WIDGET_NAME_HERE' },
         options: { at: 0 }
       });
     });
   }
   ```
4. **Select a widget** — Click on the widget in the preview iframe, or click its name in the Structure panel.
5. **Clean old screenshots** — Before taking new screenshots, remove old files from the test directory:
   ```bash
   rm -f .playwright-mcp/<test-name>/*.png .playwright-mcp/<test-name>/*.jpg
   ```
6. **Take screenshots** — Save to `.playwright-mcp/<test-name>/` with number-prefixed filenames (e.g. `01-name.png`).
7. **After testing** — Merge all screenshots into a single summary image (see below).

### Merging Screenshots After Testing

After capturing individual screenshots, combine them into a **3-column grid summary image** using the shared merge script at `~/.claude/tools/merge-screenshots.py`.

**First-time setup**: If `~/.claude/tools/merge-screenshots.py` or `~/.claude/tools/.venv/` doesn't exist, create them:
```bash
python3 -m venv ~/.claude/tools/.venv
~/.claude/tools/.venv/bin/pip install Pillow
```
Then create `~/.claude/tools/merge-screenshots.py` — a Pillow script that:
- Takes a directory of screenshots and produces a 3-column grid summary
- Only includes files starting with a digit (e.g. `01-name.png`), skips old summaries and non-numbered files
- Extracts titles from filenames (strips number prefix, converts hyphens/underscores to spaces)
- Accepts `--notes '{"stem":"Pass: text"}'` for status annotations below each image
- Draws a green dot for `Pass:`, red dot for `Fail:`/`Issue:` prefixed notes
- Supports `--cols N` (default 3), `--width N` (default 600), `--output path`

**Usage**:
```bash
~/.claude/tools/.venv/bin/python3 ~/.claude/tools/merge-screenshots.py .playwright-mcp/<test-name>/ \
  --notes '{"01-filename-stem":"Pass: description","02-another":"Fail: what went wrong"}'
```

Save the final summary as `.playwright-mcp/<test-name>/summary.png`. Keep individual screenshots too.

### Creating a test page

If `elementor_test_page_id` is not in memory, ask the user:
- **Option A:** "Provide an existing page ID" — user gives you a post ID, store it in memory.
- **Option B:** "Create one for me" — create via WP CLI and store the new ID in memory:

```bash
wp post create --post_title="Claude Elementor Page" --post_status=publish --post_type=page --porcelain
# Then set Elementor meta with the returned ID:
wp post meta update <ID> _elementor_edit_mode builder
wp post meta update <ID> _elementor_data '[]'
wp post meta update <ID> _wp_page_template 'elementor_canvas'
```

## Before Starting Any Task

Always check `refs/claude/` first — it contains implementation plans, style control references, and architectural notes for existing widgets.

## Building a New Widget

1. **Start by reading** `refs/claude/WIDGET-REFERENCE-GUIDE.md` — it picks 2 reference widgets (ProductCardWidget + ProductCarouselWidget) that collectively cover nearly every pattern in the codebase.
2. Read the actual widget source files referenced in the guide before writing any code.
3. After shipping a new widget, evaluate if it should replace one of the reference picks (checklist is in the guide).

## Available Agents (Auto-Spawn)

When the situation matches a trigger below, automatically spawn the appropriate agent using the **Task tool**. Read the corresponding `.claude/commands/*.md` file and use its contents as the Task prompt, replacing `$ARGUMENTS` with the relevant value.

| Trigger | Agent | Command file | Arguments |
|---|---|---|---|
| User asks to create/scaffold a new widget | scaffold-widget | `.claude/commands/scaffold-widget.md` | Widget name (e.g., `ProductTabs`) |
| User asks to test a widget visually in browser | test-widget | `.claude/commands/test-widget.md` | Widget slug (e.g., `fluent_cart_product_card`) |
| Before shipping widget changes, or user asks for review | review-widget | `.claude/commands/review-widget.md` | Widget name or file path |
| User asks to build assets or create a ZIP | build | `.claude/commands/build.md` | Flags (e.g., `--zip`) or empty |

**How to spawn:** Read the command file, substitute `$ARGUMENTS`, then pass the full content as a Task tool prompt with `subagent_type: "general-purpose"`.

**When to auto-spawn without being asked:**
- **review-widget** — After completing a new widget or significant widget modifications, proactively run a review before telling the user you're done
- **build** — After changes that affect compiled assets (JS files in `resources/elementor/`), proactively verify the build still works

## Key Reference Files

| Topic | File |
|---|---|
| Widget patterns index | `refs/claude/WIDGET-REFERENCE-GUIDE.md` |
| Checkout architecture | `refs/claude/Checkout/IMPLEMENTATION-PLAN.md` |
| Checkout style controls | `refs/claude/Checkout/STYLE-CONTROLS.md` |
| Shop/Products style controls | `refs/claude/shopapp/STYLE-CONTROLS.md` |
| Shop layout repeater + AJAX | `refs/claude/shopapp/reorderable-shop-layout.md` |
| Shared card style methods | `refs/claude/ProductCard/STYLE-CONTROLS.md` |
| Carousel style controls | `refs/claude/ProductCarousel/STYLE-CONTROLS.md` |
| Categories list controls | `refs/claude/ProductCategoriesList/STYLE-CONTROLS.md` |
| Add to Cart controls | `refs/claude/AddToCart/STYLE-CONTROLS.md` |
| Buy Now controls | `refs/claude/BuyNow/STYLE-CONTROLS.md` |
| Mini Cart controls | `refs/claude/MiniCart/STYLE-CONTROLS.md` |
| Theme Builder overview | `refs/claude/ThemeBuilder/STYLE-CONTROLS.md` |
| Theme Builder integration plan | `refs/claude/theme-builder-integration-plan.md` |
| Elementor controls (advanced) | `refs/claude/elementor/controls-reference.md` |
| Elementor hooks (full reference) | `refs/claude/elementor/hooks-reference.md` |
