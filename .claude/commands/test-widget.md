# Test Widget: $ARGUMENTS

You are visually testing an Elementor widget in the browser using Playwright MCP. Your job is to **thoroughly test every aspect** of the widget: default render, all content controls, all style sections, responsive behavior, and console errors.

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

---

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

### 5. Screenshot Default Widget Preview

Click on the widget in the preview iframe to select it. Take a screenshot showing the rendered widget in its default state: `03-default-preview.png`

---

### 6. Test Content Tab (All Sections)

The Content tab should be active by default when the widget is selected.

**Goal:** Open every content section, screenshot each one individually.

1. Take a snapshot of the Elementor panel to identify all content sections
2. For each content section visible in the panel:
   a. Click the section header to expand it (if collapsed)
   b. Take a screenshot named `04-content-{section-name}.png` (e.g., `04-content-product.png`, `04-content-card-layout.png`)
   c. If a section has many controls that require scrolling, scroll and take additional screenshots
   d. Note what controls are present in each section
3. If there are interactive content controls (like product selectors, repeaters, toggles):
   a. Try toggling a switcher or changing a select dropdown
   b. Check that the preview updates accordingly
   c. Screenshot any interesting state changes

**Numbering:** Use `04-content-*` prefix for all content tab screenshots. Increment the number for each screenshot.

---

### 7. Test Style Tab (All Sections — THOROUGH)

This is the most important part. Every style section must be individually tested.

1. **Click the "Style" tab** in the Elementor panel
2. Take a snapshot to identify all style sections
3. Take screenshot: `10-style-tab-overview.png`

**For each style section:**

4. Click the section header to expand it
5. Take a screenshot of the expanded section showing all its controls: `11-style-{section-name}.png`
6. Note the types of controls present:
   - Color pickers
   - Typography controls
   - Dimensions (padding/margin/border-radius)
   - Sliders
   - Border controls
   - Box shadow controls
   - Background controls
   - Normal/Hover tabs
7. If the section has **Normal/Hover tabs**:
   a. Screenshot the Normal tab controls
   b. Click the Hover tab and screenshot those controls too: `11-style-{section-name}-hover.png`
8. Collapse the section before moving to the next one

**Numbering:** Use `11-style-*` through `19-style-*` for style screenshots. Increment for each screenshot.

### 8. Test Style Changes (Visual Verification)

Pick **2-3 representative style controls** and actually modify them to verify they apply visually:

1. **Test a color change:**
   - Find a color control (e.g., background color, text color, button color)
   - Change it to a distinctive color (e.g., bright red `#FF0000` or bright blue `#0000FF`)
   - Screenshot the preview to verify the color applied: `20-style-test-color.png`

2. **Test a spacing/dimension change:**
   - Find a padding, margin, or border-radius control
   - Change it to a noticeable value (e.g., padding 30px, border-radius 20px)
   - Screenshot the preview to verify: `21-style-test-spacing.png`

3. **Test typography (if available):**
   - Find a typography group control
   - Change font size to something noticeably different (e.g., 28px)
   - Screenshot the preview to verify: `22-style-test-typography.png`

After testing, undo changes (Ctrl+Z multiple times) or reset controls to defaults.

---

### 9. Test Advanced Tab

1. Click the "Advanced" tab in the Elementor panel
2. Take a quick screenshot: `25-advanced-tab.png`
3. Verify the standard Elementor advanced sections are present (Margin/Padding, Motion Effects, Responsive, etc.)
4. No need to test each section — just confirm the tab loads without errors

---

### 10. Test Responsive Modes

1. Click the responsive mode switcher in the Elementor bottom bar (or use the viewport icons)
2. Switch to **Tablet** view
3. Screenshot the widget in tablet mode: `26-responsive-tablet.png`
4. Switch to **Mobile** view
5. Screenshot the widget in mobile mode: `27-responsive-mobile.png`
6. Switch back to **Desktop** view
7. Note if the widget layout adapts properly to smaller viewports

---

### 11. Check Console for Errors

Use `browser_console_messages` with level "error" to check for JavaScript errors. Note any errors related to the widget. Also check level "warning" for relevant warnings.

---

### 12. Generate Summary

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
  --notes '{...actual notes based on observations...}' \
  --output .playwright-mcp/$ARGUMENTS/summary.png
```

Replace notes with actual pass/fail observations. Keep individual screenshots too.

---

### 13. Report Results

Output a comprehensive checklist:

```
## Test Results: $ARGUMENTS

### Setup & Rendering
- [ ] Editor loads successfully
- [ ] Widget can be added to page
- [ ] Widget renders in preview (not blank/error)

### Content Tab
- [ ] All content sections expand without errors
- [ ] Content controls are present and functional
- [ ] Interactive controls (selectors, toggles, repeaters) work
- [ ] Preview updates when content settings change

### Style Tab
- [ ] Style tab loads with all expected sections
- [ ] All style sections expand without errors
- [ ] Normal/Hover tabs present where expected
- [ ] Color controls listed: [list them]
- [ ] Typography controls listed: [list them]
- [ ] Spacing/dimension controls listed: [list them]
- [ ] Border/shadow controls listed: [list them]

### Style Application (Visual)
- [ ] Color changes apply visually in preview
- [ ] Spacing/dimension changes apply visually
- [ ] Typography changes apply visually

### Advanced & Responsive
- [ ] Advanced tab loads without errors
- [ ] Widget responds to viewport changes (tablet/mobile)

### Console
- [ ] No widget-related JavaScript errors
- [ ] No widget-related warnings

### Style Sections Found
List every style section name found in the panel:
1. ...
2. ...

Screenshots: .playwright-mcp/$ARGUMENTS/summary.png
```

Mark each item as pass `[x]` or fail `[ ]` with details on any failures. For the style sections list, enumerate every section name you found — this helps verify completeness against the widget's STYLE-CONTROLS.md reference doc.