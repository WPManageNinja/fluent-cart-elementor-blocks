/**
 * Keeps the Attributes picker in the order you pick: Elementor saves a multi-select in option order.
 * A picked option moves last before Select2 selects it; saved values are put back in order on open.
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

    // The saved ids go to the end of the list, in their saved order. Options are matched by value, never by
    // a selector built from the saved text.
    function restoreOrder(select, settings) {
        var saved = settings.get(select.getAttribute('data-setting'));

        if (!saved || !saved.length) {
            return;
        }

        var byValue = {};
        [].forEach.call(select.options, function (option) {
            byValue[option.value] = option;
        });

        [].concat(saved).forEach(function (id) {
            var option = byValue[String(id)];

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

        // The panel links the control to its container a moment after it is drawn; try again until it does.
        var settings = settingsFor(select);

        var tries = Number(select.getAttribute('data-fct-pick-tries') || 0);

        if (!settings) {
            if (tries < 20) {
                select.setAttribute('data-fct-pick-tries', String(tries + 1));
                window.setTimeout(schedule, 100);
            }
            return;
        }

        select.setAttribute('data-fct-pick-order', '1');
        restoreOrder(select, settings);

        // Undo, redo and a reset set the saved value again: put the order back after each of them.
        settings.on('change:' + select.getAttribute('data-setting'), function () {
            restoreOrder(select, settings);
        });

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

    schedule();
    document.addEventListener('DOMContentLoaded', schedule);
}());
