/**
 * The panel-side guard on features this site's licence cannot draw.
 *
 * Two marks, both put on a control by the widget that registered it:
 *
 *   fct-control-pro-only    the whole control. Its input is disabled.
 *   fct-control-pro-options some of a select's options, named one per class
 *                           as fct-pro-option-<value>. Those are disabled.
 *
 * The editor stylesheet dims both, and dimming is not disabling: the field
 * inside a dimmed control is still a real one, so it stays in the tab order
 * and a keyboard reaches whatever the pointer cannot. Storing a setting that
 * will not draw would leave the widget one licence away from changing the
 * page on its own, so the field itself is disabled here.
 *
 * Elementor builds the panel a section at a time, as a merchant opens them,
 * and rebuilds it wholesale when they select another widget. There is no one
 * moment to do this at and no element that is reliably present to watch, so
 * the observer goes on the document and the work is deferred to a frame. The
 * pass costs two querySelectorAll calls for selectors a panel matches a
 * handful of times or not at all.
 */
(function () {
    'use strict';

    var PRO_ONLY_CONTROL = '.fct-control-pro-only';
    var PRO_ONLY_FIELDS = PRO_ONLY_CONTROL + ' input, '
        + PRO_ONLY_CONTROL + ' select, '
        + PRO_ONLY_CONTROL + ' textarea';

    var PRO_OPTION_CONTROL = '.fct-control-pro-options';
    var PRO_OPTION_PREFIX = 'fct-pro-option-';

    var queued = false;

    function disable(field) {
        if (!field.disabled) {
            field.disabled = true;
            field.setAttribute('aria-disabled', 'true');
        }
    }

    /** Whole controls Pro draws. */
    function disableProOnlyFields() {
        var fields = document.querySelectorAll(PRO_ONLY_FIELDS);

        for (var i = 0; i < fields.length; i++) {
            disable(fields[i]);
        }
    }

    /**
     * Single options Pro draws, left in the list rather than taken out of it:
     * a choice that has disappeared looks like something the plugin cannot
     * do, while one that is there and cannot be picked is an invitation.
     */
    function disableProOnlyOptions() {
        var controls = document.querySelectorAll(PRO_OPTION_CONTROL);

        for (var i = 0; i < controls.length; i++) {
            var classes = controls[i].className.split(/\s+/);

            for (var c = 0; c < classes.length; c++) {
                if (classes[c].indexOf(PRO_OPTION_PREFIX) !== 0) {
                    continue;
                }

                var value = classes[c].slice(PRO_OPTION_PREFIX.length);
                var options = controls[i].querySelectorAll(
                    'option[value="' + value.replace(/"/g, '') + '"]'
                );

                for (var o = 0; o < options.length; o++) {
                    disable(options[o]);
                }
            }
        }
    }

    function run() {
        queued = false;

        disableProOnlyFields();
        disableProOnlyOptions();
    }

    // One pass per frame however many mutations that frame brought, so opening
    // a section is one pass rather than one per control it inserted.
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

    if (window.MutationObserver) {
        new window.MutationObserver(schedule).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    }

    // For the controls already on the page, whenever this script happens to
    // run relative to them.
    schedule();
    document.addEventListener('DOMContentLoaded', schedule);
}());
