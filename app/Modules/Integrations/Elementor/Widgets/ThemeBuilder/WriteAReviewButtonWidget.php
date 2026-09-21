<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Write a Review Button — the trigger, and the drawer or modal it opens.
 *
 * Mirrors the fluent-cart/write-a-review-button block. The button always
 * renders, because it is the widget's whole purpose; what sits behind it is
 * the Container setting. In link mode there is no overlay at all — the form
 * lives at the other end of the link, which is how a store sends reviewers to
 * one dedicated page.
 */
class WriteAReviewButtonWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    const CONTAINERS = ['drawer', 'modal', 'link'];
    const LAYOUTS = ['inline', 'steps'];
    const LINK_TARGETS = ['self', 'blank'];

    public function get_name()
    {
        return 'fluentcart_write_a_review_button';
    }

    public function get_title()
    {
        return esc_html__('Write a Review Button', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-button fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['review', 'write', 'button', 'cta', 'form', 'rating', 'fluent'];
    }

    public function get_style_depends()
    {
        // The form's own bundle, not the whole single-product one: this is a
        // trigger plus a form, and needs none of the gallery, product card or
        // review list. On a product page the bundle is enqueued anyway, under
        // the same handle.
        AssetLoader::loadReviewSubmissionFormAssets();

        return [];
    }

    /**
     * @param \Elementor\Widget_Base $widget
     * @param string $selector
     */
    public static function registerCtaStyleControls($widget, $selector = '{{WRAPPER}} .fct-review-cta-btn')
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'cta_typography',
                'label'    => esc_html__('Typography', 'fluent-cart-elementor-blocks'),
                'selector' => $selector,
            ]
        );

        $widget->start_controls_tabs('cta_style_tabs');

        $widget->start_controls_tab(
            'cta_style_normal',
            ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $widget->add_control(
            'cta_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$selector => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'cta_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$selector => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'cta_style_hover',
            ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]
        );

        $widget->add_control(
            'cta_color_hover',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$selector . ':hover' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'cta_background_hover',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$selector . ':hover' => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'cta_border_color_hover',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$selector . ':hover' => 'border-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'cta_border',
                'selector'  => $selector,
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'cta_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'cta_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'cta_align',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center'     => [
                        'title' => esc_html__('Center', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end'   => [
                        'title' => esc_html__('Right', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'stretch'    => [
                        'title' => esc_html__('Full Width', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .fluentcart-write-a-review-button' => 'display: flex; flex-direction: column; align-items: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->add_control(
            'container',
            [
                'label'       => esc_html__('Open In', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'drawer',
                'options'     => [
                    'drawer' => esc_html__('Drawer (slides in from the side)', 'fluent-cart-elementor-blocks'),
                    'modal'  => esc_html__('Modal (centered on the screen)', 'fluent-cart-elementor-blocks'),
                    'link'   => esc_html__('Link (open another page)', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Where the form appears when the button is clicked.', 'fluent-cart-elementor-blocks'),
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'layout',
            [
                'label'       => esc_html__('Field Layout', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'inline',
                'options'     => [
                    'inline' => esc_html__('Inline (all fields at once)', 'fluent-cart-elementor-blocks'),
                    'steps'  => esc_html__('Steps (one at a time)', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Steps walks the reviewer through rating, details and photos. Inline shows every field at once.', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'container!' => 'link',
                ],
            ]
        );

        $this->add_control(
            'link_url',
            [
                'label'       => esc_html__('Link URL', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::URL,
                'options'     => ['is_external', 'nofollow'],
                'placeholder' => esc_html__('https://example.com/write-a-review/', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Where the button sends the visitor. Without one the button is not rendered.', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'container' => 'link',
                ],
            ]
        );

        $this->add_control(
            'button_texts_heading',
            [
                'label'       => esc_html__('Button Text', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::HEADING,
                'description' => esc_html__('The button says something different to a reviewer who has already written one, and to a visitor who has to log in first. Leave blank for the store defaults.', 'fluent-cart-elementor-blocks'),
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'add_review_button_text',
            [
                'label'       => esc_html__('New Review', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Write a Review', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Shown when the visitor has not reviewed this product yet', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'edit_review_button_text',
            [
                'label'       => esc_html__('Edit Review', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Edit your review', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Shown when the visitor already has a review for this product', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'container!' => 'link',
                ],
            ]
        );

        $this->add_control(
            'login_review_button_text',
            [
                'label'       => esc_html__('Logged Out', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Log in to Review', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Shown when a visitor must log in before reviewing', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'container!' => 'link',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'cta_style_section',
            [
                'label' => esc_html__('Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerCtaStyleControls($this);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if ($this->renderReviewsUnavailable()) {
            return;
        }

        $product = $this->getProduct($settings);

        if (!$product && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $product = $this->getPreviewProduct();
        }

        if (!$product) {
            $this->renderPlaceholder(
                esc_html__('Please select a product or use this widget inside a product template.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        if ($this->renderReviewsUnavailable($product->ID)) {
            return;
        }

        AssetLoader::loadReviewSubmissionFormAssets();

        $container = $this->pick($settings, 'container', self::CONTAINERS, 'drawer');
        $layout = $this->pick($settings, 'layout', self::LAYOUTS, 'inline');

        // Elementor's URL control is a group: the address plus is_external and
        // nofollow. Only the address and the new-tab flag mean anything to the
        // renderer, which takes 'self' or 'blank'.
        $link = is_array($settings['link_url'] ?? null) ? $settings['link_url'] : [];
        $linkUrl = esc_url_raw((string) ($link['url'] ?? ''));
        $linkTarget = !empty($link['is_external']) ? 'blank' : 'self';

        $renderer = new ProductReviewRenderer($product->ID, [
            'container'    => $container,
            'layout'       => $layout,
            'linkUrl'      => $linkUrl,
            'linkTarget'   => $linkTarget,
            'ctaAddText'   => sanitize_text_field((string) ($settings['add_review_button_text'] ?? '')),
            'ctaEditText'  => sanitize_text_field((string) ($settings['edit_review_button_text'] ?? '')),
            'ctaLoginText' => sanitize_text_field((string) ($settings['login_review_button_text'] ?? '')),
        ]);

        ob_start();
        $renderer->renderWriteReviewCta();
        // A no-op in link mode: the form is at the other end of the link, not
        // hidden behind this button.
        $renderer->renderForm();
        $content = ob_get_clean();

        if (trim($content) === '') {
            $this->renderPlaceholder(
                esc_html__('This visitor cannot review this product, so no button is shown.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-write-a-review-button fct-write-a-review-button-block">' . $content . '</div>';
    }

    /**
     * One validated choice from a whitelist. Elementor settings are user
     * input; a saved value can outlive the option that produced it.
     */
    protected function pick(array $settings, string $key, array $allowed, string $fallback): string
    {
        $value = (string) ($settings[$key] ?? $fallback);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
