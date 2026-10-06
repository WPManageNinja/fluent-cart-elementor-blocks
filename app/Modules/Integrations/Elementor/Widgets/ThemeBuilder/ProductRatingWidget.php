<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Rating — the star line and review count for one product.
 *
 * Mirrors the fluent-cart/product-rating block: the same two thresholds, the
 * same store kill switch, and the same renderer
 * (ProductCardRender::renderStarRatingBlock), so a page built here and a page
 * built in the editor draw the identical markup from the identical aggregate.
 */
class ProductRatingWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    /**
     * The store setting the block consults on its non-related path. Related
     * products have their own switch in core, reached through block context
     * that an Elementor widget has no equivalent of; a widget the merchant
     * placed by hand is the shop-side question.
     */
    const VISIBILITY_SETTING = 'show_rating_in_shop';

    public function get_name()
    {
        return 'fluentcart_product_rating';
    }

    public function get_title()
    {
        return esc_html__('Product Rating', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-rating fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'rating', 'review', 'star', 'stars', 'fluent'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadSingleProductAssets();

        return [];
    }

    /**
     * Star, half-star and count styling, shared so a composite widget can
     * offer the same panel against its own selector.
     *
     * @param \Elementor\Widget_Base $widget
     * @param string $selector
     */
    public static function registerRatingStyleControls($widget, $selector = '{{WRAPPER}} .fct-product-card-rating')
    {
        $widget->add_control(
            'rating_star_color',
            [
                'label'     => esc_html__('Star Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    // The filled star and the filled half of a half star are
                    // one colour; the empty star is the control below.
                    $selector . ' .fct-star-filled'     => 'color: {{VALUE}};',
                    $selector . ' .fct-star-half-fill'  => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'rating_empty_star_color',
            [
                'label'     => esc_html__('Empty Star Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-star-empty'      => 'color: {{VALUE}};',
                    $selector . ' .fct-star-half-empty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'rating_star_size',
            [
                'label'      => esc_html__('Star Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => ['min' => 8, 'max' => 64],
                    'em' => ['min' => 0.5, 'max' => 4, 'step' => 0.1],
                ],
                'selectors'  => [
                    $selector . ' .fct-product-card-stars' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'rating_star_gap',
            [
                'label'      => esc_html__('Star Spacing', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 20],
                ],
                'selectors'  => [
                    $selector . ' .fct-star' => 'margin-inline-end: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'rating_count_typography',
                'label'     => esc_html__('Review Count Typography', 'fluent-cart-elementor-blocks'),
                'selector'  => $selector . ' .fct-product-card-review-count',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'rating_count_color',
            [
                'label'     => esc_html__('Review Count Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-product-card-review-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'rating_alignment',
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
                ],
                'selectors' => [
                    $selector => 'display: flex; align-items: center; justify-content: {{VALUE}};',
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

        // The two thresholds the block carries, with its wording. An average
        // over one or two reviews says very little, and five empty stars on a
        // new product reads as a bad rating rather than as no rating.
        $this->add_control(
            'min_review_count',
            [
                'label'       => esc_html__('Minimum Reviews', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Hide the rating until the product has at least this many reviews. 0 always shows it, including five empty stars on a product with none.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 1000,
                'step'        => 1,
                'default'     => 0,
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'min_average_rating',
            [
                'label'       => esc_html__('Minimum Average Rating', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Hide the rating unless the product averages at least this many stars. 0 shows every rating. Half stars are allowed.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 5,
                'step'        => 0.5,
                'default'     => 0,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'rating_style_section',
            [
                'label' => esc_html__('Rating', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerRatingStyleControls($this);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if ($this->renderReviewsUnavailable()) {
            return;
        }

        // The store-wide switch, the same one the block reads before it draws
        // anything. Turning ratings off store-wide has to turn this off too,
        // or the setting means nothing on an Elementor-built store.
        if ((new \FluentCart\Api\StoreSettings())->get(self::VISIBILITY_SETTING, 'yes') !== 'yes') {
            $this->renderPlaceholder(
                esc_html__('Product ratings are switched off in FluentCart → Settings.', 'fluent-cart-elementor-blocks')
            );
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

        if (!$this->passesThresholds($product, $settings)) {
            $this->renderPlaceholder(
                esc_html__('This product does not meet the minimum reviews or rating set for this widget.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        AssetLoader::loadSingleProductAssets();

        ob_start();
        // The renderer writes the wrapper's attributes itself, the way the
        // block hands it get_block_wrapper_attributes().
        (new ProductCardRender($product))->renderStarRatingBlock('class="fct-product-card-rating"');
        $content = ob_get_clean();

        if (trim($content) === '') {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-product-rating">' . $content . '</div>';
    }

    /**
     * Both thresholds, read from detail->other_info — the canonical aggregate
     * the stars are drawn from. Recounting the rows here could disagree with
     * the number printed beside them.
     *
     * @param \FluentCart\App\Models\Product $product
     * @param array $settings
     */
    protected function passesThresholds($product, array $settings): bool
    {
        $detail = $product->detail;
        $info = $detail ? ($detail->other_info ?: []) : [];

        $minReviewCount = max(0, absint($settings['min_review_count'] ?? 0));

        if ($minReviewCount > 0 && (int) Arr::get($info, 'review_count', 0) < $minReviewCount) {
            return false;
        }

        // Clamped to the five the stars can draw — a threshold above 5 would
        // hide every product, which is a setting no one means to choose.
        $minAverage = min(5.0, max(0.0, (float) ($settings['min_average_rating'] ?? 0)));

        if ($minAverage > 0 && (float) Arr::get($info, 'average_rating', 0) < $minAverage) {
            return false;
        }

        return true;
    }
}
