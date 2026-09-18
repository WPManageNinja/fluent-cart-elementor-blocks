(function() {
    'use strict';

    /**
     * TinyMCE plugin: "Short Codes" toolbar dropdown for the Order Receipt
     * widget's Message control (Elementor panel WYSIWYG).
     *
     * Registered on Elementor's base editor config via the mce_external_plugins
     * / mce_buttons PHP filters (ElementorIntegration), which Elementor clones
     * into every panel WYSIWYG — so the button hides itself unless the widget
     * being edited is the Order Receipt. Code data comes from
     * window.fceReceiptShortCodes (localized from core's EditorShortCodeHelper).
     */

    if (typeof tinymce === 'undefined') {
        return;
    }

    tinymce.PluginManager.add('fce_shortcodes', function(editor) {
        var data = window.fceReceiptShortCodes || {};
        var groups = data.groups || [];

        if (!groups.length) {
            return;
        }

        var editingReceiptWidget = function() {
            try {
                return window.elementor
                    .getPanelView()
                    .getCurrentPageView()
                    .model.get('widgetType') === 'fluent_cart_receipt';
            } catch (e) {
                return false;
            }
        };

        // Compact "{{:}}" glyph as the button face — reads as "insert a
        // short code" and stays as small as the native toolbar buttons (a
        // full text label wraps the narrow panel toolbar onto its own row).
        editor.addButton('fce_shortcodes', {
            type: 'menubutton',
            text: '{{:}}',
            icon: false,
            classes: 'widget btn fce-shortcodes',
            tooltip: data.buttonLabel || 'Short Codes',
            menu: groups.map(function(group) {
                // Group titles come from core's registry as-is and are not
                // consistently capitalized (e.g. "transaction").
                var title = String(group.title || '');

                return {
                    text: title.charAt(0).toUpperCase() + title.slice(1),
                    menu: (group.codes || []).map(function(item) {
                        return {
                            text: item.label,
                            onclick: function() {
                                editor.insertContent(item.code);
                            }
                        };
                    })
                };
            }),
            onPostRender: function() {
                var el = this.getEl();

                // The base config is shared by every panel WYSIWYG — only the
                // Order Receipt widget gets the button.
                if (!editingReceiptWidget()) {
                    if (el && el.parentNode) {
                        el.parentNode.style.display = 'none';
                    }
                    return;
                }

                // Fixed dark glyph in EVERY button state — inline+!important
                // so the skin's hover/active styles can never wash it out.
                var txt = el ? el.querySelector('.mce-txt') : null;
                if (txt) {
                    txt.style.setProperty('color', '#1f2124', 'important');
                    txt.style.setProperty('font-size', '11px', 'important');
                    txt.style.setProperty('font-weight', '700', 'important');
                    txt.style.setProperty('letter-spacing', '0.5px', 'important');
                }

                // Flex-center the label + caret so the arrow lines up with
                // the Paragraph dropdown's — the smaller glyph font otherwise
                // leaves the caret sitting off-baseline.
                var inner = el ? el.querySelector('button') : null;
                if (inner) {
                    inner.style.setProperty('display', 'inline-flex', 'important');
                    inner.style.setProperty('align-items', 'center', 'important');
                }

                var caret = el ? el.querySelector('.mce-caret') : null;
                if (caret) {
                    caret.style.setProperty('margin-top', '0', 'important');
                    caret.style.setProperty('margin-left', '4px', 'important');
                }
            }
        });
    });
})();
