<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\StoreLogoRenderer;

if (!defined('ABSPATH')) {
    exit;
}

class StoreLogoWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_store_logo';
    }

    public function get_title()
    {
        return esc_html__('Store Logo', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-site-logo';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['store', 'logo', 'brand', 'fluent', 'cart'];
    }

    public function get_style_depends()
    {
        AssetLoader::markFrontendAssetsRequired();

        return [];
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerStyleControls();
    }

    // ──────────────────────────────────────────────────────────
    // Content controls
    // ──────────────────────────────────────────────────────────

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Store Logo', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'link_to',
            [
                'label'   => esc_html__('Link', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'home',
                'options' => [
                    'home' => esc_html__('Home Page', 'fluent-cart'),
                    'none' => esc_html__('None', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'link_target',
            [
                'label'     => esc_html__('Open in New Tab', 'fluent-cart'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'fluent-cart'),
                'label_off' => esc_html__('No', 'fluent-cart'),
                'default'   => '',
                'condition' => [
                    'link_to' => 'home',
                ],
            ]
        );

        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Style controls
    // ──────────────────────────────────────────────────────────

    private function registerStyleControls()
    {
        $this->start_controls_section(
            'logo_style_section',
            [
                'label' => esc_html__('Logo Image', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerLogoStyleControls($this, '{{WRAPPER}} .fct-store-logo-img');

        $this->end_controls_section();

        $this->start_controls_section(
            'alignment_style_section',
            [
                'label' => esc_html__('Alignment', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerAlignmentStyleControls($this, '{{WRAPPER}} .fct-store-logo-wrapper');

        $this->end_controls_section();

        $this->start_controls_section(
            'store_name_style_section',
            [
                'label' => esc_html__('Store Name (Fallback)', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerStoreNameStyleControls($this, '{{WRAPPER}} .fct-store-logo-text');

        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Public static style methods — callable by any widget
    // ──────────────────────────────────────────────────────────

    /**
     * Logo image: max-width (responsive), max-height (responsive).
     */
    public static function registerLogoStyleControls($widget, $selector)
    {
        $widget->add_responsive_control(
            'logo_max_width',
            [
                'label'      => esc_html__('Max Width', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px' => ['min' => 20, 'max' => 500],
                    '%'  => ['min' => 1, 'max' => 100],
                    'vw' => ['min' => 1, 'max' => 100],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 150,
                ],
                'selectors'  => [
                    $selector => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'logo_max_height',
            [
                'label'      => esc_html__('Max Height', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'vh'],
                'range'      => [
                    'px' => ['min' => 10, 'max' => 300],
                    'em' => ['min' => 1, 'max' => 20],
                    'vh' => ['min' => 1, 'max' => 50],
                ],
                'selectors'  => [
                    $selector => 'max-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Wrapper alignment: text-align (left / center / right), responsive.
     */
    public static function registerAlignmentStyleControls($widget, $selector)
    {
        $widget->add_responsive_control(
            'logo_alignment',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => esc_html__('Left', 'fluent-cart'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'fluent-cart'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__('Right', 'fluent-cart'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    $selector => 'text-align: {{VALUE}};',
                ],
            ]
        );
    }

    /**
     * Store name text fallback: typography, color.
     */
    public static function registerStoreNameStyleControls($widget, $selector)
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'store_name_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'store_name_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}};',
                ],
            ]
        );
    }

    // ──────────────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────────────

    protected function render()
    {
        AssetLoader::markFrontendAssetsRequired();

        $settings = $this->get_settings_for_display();

        $linkTo = in_array($settings['link_to'] ?? 'home', ['home', 'none'], true)
            ? ($settings['link_to'] ?? 'home')
            : 'home';

        $isLink = $linkTo === 'home';

        $linkTargetRaw = $settings['link_target'] ?? '';
        $linkTarget    = ($isLink && $linkTargetRaw === 'yes') ? '_blank' : '_self';

        $atts = [
            'is_shortcode' => true,
            'is_link'      => $isLink,
            'link_target'  => $linkTarget,
        ];

        $renderer = new StoreLogoRenderer();
        $renderer->render($atts);
    }
}
