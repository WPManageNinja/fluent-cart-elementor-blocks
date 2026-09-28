/**
 * The panel-side guard on features this site's licence cannot draw.
 *
 * A widget marks one with the `fct-control-pro-only` class and the editor
 * stylesheet dims it. Dimming is not disabling, though: the switcher inside is
 * still a real input, so it stays in the tab order and a keyboard reaches it
 * whatever the pointer can do. Storing a style that cannot draw would leave
 * the widget one licence away from silently changing the page, so the input
 * itself is disabled here.
 *
 * Elementor builds the panel a section at a time, as a merchant opens them,
 * and rebuilds it wholesale when they select another widget. There is no one
 * moment to do this at and no element that is reliably present to watch, so
 * the observer goes on the document and the work is deferred to a frame. The
 * pass costs one querySelectorAll for a selector a panel matches twice or not
 * at all.
 */
(function () {
    'use strict';

    var PRO_ONLY_CONTROL = '.fct-control-pro-only';
    var SELECTOR = PRO_ONLY_CONTROL + ' input, '
        + PRO_ONLY_CONTROL + ' select, '
        + PRO_ONLY_CONTROL + ' textarea';

    var queued = false;

    function disableProOnlyControls() {
        queued = false;

        var fields = document.querySelectorAll(SELECTOR);

        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].disabled) {
                fields[i].disabled = true;
                fields[i].setAttribute('aria-disabled', 'true');
            }
        }
    }

    // One pass per frame however many mutations that frame brought, so opening
    // a section is one pass rather than one per control it inserted.
    function schedule() {
        if (queued) {
            return;
        }

        queued = true;

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(disableProOnlyControls);
        } else {
            window.setTimeout(disableProOnlyControls, 16);
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
