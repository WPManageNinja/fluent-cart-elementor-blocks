# Testing

Changed code? Run the tests before you call it done.

```bash
npm test                     # every tier
npm run test:static          # no WordPress needed
npm run test:smoke           # every widget renders on a real product
npm run test:integration     # template library self-heal

bash tests/bin/run-all.sh smoke render   # one tier, files whose name contains "render"
```

The runner (`tests/bin/run-all.sh`) is modelled on FluentCart core's regression gate: tiers that run alone or together, a PASS/FAIL summary, and a non-zero exit on any failure.

## Tiers

| Tier | Folder | Needs | What it checks |
|---|---|---|---|
| static | `tests/static/` | PHP only | Every PHP file parses (`php -l`). Every translation call uses `fluent-cart-elementor-blocks`. Version and text domain agree across the plugin header, constant, readme, config and POT. |
| smoke | `tests/smoke/` | Local site | Every FluentCart widget renders with its default settings on the most-reviewed product: no exceptions, no PHP warnings from this add-on, and the key markup of the product and review widgets. Reads only. |
| integration | `tests/integration/` | Local site | The bundled template library: a deleted template comes back on request, and nothing duplicates. This tier writes, but only to the templates this add-on seeds into Elementor's library. |

## Live tiers

The smoke and integration tiers run through WP-CLI against your local site with FluentCart installed. They load Elementor and this add-on for the test process only (`tests/bin/lib/load-plugins.php`), so they run even when both are inactive, and nothing about the site's active plugins changes.

The WordPress root is found from the plugin folder. If that fails, set it:

```bash
export WP_PLUGIN_TEST_ROOT=/path/to/wordpress
```

## Adding a test

Put a PHP file in the tier's folder; the runner picks it up. Use the shared assertions in `tests/lib/assert.php`:

```php
require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/my-test.php', ['\Elementor\Plugin']);

$test = new FceTest('smoke/my-test');
$test->check($condition, 'what it proves');
$test->same($actual, $expected, 'what it proves');
$test->finish(); // exits non-zero on any failure
```

Live tests must not change store data. If one has to write, limit it to data the add-on creates, and say so in the file's docblock.

## No CI

Like FluentCart core, the tests run locally: run `npm test` before you call a change done. There is no CI workflow.
