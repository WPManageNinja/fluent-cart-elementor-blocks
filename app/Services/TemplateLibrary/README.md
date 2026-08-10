# Template Library

Bundled, ready-made Elementor page templates that FluentCart seeds into
Elementor's own library (the **Insert Template → My Templates** modal), so a
user can drop a fully-designed store page onto a blank canvas in one click.

This directory holds only the **template content** and its metadata. The PHP
that reads this folder and seeds it into Elementor (manifest loader, seeder,
version-gated orchestrator) lands in follow-up PRs — this PR is the storage
scaffold only.

## Folder layout

```
TemplateLibrary/
├── README.md                 ← you are here
└── templates/
    ├── manifest.json         ← the index: a JSON array of template slugs
    └── <slug>/               ← one self-contained folder per template
        ├── manifest.json     ← template metadata (title, type, version, preview)
        ├── template.json     ← the Elementor export payload (element tree)
        └── preview.webp       ← library-card preview image (added when authored)
```

Keeping each template in its own folder — metadata, element tree, and preview
together — makes every template easy to edit, review, and diff on its own, and
guarantees the preview ships. Everything under `app/` is in the release ZIP
whitelist (`resources/` is not), so the preview lives **inside the template
folder**, not under `resources/images/`.

## `templates/manifest.json` (the index)

A flat JSON array of the slugs to seed. The loader reads this to discover the
folders — a slug listed here **must** have a matching `<slug>/` folder.

```json
["fc-shop-app", "fc-single-product", "..."]
```

## `<slug>/manifest.json` (per-template metadata)

| Field              | Meaning                                                                 |
|--------------------|-------------------------------------------------------------------------|
| `slug`             | Must equal the folder name. Ownership marker — the seeder matches its own library posts by this, never by title, so a user's own templates are never touched. |
| `title`            | Shown on the library card and as the seeded post title.                 |
| `type`             | Elementor template type → the `elementor_library_type` term. `page` for full pages. |
| `category`         | Branded grouping label — `FluentCart` for all bundled templates, so they group under one filter (the optional `elementor_library_category` term). The specific page is carried by `title`. |
| `template_version` | Bump this when the layout changes so installed copies re-seed.          |
| `preview`          | Bare WebP filename inside this template's own folder (e.g. `preview.webp`). |

## `<slug>/template.json` (the payload)

The **Elementor export format** — exactly what "Export Template" produces in the
Elementor editor:

```json
{
  "version": "0.4",
  "title": "FluentCart — Shop",
  "type": "page",
  "content": [ /* element tree: sections → columns/containers → widgets */ ],
  "page_settings": []
}
```

The seeder feeds this file to Elementor's own `import_template()`, which
remaps bundled image references and writes the correctly-slashed
`_elementor_data` — we never hand-assemble that meta.

## Authoring a template (workflow)

1. Build the layout on a blank page in the Elementor editor using the FluentCart
   widgets (Shop, Checkout, Cart, Receipt, Customer Dashboard, etc.).
2. **Export Template** → download the `.json`.
3. Save it as `templates/<slug>/template.json` and fill in `<slug>/manifest.json`.
4. Add the slug to `templates/manifest.json`.
5. Capture a preview screenshot, convert to WebP, save it as
   `templates/<slug>/preview.webp`.

> The `template.json` files in this scaffold are **placeholders** (empty
> `content`) — real layouts are authored per page in follow-up PRs.
