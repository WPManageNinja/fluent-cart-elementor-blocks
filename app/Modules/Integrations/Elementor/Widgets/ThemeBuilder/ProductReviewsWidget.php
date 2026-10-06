<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ReviewStyleControls;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewLayoutPresets;

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

    const MIN_COLUMNS = ProductReviewListWidget::MIN_COLUMNS;
    const MAX_COLUMNS = ProductReviewListWidget::MAX_COLUMNS;
    // Borrowed rather than repeated, the way MIN_COLUMNS above it is: the two
    // widgets draw the same list through the same renderer, and a second copy
    // of this list is a second place every change has to be remembered.
    const VIEW_MODES = ProductReviewListWidget::VIEW_MODES;

    /** Where the rating summary goes, as ProductReviewRenderer names them. */
    const SUMMARY_MODES = ['side', 'top', 'cta'];
    const ARROW_SIZES = ['sm', 'md', 'lg'];
    const ARROW_POSITIONS = ['overlap', 'outside', 'bottom'];
    const AUTOPLAY_MODES = ['no', 'yes', 'hover'];
    const PAGINATION_TYPES = ['numbers', 'fraction', 'bullets'];
    // The slider's own indicator, which is a different thing from the pager
    // above: these move between loaded slides, that one loads more reviews.
    const PAGINATION_STYLES = ['bullets', 'fraction', 'progressbar', 'segmented'];
    const CONTAINERS = ['drawer', 'modal'];
    const LAYOUTS = ['inline', 'steps'];

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
        static::registerReviewPresetStyles();
        static::registerSliderAssets();

        // Unconditionally, and not because it is always needed.
        //
        // Elementor calls this while collecting a widget's assets, before any
        // instance exists: every way of reading the settings from here — both
        // get_settings_for_display() and get_settings() — runs Elementor's
        // sanitiser over a null and throws. There is no way to ask which layout
        // is in play at the moment the question is asked.
        //
        // So the answer is the safe one. A stylesheet that arrives when it was
        // not needed costs one cached core file on a page that already draws a
        // review section; one that is missing when it was needed collapses the
        // summary and the list into a column of single letters.
        return ['wp-block-library'];
    }

    /**
     * A class method wins over the trait's, so the shared re-init handler is
     * named here rather than inherited.
     */
    public function get_script_depends()
    {
        static::registerReviewEditorScript();

        return [
            'fluentcart-product-reviews-elementor',
            static::registerSliderAssets(),
        ];
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

        // The product first. It is what the section is about, and the
        // layout picker below draws the reviews of whichever one is named
        // here - a picker that comes first is showing eleven arrangements of
        // nothing in particular.
        $this->registerProductSourceControls();

        $this->addReviewLayoutPresetControl();

        $this->add_control(
            'composition_note',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('The whole review section in one widget: the rating summary, the Write a Review button and the list. To place those as separate pieces, use the Review Summary, Write a Review Button and Product Review List widgets instead.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-descriptor',
                'separator'       => 'before',
            ]
        );

        $this->end_controls_section();


        // ── Rating summary ────────────────────────────────
        $this->start_controls_section(
            'summary_content_section',
            [
                'label' => esc_html__('Rating Summary', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_summary',
            [
                'label'        => esc_html__('Rating Summary', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'summary_mode',
            [
                'label'     => esc_html__('Placement', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'side',
                'options'   => [
                    'side' => esc_html__('Beside the reviews', 'fluent-cart-elementor-blocks'),
                    'top'  => esc_html__('Across the top', 'fluent-cart-elementor-blocks'),
                    'cta'  => esc_html__('Only the Write a Review button', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['show_summary' => 'yes'],
            ]
        );

        $this->end_controls_section();

        // ── The call to action and the form behind it ─────
        $this->start_controls_section(
            'cta_content_section',
            [
                'label' => esc_html__('Write a Review Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'container',
            [
                'label'       => esc_html__('Open In', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'drawer',
                'options'     => [
                    'drawer' => esc_html__('Drawer (slides in from the side)', 'fluent-cart-elementor-blocks'),
                    'modal'  => esc_html__('Modal (centered on the screen)', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Where the form appears when the button is clicked.', 'fluent-cart-elementor-blocks'),
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
            ]
        );

        $this->add_control(
            'login_review_button_text',
            [
                'label'       => esc_html__('Logged Out', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Log in to Review', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Shown when a visitor must log in before reviewing', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->end_controls_section();

        // ── The list beneath them ─────────────────────────
        $this->start_controls_section(
            'list_content_section',
            [
                'label' => esc_html__('Review List', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'min_rating',
            [
                'label'       => esc_html__('Minimum rating', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Lower-rated reviews are left out of the list entirely, and the count and the pages follow.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => '0',
                'options'     => [
                    '0' => esc_html__('All ratings', 'fluent-cart-elementor-blocks'),
                    '2' => esc_html__('2 stars and up', 'fluent-cart-elementor-blocks'),
                    '3' => esc_html__('3 stars and up', 'fluent-cart-elementor-blocks'),
                    '4' => esc_html__('4 stars and up', 'fluent-cart-elementor-blocks'),
                    '5' => esc_html__('5 stars only', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'photos_only',
            [
                'label'        => esc_html__('Reviews with attachments only', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Leaves out reviews that carry no photograph, and the count and the pages follow. What the photo layouts are built on.', 'fluent-cart-elementor-blocks'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'content_max_words',
            [
                'label'       => esc_html__('Words shown', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Reviews show in full. Move the slider to cut longer ones down.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 500,
                'default'     => 0,
            ]
        );

        $this->add_control(
            'view_mode',
            [
                'label'     => esc_html__('View Mode', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'list',
                'options'   => $this->reviewViewModeOptions(),
                'separator' => 'before',
                'classes'   => $this->reviewViewModeProClasses(),
            ]
        );

        $this->addReviewViewModeProNotice();

        $this->add_control(
            'grid_columns',
            [
                'label'     => esc_html__('Columns', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => self::MIN_COLUMNS,
                'max'       => self::MAX_COLUMNS,
                'default'   => 2,
                'condition' => ['view_mode' => ['grid', 'masonry', 'slider']],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'card_content_section',
            [
                'label' => esc_html__('Review Card', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'row_fields_heading',
            [
                'label'     => esc_html__('Show in each review', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_reviewer_name',
            [
                'label'        => esc_html__('Reviewer Name', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_review_date',
            [
                'label'        => esc_html__('Review Date', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_verified',
            [
                'label'        => esc_html__('Verified Purchase Badge', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                // Yes, not the store's setting. Every layout says yes here,
                // for the reason core states where it builds them: a layout
                // that read the store setting would make the two builders
                // disagree about what Classic is. A widget defaulting to
                // anything else would therefore read as Custom the moment it
                // was inserted on a store with the badge switched off.
                //
                // The store still decides whether one is drawn:
                // showVerifiedBadge() reads this and the store setting
                // together, and a store that has turned the badge off draws
                // none whatever this says.
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_view_reply',
            [
                'label'        => esc_html__('Store Reply', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );


        $this->add_control(
            'show_avatar',
            [
                'label'        => esc_html__('Avatar', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'        => esc_html__('Review Title', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_content',
            [
                'label'        => esc_html__('Review Text', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_photos',
            [
                'label'        => esc_html__('Attachments', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_variation',
            [
                'label'        => esc_html__('Variation', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_footer',
            [
                'label'        => esc_html__('Footer (replies and votes)', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_meta',
            [
                'label'        => esc_html__('Meta Line', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'card_order_heading',
            [
                'label'     => esc_html__('Order within the card', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'photos_first',
            [
                'label'        => esc_html__('Attachments Above The Text', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'rating_first',
            [
                'label'        => esc_html__('Stars Above The Name', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'badge_last',
            [
                'label'        => esc_html__('Verified Badge After The Variation', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        // Not shown. A layout writes it and the stylesheet reads it: it is
        // the one part of a card's shape that is a class name rather than a
        // choice, and asking a merchant to type one would be asking them to
        // know the stylesheet.
        $this->add_control(
            'item_class',
            [
                'type'    => Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'media_content_section',
            [
                'label' => esc_html__('Attachments', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'media_visible',
            [
                'label'       => esc_html__('Attachments Shown', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('How many attachments a review shows. The rest go behind a + that opens them in the lightbox. 0 shows all of them.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                // What a review may actually hold. Offering a limit past it
                // would be a number that never comes into play — the store's
                // own upload cap decides how many attachments there can be.
                'max'         => ProductReviewRenderer::maxPhotosPerReview(),
                'default'     => 0,
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'media_more',
            [
                'label'       => esc_html__('The + Counter', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Counts the attachments the limit leaves out and opens them in the lightbox. Hidden, they are simply not shown.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'overlay',
                'options'     => [
                    'overlay' => esc_html__('On the last attachment', 'fluent-cart-elementor-blocks'),
                    'tile'    => esc_html__('Beside the attachments', 'fluent-cart-elementor-blocks'),
                    'none'    => esc_html__('Hidden', 'fluent-cart-elementor-blocks'),
                ],
                // With no limit there is no overflow for it to stand for.
                'condition'   => ['media_visible!' => 0],
            ]
        );

        $this->add_control(
            'media_full_width',
            [
                'label'        => esc_html__('Full Width Attachments', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Each attachment spans the whole review, one to a line. The height still applies.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->addReviewMediaStyleControls();

        $this->add_control(
            'media_width',
            [
                'label'       => esc_html__('Attachment Width (px)', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('0 leaves the width to the theme.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 200,
                // 0 for the same reason as the height: a filled-in default is
                // indistinguishable from a chosen one.
                'default'     => 0,
                // Full width is the width; a pixel one beside it would be a
                // setting with nothing to change.
                'condition' => ['media_full_width!' => 'yes'],
            ]
        );

        $this->add_control(
            'media_height',
            [
                'label'       => esc_html__('Attachment Height (px)', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('0 leaves the height to the theme, and at full width gives each attachment its own proportions, uncropped. Any other height crops it to a band of that depth.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 600,
                // 0, not 72. Elementor fills a control's default into the
                // settings whether or not anyone touched it, so a default of
                // 72 is a height that was always asked for — and full width
                // would crop every attachment to a 72px band with no way to
                // say otherwise. 0 is the same 72px at a fixed size, because
                // that is what the stylesheet falls back to.
                'default'     => 0,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'slider_content_section',
            [
                'label' => esc_html__('Slider', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
                // Said once here rather than on each control inside. Elementor
                // lays a section's args under every control registered in it
                // (handle_control_position(), array_replace_recursive), so
                // each one still carries this condition - and its own, the two
                // being different keys, merge rather than replace.
                'condition' => ['view_mode' => 'slider'],
            ]
        );

        $this->add_control(
            'slider_arrows',
            [
                'label'        => esc_html__('Show arrows', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'slider_arrows_size',
            [
                'label'     => esc_html__('Arrow Size', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'md',
                'options'   => [
                    'sm' => esc_html__('Small', 'fluent-cart-elementor-blocks'),
                    'md' => esc_html__('Medium', 'fluent-cart-elementor-blocks'),
                    'lg' => esc_html__('Large', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['slider_arrows' => 'yes'],
            ]
        );

        $this->add_control(
            'slider_arrows_position',
            [
                'label'       => esc_html__('Arrow Placement', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('On the reviews saves space. Beside them keeps every review clear. Below them suits a narrow column, where side arrows have nowhere to go.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'overlap',
                'options'     => [
                    'overlap' => esc_html__('On the reviews', 'fluent-cart-elementor-blocks'),
                    'outside' => esc_html__('Beside the reviews', 'fluent-cart-elementor-blocks'),
                    'bottom'  => esc_html__('Below the reviews', 'fluent-cart-elementor-blocks'),
                ],
                'condition'   => [
                    'slider_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'slider_pagination',
            [
                'label'        => esc_html__('Show Pagination', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'slider_pagination_type',
            [
                'label'     => esc_html__('Pagination Type', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'bullets',
                'options'   => [
                    'bullets'     => esc_html__('Dots', 'fluent-cart-elementor-blocks'),
                    'fraction'    => esc_html__('Fraction', 'fluent-cart-elementor-blocks'),
                    'progressbar' => esc_html__('Progress Bar', 'fluent-cart-elementor-blocks'),
                    'segmented'   => esc_html__('Segmented', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => [
                    'slider_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'slider_autoplay',
            [
                'label'     => esc_html__('Autoplay', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'no',
                'options'   => [
                    'no'    => esc_html__('Disabled', 'fluent-cart-elementor-blocks'),
                    'yes'   => esc_html__('Always', 'fluent-cart-elementor-blocks'),
                    'hover' => esc_html__('On Hover', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'slider_autoplay_delay',
            [
                'label'       => esc_html__('Autoplay Delay (ms)', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Time between slides in milliseconds', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 300,
                'max'         => 10000,
                'step'        => 100,
                'default'     => 3000,
                'condition'   => ['slider_autoplay' => ['yes', 'hover']],
            ]
        );

        $this->add_control(
            'slider_infinite',
            [
                'label'        => esc_html__('Infinite loop', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->end_controls_section();

        // ── Header and pager ──────────────────────────────
        $this->start_controls_section(
            'header_content_section',
            [
                'label' => esc_html__('Header', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_count',
            [
                'label'        => esc_html__('Review Count', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_filter',
            [
                'label'        => esc_html__('Star Filter Chips', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_sorting',
            [
                'label'        => esc_html__('Sort Control', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'default_sort',
            [
                'label'   => esc_html__('Default Sort', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => ProductReviewListWidget::FALLBACK_SORT,
                'options' => ProductReviewListWidget::sortChoices(),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'pagination_content_section',
            [
                'label' => esc_html__('Pagination', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
                'condition' => ['view_mode' => ['list', 'grid', 'masonry']],
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label'   => esc_html__('Pagination Type', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'numbers',
                'options' => [
                    'numbers'  => esc_html__('Numbers', 'fluent-cart-elementor-blocks'),
                    'fraction' => esc_html__('Fraction', 'fluent-cart-elementor-blocks'),
                    'bullets'  => esc_html__('Bullets', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label'       => esc_html__('Reviews Per Page', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('0 uses the store setting.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 100,
                'default'     => 0,
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

        ReviewStyleControls::registerReviewStyleControls($this, true);
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

        // The settings are the section. A layout is not consulted here at
        // all: choosing one in the panel writes its settings onto the widget
        // and is then done with, which is how the block editor has always
        // worked - a layout builds a row out of blocks and the page renders
        // the blocks, not the layout's name.
        //
        // What that buys is the thing a merchant kept running into: a
        // setting they changed used to argue with a layout at render, and
        // whether it won depended on how the argument was scored. There is
        // no argument now. They changed a setting; the setting is what draws.
        ob_start();
        (new ProductReviewRenderer($product->ID, $this->rendererOptions($settings)))->render();
        $content = ob_get_clean();

        // The layout's own stylesheet keys off this, so it has to say what
        // the settings say rather than what was last chosen. Choose Summary
        // on Top and then put the summary back beside the reviews and the
        // class went on claiming summary-top, styling a layout that was no
        // longer there - the panel called it Custom while the page did not.
        //
        // Asked of the settings, by the same rule the picker uses.
        $preset = ReviewLayoutPresets::detect($settings);

        if ($preset !== '') {
            $content = '<div class="fct-reviews-layout-preset fct-reviews-layout-preset--'
                . esc_attr(sanitize_html_class($preset)) . '">' . $content . '</div>';
        }

        if (trim($content) === '') {
            $this->renderPlaceholder(
                esc_html__('There is nothing to show for this product yet.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-product-reviews fct-product-reviews-block">' . $content . '</div>';
    }

    /**
     * The panel's choices in the shape the renderer reads.
     *
     * Every one of these is an option the renderer already accepted; the
     * widget simply had nothing to pass. The names and the wording match the
     * separate widgets, so the same setting reads the same either way.
     */
    protected function rendererOptions(array $settings): array
    {
        list($sortBy, $sortOrder) = ProductReviewListWidget::splitSort((string) ($settings['default_sort'] ?? ''));

        return [
            'showSummary'       => $this->isOn($settings, 'show_summary'),
            'showFilterChips'   => $this->isOn($settings, 'show_filter'),
            'showSortControls'  => $this->isOn($settings, 'show_sorting'),
            'showReviewerName'  => $this->isOn($settings, 'show_reviewer_name'),
            // The shape of the card. These used to reach the renderer only
            // from a layout, which is why changing one meant abandoning the
            // layout: there was no control to change.
            'summaryMode'       => $this->pick($settings, 'summary_mode', self::SUMMARY_MODES, 'side'),
            'showCount'         => $this->isOn($settings, 'show_count'),
            'showAvatar'        => $this->isOn($settings, 'show_avatar'),
            'showTitle'         => $this->isOn($settings, 'show_title'),
            'showContent'       => $this->isOn($settings, 'show_content'),
            'showVariation'     => $this->isOn($settings, 'show_variation'),
            'showPhotos'        => $this->isOn($settings, 'show_photos'),
            'showFooter'        => $this->isOn($settings, 'show_footer'),
            'showMeta'          => $this->isOn($settings, 'show_meta'),
            'photosFirst'       => $this->isOn($settings, 'photos_first', false),
            'ratingFirst'       => $this->isOn($settings, 'rating_first', false),
            'badgeLast'         => $this->isOn($settings, 'badge_last', false),
            'itemClass'         => sanitize_html_class((string) ($settings['item_class'] ?? '')),
            'showReviewDate'    => $this->isOn($settings, 'show_review_date'),
            'showVerifiedBadge' => $this->showVerifiedBadge($settings),
            'showViewReply'     => $this->isOn($settings, 'show_view_reply'),
            'defaultSortBy'     => $sortBy,
            'defaultSortOrder'  => $sortOrder,
            'perPage'           => $this->pick($settings, 'view_mode', self::VIEW_MODES, 'list') === 'slider'
                ? 0
                : max(0, min(100, absint($settings['per_page'] ?? 0))),
            'minRating'         => max(0, min(5, absint($settings['min_rating'] ?? 0))),
            // Explicitly false: isOn() answers true for an absent key, and a
            // widget saved before this control existed has none.
            'hasMedia'          => $this->isOn($settings, 'photos_only', false),
            'maxWords'          => max(0, min(500, absint($settings['content_max_words'] ?? 0))),
            'paginationType'    => $this->pick($settings, 'pagination_type', self::PAGINATION_TYPES, 'numbers'),
            'viewMode'          => $this->pick($settings, 'view_mode', self::VIEW_MODES, 'list'),
            'gridColumns'       => max(self::MIN_COLUMNS, min(self::MAX_COLUMNS, absint($settings['grid_columns'] ?? 2))),
            'sliderSettings'    => [
                'autoplay'      => $this->pick($settings, 'slider_autoplay', self::AUTOPLAY_MODES, 'no'),
                'autoplayDelay' => max(300, min(10000, absint($settings['slider_autoplay_delay'] ?? 3000))),
                'arrows'        => $this->isOn($settings, 'slider_arrows') ? 'yes' : 'no',
                'arrowsSize'    => $this->pick($settings, 'slider_arrows_size', self::ARROW_SIZES, 'md'),
            'arrowsPosition'=> $this->pick($settings, 'slider_arrows_position', self::ARROW_POSITIONS, 'overlap'),
                // Off by default, unlike the switchers above it.
                'infinite'      => $this->isOn($settings, 'slider_infinite', false) ? 'yes' : 'no',
                'pagination'    => $this->isOn($settings, 'slider_pagination') ? 'yes' : 'no',
                'paginationType'=> $this->pick($settings, 'slider_pagination_type', self::PAGINATION_STYLES, 'bullets'),
            ],
            'container'         => $this->pick($settings, 'container', self::CONTAINERS, 'drawer'),
            'layout'            => $this->pick($settings, 'layout', self::LAYOUTS, 'inline'),
            'ctaAddText'        => sanitize_text_field((string) ($settings['add_review_button_text'] ?? '')),
            'ctaEditText'       => sanitize_text_field((string) ($settings['edit_review_button_text'] ?? '')),
            'ctaLoginText'      => sanitize_text_field((string) ($settings['login_review_button_text'] ?? '')),
        ] + $this->reviewMediaOptions(
            $settings,
            // Explicitly false: isOn() here answers true for a key that is
            // absent, and a widget saved before this control existed has no
            // key — which would turn full width on for every one of them.
            $this->isOn($settings, 'media_full_width', false)
        );
    }

    /**
     * A switcher that has never been touched has no stored value, so an
     * absent key means the control's default rather than off.
     */
    protected function isOn(array $settings, string $key, bool $fallback = true): bool
    {
        return array_key_exists($key, $settings) ? ($settings[$key] === 'yes') : $fallback;
    }

    protected function pick(array $settings, string $key, array $allowed, string $fallback): string
    {
        $value = (string) ($settings[$key] ?? $fallback);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
