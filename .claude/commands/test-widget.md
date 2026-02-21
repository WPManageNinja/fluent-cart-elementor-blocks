# Test Widget: $ARGUMENTS

You are visually testing an Elementor widget in the browser using Playwright MCP.

**Widget slug:** `$ARGUMENTS` (e.g., `fluent_cart_product_card`, `fluent_cart_checkout`)

## Environment Setup

Check your **auto memory** for these values. If any are missing, ask the user once and store them in memory before proceeding.

| Variable | Memory key | Description |
|---|---|---|
| Base URL | `base_url` | WordPress site URL (e.g., `https://wp.test/`) |
| Admin username | `admin_user` | WordPress admin username |
| Admin password | `admin_pass` | WordPress admin password |
| Test page ID | `elementor_test_page_id` | Elementor page ID for testing |

**Derived URLs:**
- Admin URL: `{base_url}wp-admin/`
- Elementor editor URL: `{base_url}wp-admin/post.php?post={elementor_test_page_id}&action=elementor`
- Screenshots dir: `.playwright-mcp/$ARGUMENTS/`

If `elementor_test_page_id` is not in memory, ask the user whether to provide an existing page ID or create one via WP CLI (see CLAUDE.md "Creating a test page" section).

## Steps

### 1. Clean Old Screenshots

```bash
rm -f .playwright-mcp/$ARGUMENTS/*.png .playwright-mcp/$ARGUMENTS/*.jpg
mkdir -p .playwright-mcp/$ARGUMENTS
```

### 2. Login to WordPress Admin

1. Navigate to `{base_url}wp-admin/`
2. If redirected to login page, fill in credentials (`{admin_user}` / `{admin_pass}`) and submit
3. Wait for the dashboard to load

### 3. Open Elementor Editor

1. Navigate to `{base_url}wp-admin/post.php?post={elementor_test_page_id}&action=elementor`
2. Wait for the Elementor editor to fully load (look for the widget panel or canvas)
3. Take screenshot: `01-editor-loaded.png`

### 4. Add the Widget

Use `browser_run_code` to add the widget via Elementor's JS API:

```js
async (page) => {
  const frame = page.locator('#elementor-preview-iframe').contentFrame();
  await frame.evaluate((widgetType) => {
    $e.run('document/elements/create', {
      container: elementor.getPreviewContainer(),
      model: { elType: 'widget', widgetType: widgetType },
      options: { at: 0 }
    });
  }, '$ARGUMENTS');
}
```

Wait briefly for the widget to render, then take screenshot: `02-widget-added.png`

### 5. Screenshot the Widget Preview

Click on the widget in the preview iframe to select it. Take a screenshot of the preview area showing the rendered widget: `03-widget-preview.png`

### 6. Screenshot Content Controls

1. Ensure the Elementor panel is showing the widget's settings
2. The Content tab should be selected by default
3. Take screenshot of the panel: `04-content-tab.png`
4. If there are multiple content sections, scroll and take additional screenshots

### 7. Screenshot Style Controls

1. Click the "Style" tab in the Elementor panel
2. Take screenshot: `05-style-tab.png`
3. Expand each style section and screenshot if there are many controls

### 8. Check Console for Errors

Use `browser_console_messages` with level "error" to check for JavaScript errors. Note any errors related to the widget.

### 9. Generate Summary

Merge all screenshots into a **3-column grid summary image** using the shared merge script.

**First-time setup:** If `~/.claude/tools/merge-screenshots.py` or `~/.claude/tools/.venv/` doesn't exist, create them:
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

**Run the merge:**
```bash
~/.claude/tools/.venv/bin/python3 ~/.claude/tools/merge-screenshots.py .playwright-mcp/$ARGUMENTS/ \
  --notes '{"01-editor-loaded":"Pass: Editor loaded","02-widget-added":"...","03-widget-preview":"...","04-content-tab":"...","05-style-tab":"..."}' \
  --output .playwright-mcp/$ARGUMENTS/summary.png
```

Replace `...` with actual pass/fail notes based on what you observed. Keep individual screenshots too.

### 10. Report Results

Output a checklist:

```
## Test Results: $ARGUMENTS

- [ ] Editor loads successfully
- [ ] Widget can be added to page
- [ ] Widget renders in preview (not blank/error)
- [ ] Content controls load in panel
- [ ] Style controls load in panel
- [ ] No console errors related to widget

Screenshots: .playwright-mcp/$ARGUMENTS/summary.png
```

Mark each item as pass `[x]` or fail `[ ]` with details on any failures.