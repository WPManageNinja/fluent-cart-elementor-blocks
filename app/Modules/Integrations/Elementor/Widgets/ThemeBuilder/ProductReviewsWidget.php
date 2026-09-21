<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ReviewStyleControls;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Reviews — the whole section in one widget: the rating summary, the
 * Write a Review call to action, and the list beneath them.
 *
 * Mirrors the fluent-cart/product-reviews block on its emptied path, where the
 * block stops being a container and draws the section itself to the renderer's
 * own defaults. That is the one to reach for when a product page just needs
 * reviews on it; the four widgets beside it are for laying the same pieces out
 * by hand.
 *
 * Deliberately thin. Every choice this section offers already lives either in
 * core's review settings or in one of the other widgets, and duplicating them
 * here would give a merchant two places to set the same thing and no way to
 * tell which won.
 */
class ProductReviewsWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_reviews';
    }

    public function get_title()
    {
        return esc_html__('Product Reviews', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-testimonial fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['review', 'reviews', 'rating', 'testimonial', 'feedback', 'fluent'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadSingleProductAssets();

        return [];
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
            'composition_note',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('This shows the whole review section — summary, Write a Review button and the list — using the store\'s review settings. To lay those out yourself, use the Review Summary, Write a Review Button and Product Review List widgets instead.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-descriptor',
                'separator'       => 'before',
            ]
        );

        $this->end_controls_section();

        // This widget draws the summary, the call to action and the list, so
        // it offers all three panels — the first two borrowed from the widgets
        // that own them, the rest shared with the Review List widget.
        $this->start_controls_section(
            'summary_style_section',
            [
                'label' => esc_html__('Rating Summary', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductReviewSummaryWidget::registerSummaryStyleControls($this);

        $this->end_controls_section();

        $this->start_controls_section(
            'cta_style_section',
            [
                'label' => esc_html__('Write a Review Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        WriteAReviewButtonWidget::registerCtaStyleControls($this);

        $this->end_controls_section();

        // false: this widget always draws core's default section, which is
        // list view, so a grid or slider gap control would never apply.
        ReviewStyleControls::register($this, false);
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

        AssetLoader::loadSingleProductAssets();

        ob_start();
        // No options: the renderer's own defaults are what the emptied block
        // draws, and what the shortcode draws. One section, one look.
        (new ProductReviewRenderer($product->ID, []))->render();
        $content = ob_get_clean();

        if (trim($content) === '') {
            $this->renderPlaceholder(
                esc_html__('There is nothing to show for this product yet.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-product-reviews fct-product-reviews-block">' . $content . '</div>';
    }
}
