<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits;

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewSupport;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * What every review widget shares on top of ProductWidgetTrait: it is hidden
 * outright on a core with no review feature, and it explains itself in the
 * editor instead of rendering nothing when the store or the product has
 * reviews switched off.
 *
 * Panel visibility and render-time availability are separate on purpose. A
 * core without the feature hides the widget, because offering a control that
 * could never work is worse than an absent widget. A store that merely has
 * the module switched off keeps the widget listed and says so on the canvas —
 * the merchant can go and switch it on, and an existing page keeps its widget
 * rather than silently losing it.
 */
trait ReviewWidgetTrait
{
    use ProductWidgetTrait {
        show_in_panel as productShowInPanel;
    }

    public function show_in_panel()
    {
        if (!ReviewSupport::isSupportedByCore()) {
            return false;
        }

        return $this->productShowInPanel();
    }

    /**
     * Render the editor notice for whatever is stopping reviews, and say
     * whether anything was. The front end stays silent: a shopper should see
     * an absent section, never a diagnostic.
     *
     * @param int $postId 0 to skip the per-product question.
     * @return bool true when a reason was found (the caller should return).
     */
    protected function renderReviewsUnavailable($postId = 0): bool
    {
        $reason = ReviewSupport::unavailableReason($postId);

        if ($reason === '') {
            return false;
        }

        $this->renderPlaceholder($reason);

        return true;
    }
}
