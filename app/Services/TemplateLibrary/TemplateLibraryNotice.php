<?php

namespace FluentCartElementorBlocks\App\Services\TemplateLibrary;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The Saved Templates screen offers to add back the bundled FluentCart
 * templates that are not in the library.
 *
 * The seeder never re-creates a template a merchant deleted, so this is the
 * one place a missing one comes back from, and it does so only on a click.
 * Shown on that screen alone: it is where a merchant is when they notice a
 * template is gone, and the lookup behind it runs nowhere else.
 */
class TemplateLibraryNotice
{
    const ACTION = 'fluent_cart_elementor_add_missing_templates';
    const UPDATE_ACTION = 'fluent_cart_elementor_update_template';
    const ADDED_QUERY = 'fct_templates_added';
    const UPDATED_QUERY = 'fct_template_updated';
    const SCREEN = 'edit-elementor_library';

    public function register()
    {
        add_action('admin_notices', [$this, 'render']);
        // WordPress drops the result flags from the address bar once the page
        // has loaded, so a refresh does not show a success notice again.
        add_filter('removable_query_args', [$this, 'removableQueryArgs']);
        add_action('admin_post_' . self::ACTION, [$this, 'handle']);
        // Late, so the link comes last in the row, after the builder's own
        // Edit with … action.
        add_filter('post_row_actions', [$this, 'rowActions'], 100, 2);
        add_action('admin_post_' . self::UPDATE_ACTION, [$this, 'handleUpdate']);
        add_action('admin_footer', [$this, 'confirmScript']);
    }

    public function removableQueryArgs($args)
    {
        $args[] = self::ADDED_QUERY;
        $args[] = self::UPDATED_QUERY;

        return $args;
    }

    /**
     * "Update to 1.0.1" beside View, on a seeded template whose bundled copy
     * is newer. The confirm names what the click replaces.
     *
     * @param array $actions
     * @param \WP_Post $post
     * @return array
     */
    public function rowActions($actions, $post)
    {
        if (!$post instanceof \WP_Post || $post->post_type !== TemplateSeeder::CPT || !current_user_can('edit_theme_options')) {
            return $actions;
        }

        foreach ($this->outdated() as $entry) {
            if ((int) $entry['post_id'] !== (int) $post->ID) {
                continue;
            }

            $url = wp_nonce_url(
                add_query_arg(['action' => self::UPDATE_ACTION, 'post' => $post->ID], admin_url('admin-post.php')),
                self::UPDATE_ACTION . '_' . $post->ID
            );
            $confirm = sprintf(
                /* translators: 1: template title, 2: bundled version */
                __('Replace "%1$s" with the bundled version %2$s? Changes you made to this template are lost. Pages built from a copy of it are not affected.', 'fluent-cart-elementor-blocks'),
                $entry['template']['title'],
                $entry['template']['version']
            );
            // The confirm is Elementor's own dialog (see confirmScript()); the
            // native one stands in when that is not on the page.
            $actions['fct_update'] = sprintf(
                '<a href="%s" class="fct-template-update" data-title="%s" data-message="%s" onclick="return window.fctTemplateUpdateConfirm ? window.fctTemplateUpdateConfirm(this) : confirm(this.dataset.message);">%s</a>',
                esc_url($url),
                esc_attr($entry['template']['title']),
                esc_attr($confirm),
                esc_html(sprintf(
                    /* translators: %s: bundled version */
                    __('Update to %s', 'fluent-cart-elementor-blocks'),
                    $entry['template']['version']
                ))
            );
            break;
        }

        return $actions;
    }

    /**
     * The confirm behind the Update row action, drawn with Elementor's own
     * dialog so it looks like the rest of the screen. Only on the Saved
     * Templates screen, and only when Elementor's dialog manager is there.
     */
    public function confirmScript()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== self::SCREEN || !current_user_can('edit_theme_options')) {
            return;
        }
        ?>
        <script>
        (function () {
            var dialog = null;
            var href = '';

            // One widget for the screen, re-pointed at whichever row was
            // clicked; a widget per click would stack hidden copies.
            window.fctTemplateUpdateConfirm = function (link) {
                if (!window.elementorCommon || !elementorCommon.dialogsManager) {
                    return confirm(link.dataset.message);
                }
                href = link.href;
                if (!dialog) {
                    dialog = elementorCommon.dialogsManager.createWidget('confirm', {
                        id: 'fct-template-update-confirm',
                        headerMessage: <?php echo wp_json_encode(__('Update template', 'fluent-cart-elementor-blocks')); ?>,
                        position: { my: 'center', at: 'center' },
                        strings: {
                            confirm: <?php echo wp_json_encode(__('Update', 'fluent-cart-elementor-blocks')); ?>,
                            cancel: <?php echo wp_json_encode(__('Cancel', 'fluent-cart-elementor-blocks')); ?>
                        },
                        onConfirm: function () {
                            window.location.href = href;
                        }
                    });
                }
                // setMessage() takes HTML; hand it a text node so the message is
                // shown as written, whatever a template title holds.
                dialog.setMessage(jQuery('<span>').text(link.dataset.message));
                dialog.show();
                return false;
            };
        })();
        </script>
        <?php
    }

    public function handleUpdate()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_die(esc_html__('You are not allowed to update templates.', 'fluent-cart-elementor-blocks'), 403);
        }
        $postId = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        check_admin_referer(self::UPDATE_ACTION . '_' . $postId);

        $updated = null;
        foreach ($this->outdated() as $entry) {
            if ((int) $entry['post_id'] === $postId) {
                $updated = (new TemplateLibrary())->updateTemplate($entry['template']['slug']);
                break;
            }
        }

        wp_safe_redirect(add_query_arg(
            [
                'post_type'         => 'elementor_library',
                'tabs_group'        => 'library',
                self::UPDATED_QUERY => $updated ? $updated['post_id'] : 0,
            ],
            admin_url('edit.php')
        ));
        exit;
    }

    /** The outdated list, looked up once per request however many rows ask. */
    private function outdated()
    {
        static $outdated = null;
        if ($outdated === null) {
            $outdated = (new TemplateLibrary())->outdatedTemplates();
        }

        return $outdated;
    }

    public function render()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== self::SCREEN || !current_user_can('edit_theme_options')) {
            return;
        }

        $added = isset($_GET[self::ADDED_QUERY]) ? absint($_GET[self::ADDED_QUERY]) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($added) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    /* translators: %d: number of templates added */
                    _n('%d FluentCart template added to your library.', '%d FluentCart templates added to your library.', $added, 'fluent-cart-elementor-blocks'),
                    $added
                ))
            );
        }

        $updatedId = isset($_GET[self::UPDATED_QUERY]) ? absint($_GET[self::UPDATED_QUERY]) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($updatedId) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    /* translators: 1: template title, 2: bundled version */
                    __('"%1$s" updated to version %2$s.', 'fluent-cart-elementor-blocks'),
                    get_the_title($updatedId),
                    TemplateSeeder::installedVersion($updatedId)
                ))
            );
        }

        $missing = (new TemplateLibrary())->missingTemplates();
        if (!$missing) {
            return;
        }

        $titles = implode(', ', array_map(function ($template) {
            return $template['title'];
        }, $missing));
        ?>
        <div class="notice notice-info">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:8px 0;">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
                <?php wp_nonce_field(self::ACTION); ?>
                <span>
                    <?php
                    echo esc_html(sprintf(
                        /* translators: 1: number of templates, 2: their titles */
                        _n('%1$d FluentCart template is not in your library: %2$s.', '%1$d FluentCart templates are not in your library: %2$s.', count($missing), 'fluent-cart-elementor-blocks'),
                        count($missing),
                        $titles
                    ));
                    ?>
                </span>
                <button type="submit" class="button button-secondary"><?php esc_html_e('Add missing templates', 'fluent-cart-elementor-blocks'); ?></button>
            </form>
        </div>
        <?php
    }

    public function handle()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_die(esc_html__('You are not allowed to add templates.', 'fluent-cart-elementor-blocks'), 403);
        }
        check_admin_referer(self::ACTION);

        $created = (new TemplateLibrary())->seedMissing();

        wp_safe_redirect(add_query_arg(
            [
                'post_type'      => 'elementor_library',
                'tabs_group'     => 'library',
                self::ADDED_QUERY => count($created),
            ],
            admin_url('edit.php')
        ));
        exit;
    }
}
