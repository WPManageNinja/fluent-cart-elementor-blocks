/**
 * Quick-pick chips under a swatch row's Label Name, as in the Gutenberg swatches settings.
 *
 * The widget marks the text control with fct-swatch-label-chips. Clicking a chip fills the field and
 * fires the events Elementor listens to, so the value is saved like a typed one. The chip that matches
 * the field is marked pressed. Elementor builds the panel a section at a time, so the work runs from an
 * observer, once per frame.
 */
(function () {
    'use strict';

    var CONTROL = '.elementor-control.fct-swatch-label-chips';
    var CHIPS_CLASS = 'fct-label-chips';
    var queued = false;

    function presets() {
        var data = window.fluentCartElementor && window.fluentCartElementor.i18n;

        return (data && data.labelPresets) || [];
    }

    function fire(input, type) {
        input.dispatchEvent(new Event(type, {bubbles: true}));
    }

    function markPressed(wrap, input) {
        var chips = wrap.querySelectorAll('.fct-label-chip');

        for (var i = 0; i < chips.length; i++) {
            chips[i].setAttribute('aria-pressed', chips[i].textContent === input.value ? 'true' : 'false');
        }
    }

    // Undo, the history panel and a reset rewrite the field without firing an input event.
    function refreshAll() {
        var wraps = document.querySelectorAll(CONTROL + ' .' + CHIPS_CLASS);

        for (var i = 0; i < wraps.length; i++) {
            var input = wraps[i].closest(CONTROL).querySelector('input[type="text"]');

            if (input) {
                markPressed(wraps[i], input);
            }
        }
    }

    function addChips(control) {
        var input = control.querySelector('input[type="text"]');
        var names = presets();

        if (!input || !names.length || control.querySelector('.' + CHIPS_CLASS)) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = CHIPS_CLASS;
        wrap.setAttribute('role', 'group');
        wrap.setAttribute('aria-label', (window.fluentCartElementor.i18n.labelPresetsTitle) || '');

        names.forEach(function (name) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'fct-label-chip';
            chip.textContent = name;
            chip.addEventListener('click', function () {
                input.value = name;
                fire(input, 'input');
                fire(input, 'change');
                markPressed(wrap, input);
            });
            wrap.appendChild(chip);
        });

        input.addEventListener('input', function () {
            markPressed(wrap, input);
        });

        // Below the field, above its description.
        var field = control.querySelector('.elementor-control-field');

        if (field) {
            field.insertAdjacentElement('afterend', wrap);
        } else {
            control.appendChild(wrap);
        }

        markPressed(wrap, input);
    }

    function run() {
        queued = false;

        var controls = document.querySelectorAll(CONTROL);

        for (var i = 0; i < controls.length; i++) {
            addChips(controls[i]);
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

    // Only a mutation that brings in a matching control needs a pass; the editor's other churn does not.
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

    if (window.$e && window.$e.commands && typeof window.$e.commands.on === 'function') {
        window.$e.commands.on('run:after', refreshAll);
    }

    schedule();
    document.addEventListener('DOMContentLoaded', schedule);
}());
