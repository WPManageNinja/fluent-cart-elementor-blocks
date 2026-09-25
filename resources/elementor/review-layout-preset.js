(function () {
    'use strict';

    /**
     * The Layout Preset control on the review widgets.
     *
     * Elementor has no notion of one control writing others, so the panel does
     * it: choosing a preset writes the settings it names and then stops being
     * involved. Every value stays editable afterwards, which is what makes a
     * preset a starting point rather than a rule — and why the control cannot
     * simply be read at render time instead.
     *
     * Data comes from window.fceReviewLayoutPresets (localized in
     * ElementorIntegration). Nothing here decides what is allowed: core refuses
     * a Pro view mode on render whatever is saved.
     */

    var data = window.fceReviewLayoutPresets || {};
    var presets = data.presets || {};
    var keys = data.keys || [];
    var widgets = data.widgets || [];

    if (!keys.length || typeof window.elementor === 'undefined') {
        return;
    }

    /**
     * Applying a preset writes every key it names, and clears the rest back to
     * the control's own default. Without the clearing step a preset would be a
     * patch: pick Card Grid after Carousel and the arrows would still be on,
     * because nothing had turned them off.
     */
    function settingsFor(id, settings) {
        var preset = presets[id];

        if (!preset) {
            return null;
        }

        // Only keys this widget actually has a control for. The two review
        // widgets do not carry the same set — the list has no summary toggle,
        // for one — and writing a setting with no control behind it stores a
        // value nothing will ever read. Asked of the model rather than kept as
        // a per-widget list in PHP, which would be a third place the controls
        // are described.
        var controls = settings.controls || {};
        var next = {};

        keys.forEach(function (key) {
            if (!controls[key]) {
                return;
            }

            next[key] = Object.prototype.hasOwnProperty.call(preset, key) ? preset[key] : '';
        });

        return next;
    }

    elementor.hooks.addAction('panel/open_editor/widget', function (panel, model) {
        if (widgets.indexOf(model.get('widgetType')) === -1) {
            return;
        }

        var settings = model.get('settings');

        // Namespaced so re-opening the panel does not stack a second handler on
        // the same model — Elementor reuses these between openings.
        settings.off('change:layout_preset.fcePresets');
        settings.on('change:layout_preset.fcePresets', function () {
            var next = settingsFor(settings.get('layout_preset'), settings);

            if (!next) {
                // 'Custom' is where a preset lands once its values are edited.
                // Choosing it deliberately should change nothing.
                return;
            }

            // One history entry, so undo steps back over the whole layout
            // rather than through fifteen separate settings.
            $e.run('document/elements/settings', {
                container: panel.getOption('container') || elementor.getCurrentElement().getContainer(),
                settings: next,
                options: {external: true},
            });
        });
    });
}());
