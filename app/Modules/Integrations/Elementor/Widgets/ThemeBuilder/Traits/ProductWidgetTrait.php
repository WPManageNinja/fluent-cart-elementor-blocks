<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits;

use Elementor\Controls_Manager;
use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;

if (!defined('ABSPATH')) {
    exit;
}

trait ProductWidgetTrait
{
    /**
     * Register the Source (Default/Custom) and Product selector controls.
     */
    protected function registerProductSourceControls()
    {
        $this->add_control(
            'source',
            [
                'label'   => esc_html__('Source', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => esc_html__('Current Product', 'fluent-cart'),
                    'custom'  => esc_html__('Custom', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label'       => esc_html__('Select Product', 'fluent-cart'),
                'type'        => (new ProductSelectControl())->get_type(),
                'label_block' => true,
                'multiple'    => false,
                'default'     => '',
                'condition'   => [
                    'source' => 'custom',
                ],
            ]
        );
    }

    /**
     * Resolve the product based on source setting.
     */
    protected function getProduct($settings)
    {
        $source = !empty($settings['source']) ? $settings['source'] : 'default';

        if ($source === 'custom') {
            $productId = !empty($settings['product_id']) ? (int) $settings['product_id'] : 0;
            if ($productId) {
                return ProductDataSetup::getProductModel($productId);
            }
            return null;
        }

        // Default: auto-detect from context
        if (isset($GLOBALS['fct_product']) && $GLOBALS['fct_product'] instanceof Product) {
            return $GLOBALS['fct_product'];
        }

        $postId = get_the_ID();
        if ($postId && get_post_type($postId) === 'fluent-products') {
            return ProductDataSetup::getProductModel($postId);
        }

        return null;
    }

    /**
     * Editor-only fallback product for previews, mirroring how Elementor Pro's
     * WooCommerce single-product widgets resolve a product for the canvas: they
     * render a REAL product (the Theme Builder preview product / current post),
     * never fabricated sample data. When this widget is dropped somewhere with no
     * product in context (e.g. a normal page), we fall back to the latest
     * published product so the editor shows an accurate, fully dynamic layout —
     * real currency, units, gallery and related products — and every Style
     * control has a live target. Null when the store has no products; the caller
     * then shows the "select a product" notice. Read-only; never mutates data.
     *
     * @return \FluentCart\App\Models\Product|null
     */
    protected function getPreviewProduct()
    {
        $latest = Product::query()
            ->where('post_type', 'fluent-products')
            ->where('post_status', 'publish')
            ->orderBy('ID', 'DESC')
            ->first();

        return $latest ? ProductDataSetup::getProductModel($latest->ID) : null;
    }

    /**
     * Render placeholder message in the editor when no product is available.
     */
    protected function renderPlaceholder($message)
    {
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo '<div class="fluent-cart-placeholder" style="text-align:center; padding: 20px; background: #f0f0f1; border: 1px dashed #ccc;">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }

    public function show_in_panel()
    {
        if (in_array('fluent-cart', $this->get_categories(), true)) {
            return true;
        }

        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        $document = \Elementor\Plugin::$instance->documents->get_current();
        if (!$document) {
            return false;
        }

        return in_array(
            $document::get_type(),
            ['fluentcart-product', 'fluentcart-product-post'],
            true
        );
    }
}
