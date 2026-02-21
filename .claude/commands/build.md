# Build: $ARGUMENTS

You are running the build pipeline for the FluentCart Elementor Blocks plugin and validating the output.

**Arguments:** `$ARGUMENTS` (e.g., `--zip`, or empty for standard build)

## Steps

### 1. Determine Build Type

- If `$ARGUMENTS` contains `--zip`: run `npm run build:zip` (build + create distributable ZIP)
- Otherwise: run `npm run build` (production build only)

### 2. Run the Build

Execute the appropriate npm command from the project root:

```bash
# Standard build:
npm run build

# OR with --zip flag:
npm run build:zip
```

Capture the full output. If the build fails (non-zero exit code), report the error immediately and stop.

### 3. Validate Standard Build Output

Check all of these:

- [ ] **Build exit code** — Command exited with code 0
- [ ] **Assets directory** — `assets/` directory exists and contains fresh files (check modification times)
- [ ] **Manifest file** — `assets/manifest.json` exists
- [ ] **Vite config** — `config/vite_config.php` was regenerated (check modification time is recent)
- [ ] **Vite mode** — `app/Utils/Enqueuer/Vite.php` contains `PRODUCTION_MODE` (not `DEVELOPMENT_MODE`)

Validation commands:
```bash
# Check assets exist
ls -la assets/

# Check manifest
ls -la assets/manifest.json

# Check vite config was regenerated
ls -la config/vite_config.php

# Check production mode
grep -c "PRODUCTION_MODE" app/Utils/Enqueuer/Vite.php
```

### 4. Validate ZIP Output (only if --zip)

Additional checks when `--zip` flag was used:

- [ ] **ZIP exists** — `builds/fluent-cart-elementor-block.zip` exists
- [ ] **ZIP contents** — Contains expected directories: `app/`, `assets/`, `boot/`, `config/`, `vendor/`
- [ ] **ZIP excludes** — Does NOT contain `.git`, `node_modules`, `.claude`, `refs/`, `resources/`
- [ ] **Main plugin file** — Contains `fluent-cart-elementor-blocks.php` at the root level

Validation commands:
```bash
# Check ZIP exists and size
ls -la builds/fluent-cart-elementor-block.zip

# List ZIP contents (top-level)
unzip -l builds/fluent-cart-elementor-block.zip | head -30

# Verify no excluded files
unzip -l builds/fluent-cart-elementor-block.zip | grep -E "(\.git|node_modules|\.claude|refs/|resources/)" | head -5
```

### 5. Report

Output a build report:

```
## Build Report

### Command
`npm run build` / `npm run build:zip`

### Status: PASS / FAIL

### Checks
[completed checklist with pass/fail marks]

### Build Output
[key lines from build output — entry points, file sizes, timing]

### Errors (if any)
[error messages with context]
```

If the build failed, suggest likely causes:
- Missing dependencies → `npm install`
- Vite config issues → check `vite.config.js`
- JS syntax errors → check files in `resources/elementor/`