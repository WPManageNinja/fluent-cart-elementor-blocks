<?php
/**
 * The MCP builder tools, checked against the real Elementor widget registry.
 *
 * Every case here is a defect that actually reached a built page, not a
 * hypothetical: a repeater the agent could not see inside, a composite widget
 * that rendered the product twice, and a document writer that had no way to
 * remove what it placed. Reads only; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke mcp
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/mcp-builder.php', [
    '\Elementor\Plugin',
    '\FluentCartElementorBlocks\App\Modules\MCP\Support\WidgetSchemaReader',
]);

use FluentCartElementorBlocks\App\Modules\MCP\AbilitiesRegistrar;
use FluentCartElementorBlocks\App\Modules\MCP\Support\BuilderRegistry;
use FluentCartElementorBlocks\App\Modules\MCP\Support\ElementorDocument;
use FluentCartElementorBlocks\App\Modules\MCP\Support\StorefrontRecipes;
use FluentCartElementorBlocks\App\Modules\MCP\Support\WidgetSchemaReader;

$test = new FceTest('smoke/mcp-builder');

// ---------------------------------------------------------------- abilities

$definitions = AbilitiesRegistrar::getDefinitions();
$test->check(count($definitions) >= 11, 'every builder ability is defined (' . count($definitions) . ')');

$malformed = [];
foreach ($definitions as $name => $definition) {
    foreach (['label', 'description', 'execute_callback', 'permission_callback'] as $key) {
        if (empty($definition[$key])) {
            $malformed[] = $name . ' missing ' . $key;
        }
    }

    if (strpos($name, 'fluent-cart/') !== 0) {
        $malformed[] = $name . ' is outside the fluent-cart namespace';
    }

    if (!empty($definition['execute_callback']) && !is_callable($definition['execute_callback'])) {
        $malformed[] = $name . ' execute_callback is not callable';
    }
}
$test->same($malformed, [], 'every ability declares a label, description and callable callbacks');

// A write ability that forgot its annotation reads as safe to an agent.
$writes = ['fluent-cart/place-builder-widget', 'fluent-cart/update-builder-widget', 'fluent-cart/remove-builder-widget'];
$unannotated = [];
foreach ($writes as $name) {
    if (empty($definitions[$name]['annotations']['destructive'])) {
        $unannotated[] = $name;
    }
}
$test->same($unannotated, [], 'write abilities are annotated destructive');

// ------------------------------------------------------- repeater expansion

// The defect: card_elements arrived as a bare "array", so the agent invented a
// row order and the card rendered title and price above the image.
$schema = WidgetSchemaReader::schemaFor('fluent_cart_product_card');
$cardElements = isset($schema['properties']['card_elements']) ? $schema['properties']['card_elements'] : null;

$test->check((bool) $cardElements, 'the product card exposes card_elements');

// The repeater's inner fields are the part that was missing entirely.
$test->check(
    isset($cardElements['items']['properties']['element_type']),
    'card_elements rows carry an element_type field'
);
$test->check(
    strpos((string) $cardElements['description'], 'order') !== false,
    'the description says row order drives render order'
);
$test->check(
    isset($cardElements['default'][0]['element_type']) && $cardElements['default'][0]['element_type'] === 'image',
    'the default card order still leads with the image'
);

$shop = WidgetSchemaReader::schemaFor('fluent_cart_shop_app');
$test->check(
    isset($shop['properties']['shop_layout']['items']['properties']['element_type']),
    'shop_layout sections are expanded, not opaque'
);

// WP-CLI hands back the raw control stack: no `tab`, and select controls
// arrive without their `options`, so enums and labels cannot be read here.
// Those paths are exercised over REST, which is how the MCP actually calls in.
$enriched = false;
foreach (\Elementor\Plugin::$instance->widgets_manager->get_widget_types('fluent_cart_product_card')->get_controls() as $control) {
    if (isset($control['tab']) && $control['tab'] === 'style') {
        $enriched = true;
        break;
    }
}

if ($enriched) {
    $enum = isset($cardElements['items']['properties']['element_type']['enum'])
        ? $cardElements['items']['properties']['element_type']['enum']
        : [];

    foreach (['image', 'title', 'price', 'button'] as $expected) {
        $test->check(in_array($expected, $enum, true), "card_elements accepts '{$expected}'");
    }

    $sale = isset($shop['properties']['show_sale_badge']) ? $shop['properties']['show_sale_badge'] : [];
    $test->same(
        isset($sale['enum']) ? $sale['enum'] : null,
        ['yes', ''],
        'switchers advertise their literal yes/empty values'
    );

    $withStyle = WidgetSchemaReader::schemaFor('fluent_cart_product_card', ['style']);
    $test->check(
        count($withStyle['properties']) > count($schema['properties']),
        'style controls are opt-in, not in the default schema'
    );
} else {
    $test->skip('card_elements enum values', 'WP-CLI returns an unenriched control stack (no options, no tab)');
    $test->skip('switcher yes/empty enum', 'same — select options are absent under WP-CLI');
    $test->skip('style controls are opt-in', 'same — every control reports tab=content under WP-CLI');
}

// ------------------------------------------------------ composite conflicts

// The defect: product_info renders the whole product block, so placing it
// beside the individual widgets rendered everything twice.
$composite = BuilderRegistry::conflictsFor('fluentcart_product_info');
$test->same(isset($composite['role']) ? $composite['role'] : null, 'composite', 'product_info is flagged as a composite');
$test->check(
    in_array('fluentcart_product_title', (array) $composite['widgets'], true),
    'the composite declares that it already renders the title'
);

$part = BuilderRegistry::conflictsFor('fluentcart_product_title');
$test->same(isset($part['role']) ? $part['role'] : null, 'part', 'an individual widget knows it is covered by a composite');
$test->check(
    in_array('fluentcart_product_info', (array) $part['widgets'], true),
    'and names the composite that covers it'
);

$test->same(BuilderRegistry::conflictsFor('fluentcart_product_gallery') === null, false, 'gallery is covered by the composite too');

// --------------------------------------------------------- widget ownership

// The guard every write depends on: never touch another plugin's element.
$test->same(BuilderRegistry::isFluentCartWidget('fluent_cart_product_card'), true, 'our widgets are recognised');
$test->same(BuilderRegistry::isFluentCartWidget('e-heading'), false, 'Elementor widgets are refused');
$test->same(BuilderRegistry::isFluentCartWidget(''), false, 'an empty widget type is refused');
$test->same(BuilderRegistry::isFluentCartWidget(null), false, 'a null widget type is refused');

$registered = 0;
foreach (BuilderRegistry::widgets() as $row) {
    if ($row['available']) {
        $registered++;
    }
    if (!$row['available']) {
        $test->check((bool) $row['unavailable_reason'], $row['name'] . ' explains why it is unavailable');
    }
}
$test->check($registered >= 31, "the catalog matches the registry ({$registered} usable)");

// ------------------------------------------------------------ document tree

// Place, find, then remove — the round trip the agent could not complete
// before remove-builder-widget existed.
$tree = [];
$node = ElementorDocument::widgetNode(ElementorDocument::newId($tree), 'fluent_cart_product_card', ['product_id' => '1']);
$tree = ElementorDocument::append($tree, $node);

$test->check(!is_wp_error($tree), 'a widget appends at the document root');

$found = ElementorDocument::find($tree, $node['id']);
$test->check($found !== null, 'a placed widget is findable by id');
$test->same(isset($found['widgetType']) ? $found['widgetType'] : null, 'fluent_cart_product_card', 'and keeps its type');

// A widget cannot sit at the document root, so it must have been wrapped.
$test->check(
    isset($tree[0]['elements'][0]['id']) && $tree[0]['elements'][0]['id'] === $node['id'],
    'a root-level widget is wrapped in a container'
);

$outline = ElementorDocument::outline($tree);
$test->check(isset($outline[0]['elements']), 'outline names its child list "elements", matching Elementor');

$removed = [];
$pruned = ElementorDocument::removeWhere($tree, function ($n) use ($node) {
    return isset($n['id']) && $n['id'] === $node['id'];
}, $removed);
$test->same(count($removed), 1, 'removeWhere reports what it took out');
$test->same(ElementorDocument::find($pruned, $node['id']), null, 'and the widget is gone from the tree');

// Ids must be unique within a document or Elementor silently merges elements.
$ids = [];
$sample = [];
for ($i = 0; $i < 50; $i++) {
    $id = ElementorDocument::newId($sample);
    $ids[] = $id;
    $sample[] = ElementorDocument::widgetNode($id, 'fluent_cart_product_card', []);
}
$test->same(count(array_unique($ids)), 50, 'generated element ids never collide within a document');

// ---------------------------------------------------------------- recipes

// A recipe missing its block or shortcode made validate-storefront report a
// healthy store as broken.
$incomplete = [];
foreach (StorefrontRecipes::all() as $name => $recipe) {
    foreach (['title', 'widget', 'setting_key', 'purpose'] as $key) {
        if (empty($recipe[$key])) {
            $incomplete[] = "{$name}.{$key}";
        }
    }

    if (!array_key_exists('block', $recipe) || !array_key_exists('shortcode', $recipe)) {
        $incomplete[] = "{$name} missing block/shortcode equivalents";
    }

    if (!BuilderRegistry::isFluentCartWidget($recipe['widget'])) {
        $incomplete[] = "{$name}.widget is not a FluentCart widget";
    }
}
$test->same($incomplete, [], 'every storefront recipe is complete and points at a real widget');

$test->finish();
