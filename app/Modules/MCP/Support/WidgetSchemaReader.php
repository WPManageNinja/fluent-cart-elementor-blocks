<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

use FluentCart\Framework\Support\Arr;

/**
 * Turns a registered Elementor widget's control stack into JSON Schema.
 *
 * Elementor already holds the full, resolved control list for every widget —
 * including controls injected by traits, shared helpers and repeaters — so we
 * read it back with get_controls() instead of maintaining a parallel table.
 * A hand-written schema would drift from the widget classes within a release;
 * this cannot.
 *
 * Elementor's MCP will not configure our widgets (it refuses V3 elements with
 * `elementor_v3_not_supported`), so this is the only way an agent can learn
 * what settings a FluentCart widget accepts.
 */
class WidgetSchemaReader
{
    /** Controls that describe styling rather than content. */
    const STYLE_TABS = ['style', 'advanced'];

    /** Elementor control type => JSON Schema type. */
    const TYPE_MAP = [
        'text'        => 'string',
        'textarea'    => 'string',
        'wysiwyg'     => 'string',
        'code'        => 'string',
        'number'      => 'number',
        'slider'      => 'object',
        'switcher'    => 'string',
        'select'      => 'string',
        'select2'     => 'string',
        'choose'      => 'string',
        'color'       => 'string',
        'media'       => 'object',
        'gallery'     => 'array',
        'repeater'    => 'array',
        'url'         => 'object',
        'icons'       => 'object',
        'dimensions'  => 'object',
        'typography'  => 'object',
        'hidden'      => 'string',
    ];

    public static function elementorAvailable()
    {
        return did_action('elementor/loaded') && class_exists('\Elementor\Plugin');
    }

    /**
     * The widget instance Elementor registered, or null.
     *
     * Widgets register on `elementor/widgets/register`, which fires during
     * `init`; callers reaching this from a REST request are safely after that.
     */
    public static function widget($name)
    {
        if (!self::elementorAvailable()) {
            return null;
        }

        $manager = \Elementor\Plugin::$instance->widgets_manager;

        if (!$manager || !method_exists($manager, 'get_widget_types')) {
            return null;
        }

        $widget = $manager->get_widget_types($name);

        return is_object($widget) ? $widget : null;
    }

    /**
     * JSON Schema for one widget's settings.
     *
     * $include may carry 'style' and 'advanced' to add the design controls;
     * the default is content-only, which is what an agent placing a widget
     * actually needs and keeps the payload small.
     */
    public static function schemaFor($name, array $include = [])
    {
        $widget = self::widget($name);

        if (!$widget) {
            return null;
        }

        $wantStyle = (bool) array_intersect($include, self::STYLE_TABS);
        $properties = [];
        $required   = [];

        foreach ($widget->get_controls() as $id => $control) {
            if (!is_array($control) || self::skip($control, $wantStyle)) {
                continue;
            }

            $prop = self::property($control);

            if ($prop === null) {
                continue;
            }

            $properties[$id] = $prop;

            // Elementor has no "required" flag; a control with no default that
            // the widget cannot render without is the closest equivalent, and
            // only the widget itself knows that. Left to placement_notes.
        }

        return [
            'type'       => 'object',
            'properties' => $properties,
            'required'   => $required,
        ];
    }

    /**
     * Controls that carry no agent-usable value.
     *
     * Section open/close markers and raw HTML notices are editor furniture.
     * Style controls are dropped unless asked for.
     */
    private static function skip(array $control, $wantStyle)
    {
        $type = Arr::get($control, 'type');

        if (in_array($type, ['section', 'tab', 'raw_html', 'divider', 'heading', 'deprecated_notice', 'notice'], true)) {
            return true;
        }

        if (!$wantStyle && in_array(Arr::get($control, 'tab'), self::STYLE_TABS, true)) {
            return true;
        }

        return false;
    }

    /** One control => one JSON Schema property. */
    private static function property(array $control)
    {
        $type = (string) Arr::get($control, 'type', 'text');

        $prop = [
            'type' => Arr::get(self::TYPE_MAP, $type, 'string'),
        ];

        $label = Arr::get($control, 'label');
        $desc  = Arr::get($control, 'description');

        $prop['description'] = trim(wp_strip_all_tags((string) $label . ($desc ? ' — ' . $desc : '')));

        $default = Arr::get($control, 'default');
        if ($default !== null && $default !== '') {
            $prop['default'] = $default;
        }

        // Elementor switchers store the literal strings 'yes' and '' rather
        // than booleans — an agent sending true would silently not apply.
        if ($type === 'switcher') {
            $prop['enum']        = ['yes', ''];
            $prop['description'] .= ' (on = "yes", off = "")';
        }

        $options = Arr::get($control, 'options');
        if (is_array($options) && $options && in_array($type, ['select', 'select2', 'choose'], true)) {
            $prop['enum'] = array_map('strval', array_keys($options));
        }

        // A conditional control only applies when its siblings hold certain
        // values; say so rather than letting the agent set a dead key.
        $condition = Arr::get($control, 'condition');
        if (is_array($condition) && $condition) {
            $parts = [];
            foreach ($condition as $key => $value) {
                $parts[] = $key . '=' . (is_array($value) ? implode('|', $value) : $value);
            }
            $prop['description'] .= ' (applies when ' . implode(', ', $parts) . ')';
        }

        // A repeater without its inner fields is unusable: the agent sees only
        // "array" and has to invent the row shape. On card_elements that also
        // means guessing the render ORDER, which produces a visibly broken
        // card rather than an error — exactly how a real build went wrong.
        if ($type === 'repeater') {
            $prop['items'] = self::repeaterItems($control);
            $prop['description'] .= ' Row order is the render order.';
        }

        $prop['description'] = trim($prop['description']);

        return $prop;
    }

    /** Expand a repeater's inner controls into an object schema. */
    private static function repeaterItems(array $control)
    {
        $fields = Arr::get($control, 'fields');

        if (!is_array($fields) || !$fields) {
            return ['type' => 'object'];
        }

        $properties = [];

        foreach ($fields as $id => $field) {
            if (!is_array($field) || self::skip($field, true)) {
                continue;
            }

            $sub = self::property($field);

            if ($sub === null) {
                continue;
            }

            $properties[isset($field['name']) ? $field['name'] : $id] = $sub;
        }

        return $properties
            ? ['type' => 'object', 'properties' => $properties]
            : ['type' => 'object'];
    }
}
