<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use FluentCart\App\Modules\MultiCurrency\Services\CurrencySwitcher;
use FluentCart\Framework\Support\Arr;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProFeature;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The Elementor form of the Currency Switcher block / [fluent_cart_currency_switcher].
 */
class CurrencySwitcherWidget extends Widget_Base
{
    const LABEL_VALUES = ['name_code', 'name', 'code', 'symbol', 'flag'];

    /** The panel icon: a $ coin in exchange arrows (no Elementor icon shows currency). */
    const ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.6 10.2A8.6 8.6 0 0 1 18.9 6.6M19.4 2.8v4h-4M20.4 13.8A8.6 8.6 0 0 1 5.1 17.4M4.6 21.2v-4h4M12 7.6v8.8M14.1 9.7c-.3-.8-1.1-1.3-2.1-1.3-1.2 0-2.1.6-2.1 1.6 0 2.2 4.3 1.2 4.3 3.4 0 1-.9 1.7-2.2 1.7-1.1 0-1.9-.5-2.2-1.3"/></svg>';

    public function get_name()
    {
        return 'fluent_cart_currency_switcher';
    }

    public function get_title()
    {
        return esc_html__('Currency Switcher', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'fluent-cart-currency-switcher-widget-icon fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['currency', 'switcher', 'multi currency', 'fluent', 'cart'];
    }

    /**
     * The editor's Pro notice is styled by elementor.css, the stylesheet the review
     * widgets load (same handle, so it prints once).
     */
    public function get_style_depends()
    {
        $preview = \Elementor\Plugin::$instance->preview;
        if (!static::isAvailable() && $preview && $preview->is_preview_mode()) {
            Enqueue::staticStyle('fluentcart-product-reviews-elementor', 'css/elementor.css', [], FLUENTCART_ELEMENTOR_BLOCKS_VERSION);
        }

        return [];
    }

    /**
     * Core's switcher is a plain select with no options; only a plugin that
     * replaces it (Pro's flag button) reads them — same gate as the block.
     */
    public static function hasOptions(): bool
    {
        $preview = apply_filters('fluent_cart/currency_switcher/editor_preview', ['style' => 'select', 'currencies' => []]);

        return is_array($preview) && Arr::get($preview, 'style') === 'button';
    }

    /**
     * Multi-currency is a Pro feature: without it core's switcher renders nothing.
     * The same gate as the block; an older core without it counts as available.
     */
    public static function isAvailable(): bool
    {
        $settings = '\\FluentCart\\App\\Modules\\MultiCurrency\\Services\\MultiCurrencySettings';

        return !method_exists($settings, 'isAvailable') || $settings::isAvailable();
    }

    protected function register_controls()
    {
        if (!static::hasOptions()) {
            return;
        }

        $this->start_controls_section('switcher_section', [
            'label' => esc_html__('Switcher Settings', 'fluent-cart-elementor-blocks'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);
        $this->registerLabelControls('', 'code');
        $this->end_controls_section();

        $this->start_controls_section('list_section', [
            'label' => esc_html__('Dropdown List', 'fluent-cart-elementor-blocks'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);
        $this->registerLabelControls('list_', 'name_code');
        $this->end_controls_section();
    }

    /**
     * Display, Show sign, Show flag and Flag position — the button and the list get the same set.
     */
    private function registerLabelControls(string $prefix, string $defaultDisplay)
    {
        $display = $prefix . 'display';

        $this->add_control($display, [
            'label'   => esc_html__('Display', 'fluent-cart-elementor-blocks'),
            'type'    => Controls_Manager::SELECT,
            'default' => $defaultDisplay,
            'options' => [
                'name_code' => esc_html__('Name and code', 'fluent-cart-elementor-blocks'),
                'name'      => esc_html__('Currency name', 'fluent-cart-elementor-blocks'),
                'code'      => esc_html__('Code', 'fluent-cart-elementor-blocks'),
                'symbol'    => esc_html__('Sign only', 'fluent-cart-elementor-blocks'),
                'flag'      => esc_html__('Flag only', 'fluent-cart-elementor-blocks'),
            ],
        ]);

        if ($prefix === '') {
            // The block's sign follows the display until it is set: on for Code, off for names
            $this->add_control('symbol', [
                'label'     => esc_html__('Show sign', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SWITCHER,
                'default'   => 'yes',
                'condition' => [$display => 'code'],
            ]);
            $this->add_control('symbol_name', [
                'label'     => esc_html__('Show sign', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SWITCHER,
                'default'   => '',
                'condition' => [$display => ['name_code', 'name']],
            ]);
        } else {
            $this->add_control($prefix . 'symbol', [
                'label'     => esc_html__('Show sign', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SWITCHER,
                'default'   => '',
                'condition' => [$display . '!' => ['symbol', 'flag']],
            ]);
        }

        $this->add_control($prefix . 'flag', [
            'label'     => esc_html__('Show flag', 'fluent-cart-elementor-blocks'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => [$display . '!' => 'flag'],
        ]);

        $this->add_control($prefix . 'flag_position', [
            'label'     => esc_html__('Flag position', 'fluent-cart-elementor-blocks'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'left',
            'options'   => [
                'left'  => esc_html__('Left', 'fluent-cart-elementor-blocks'),
                'right' => esc_html__('Right', 'fluent-cart-elementor-blocks'),
            ],
            'condition' => [$display . '!' => 'flag', $prefix . 'flag' => 'yes'],
        ]);
    }

    /**
     * Widget settings as the shortcode's attributes, allowlisted.
     */
    public static function switcherAtts(array $settings): array
    {
        $display = Arr::get($settings, 'display', 'code');
        $display = in_array($display, self::LABEL_VALUES, true) ? $display : 'code';

        if ($display === 'code') {
            $symbol = Arr::get($settings, 'symbol', 'yes') === 'yes' ? 'yes' : 'no';
        } elseif (in_array($display, ['name_code', 'name'], true)) {
            $symbol = Arr::get($settings, 'symbol_name') === 'yes' ? 'yes' : 'no';
        } else {
            $symbol = '';
        }

        $listDisplay = Arr::get($settings, 'list_display', 'name_code');

        return [
            'display'            => $display,
            'symbol'             => $symbol,
            'flag'               => Arr::get($settings, 'flag', 'yes') === 'yes' ? 'yes' : 'no',
            'flag_position'      => Arr::get($settings, 'flag_position') === 'right' ? 'right' : 'left',
            'list_display'       => in_array($listDisplay, self::LABEL_VALUES, true) ? $listDisplay : 'name_code',
            'list_symbol'        => Arr::get($settings, 'list_symbol') === 'yes' ? 'yes' : 'no',
            'list_flag'          => Arr::get($settings, 'list_flag', 'yes') === 'yes' ? 'yes' : 'no',
            'list_flag_position' => Arr::get($settings, 'list_flag_position') === 'right' ? 'right' : 'left',
        ];
    }

    protected function render()
    {
        if (!static::isAvailable()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo ProFeature::notice(__('The currency switcher is a FluentCart Pro feature. It shows nothing on your site until FluentCart Pro is active.', 'fluent-cart-elementor-blocks')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ProFeature::notice()
            }

            return;
        }

        $atts = static::hasOptions() ? static::switcherAtts((array) $this->get_settings_for_display()) : [];
        $html = CurrencySwitcher::render($atts);

        if ($html === '') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                printf(
                    '<p class="fct-currency-switcher-empty">%s</p>',
                    esc_html__('Shows when Settings → Multi Currency offers more than one currency.', 'fluent-cart-elementor-blocks')
                );
            }

            return;
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core/Pro switcher markup, escaped where built
    }
}
