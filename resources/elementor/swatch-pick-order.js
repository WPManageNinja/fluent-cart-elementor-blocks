/**
 * The swatch Attributes picker keeps the order you pick, as Gutenberg's list does.
 *
 * Elementor saves a multi-select in the order of its options, so picking Size then Color saved Color
 * first. The widget marks the picker with fct-swatch-pick-order. Here a picked option moves to the end
 * of the list before it is selected, so the saved value and the chips follow the clicks. When the panel
 * opens, the options are put back in the saved order.
 */
(function () {
    'use strict';

    var CONTROL = '.elementor-control.fct-swatch-pick-order';
    var queued = false;

    // The deepest panel view holding the picker is its control view; its container has the settings.
    function settingsFor(node) {
        var panel = window.elementor && window.elementor.getPanelView && window.elementor.getPanelView();
        var view = panel && panel.getCurrentPageView && panel.getCurrentPageView();
        var found = null;

        function walk(current) {
            if (!current || !current.el || !current.el.contains(node)) {
                return;
            }
            if (current.container && current.container.settings) {
                found = current.container.settings;
            }
            if (current.children && typeof current.children.each === 'function') {
                current.children.each(walk);
            }
        }

        walk(view);

        return found;
    }

    // The saved ids, in their saved order, go to the end of the list, so the chips show that order.
    function restoreOrder(select) {
        var settings = settingsFor(select);
        var saved = settings && settings.get(select.getAttribute('data-setting'));

        if (!saved || !saved.length) {
            return;
        }

        [].concat(saved).forEach(function (id) {
            var option = select.querySelector('option[value="' + String(id).replace(/"/g, '') + '"]');

            if (option) {
                select.appendChild(option);
            }
        });

        // Only Select2 redraws; Elementor's own change handler would save the same value again.
        window.jQuery(select).trigger('change.select2');
    }

    function bind(control) {
        var select = control.querySelector('select[data-setting]');

        if (!select || select.getAttribute('data-fct-pick-order') || !window.jQuery) {
            return;
        }

        select.setAttribute('data-fct-pick-order', '1');
        restoreOrder(select);

        // Before Select2 selects it: the option moves last, so the change Elementor saves has it last.
        window.jQuery(select).on('select2:selecting', function (event) {
            var option = event.params && event.params.args && event.params.args.data && event.params.args.data.element;

            if (option && option.parentNode === select) {
                select.appendChild(option);
            }
        });
    }

    function run() {
        queued = false;

        var controls = document.querySelectorAll(CONTROL);

        for (var i = 0; i < controls.length; i++) {
            bind(controls[i]);
        }
    }

    function schedule() {
        if (queued) {
            return;
        }

        queued = true;

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(run);
        } else {
            window.setTimeout(run, 16);
        }
    }

    // Only a mutation that brings in a picker needs a pass; the editor's other churn does not.
    function addsControl(mutations) {
        for (var m = 0; m < mutations.length; m++) {
            var added = mutations[m].addedNodes;

            for (var n = 0; n < added.length; n++) {
                var node = added[n];

                if (node.nodeType === 1 && ((node.matches && node.matches(CONTROL)) || (node.querySelector && node.querySelector(CONTROL)))) {
                    return true;
                }
            }
        }

        return false;
    }

    if (window.MutationObserver) {
        new window.MutationObserver(function (mutations) {
            if (addsControl(mutations)) {
                schedule();
            }
        }).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    }

    // Undo and redo set the saved value back without moving the options, so the order is restored after them.
    if (window.$e && window.$e.commands && typeof window.$e.commands.on === 'function') {
        window.$e.commands.on('run:after', function (component, command) {
            if (String(command).indexOf('document/history') === 0) {
                // Elementor re-applies the saved value to the select just after the command, so wait for it.
                window.setTimeout(function () {
                    var selects = document.querySelectorAll(CONTROL + ' select[data-fct-pick-order]');

                    for (var i = 0; i < selects.length; i++) {
                        restoreOrder(selects[i]);
                    }
                }, 60);
            }
        });
    }

    schedule();
    document.addEventListener('DOMContentLoaded', schedule);
}());
