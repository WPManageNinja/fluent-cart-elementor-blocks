/**
 * The layout picker's behaviour.
 *
 * Almost none, deliberately. The cards are labels over radio inputs, so the
 * browser does the choosing, the arrow keys, the focus ring and the disabled
 * state, and Elementor's own base view does the saving. What is left is the
 * two things a radio group cannot do for itself: check the card matching a
 * value that was saved earlier, and filter the grid by category.
 */
(function ($) {
    'use strict';

    var initialised = false;

    function init() {
        if (initialised || !window.elementor || !elementor.modules || !elementor.modules.controls) {
            return;
        }

        var ControlBaseDataView = elementor.modules.controls.BaseData;

        if (!ControlBaseDataView) {
            return;
        }

        initialised = true;

        var ReviewLayoutPicker = ControlBaseDataView.extend({
            ui: function () {
                var ui = ControlBaseDataView.prototype.ui.apply(this, arguments);

                ui.inputs = '.fct-el-preset-input';
                ui.custom = '.fct-el-preset-custom';
                ui.tabs = '.fct-el-preset-tab';
                ui.items = '.fct-el-preset-item';
                ui.help = '.fct-el-preset-help';

                return ui;
            },

            events: function () {
                return _.extend(ControlBaseDataView.prototype.events.apply(this, arguments), {
                    'change @ui.inputs': 'onBaseInputChange',
                    'click @ui.tabs': 'onTabClick',
                });
            },

            /**
             * Elementor hands the saved value to every control on render. A
             * radio group needs it as a checked input rather than as a value,
             * which is the one thing the base view cannot do for us.
             */
            applySavedValue: function () {
                // What the settings are, not what the control remembers. Empty
                // is a real answer here - it is Custom - so an unset control
                // and a control set to Custom land in the same place, and the
                // selector below never goes looking for [value="undefined"].
                var value = this.layoutInForce();

                this.ui.inputs.prop('checked', false);
                this.ui.inputs.filter('[value="' + value + '"]').prop('checked', true);

                // No layout matches, so no card is ticked and the line above
                // them is what says so.
                if (this.ui.custom && this.ui.custom.length) {
                    this.ui.custom.toggleClass('is-current', value === '');
                }

                this.showHelp(value);
            },

            /** The chosen layout's own sentence, under the grid. */
            showHelp: function (value) {
                if (!this.ui.help || !this.ui.help.length) {
                    return;
                }

                var layouts = this.model.get('layouts') || [];
                var help = '';

                value = value || '';

                for (var i = 0; i < layouts.length; i++) {
                    if (layouts[i].value === value) {
                        help = layouts[i].help || '';
                        break;
                    }
                }

                this.ui.help.text(help);
            },

            onBaseInputChange: function (event) {
                ControlBaseDataView.prototype.onBaseInputChange.apply(this, arguments);

                var value = $(event.currentTarget).val() || '';

                this.applyLayout(value);
                this.showHelp(value);
            },

            /**
             * Choosing a layout writes it onto the widget.
             *
             * Only a layout can be chosen. Custom is a status line, not a
             * radio: there is nothing for it to write, the settings being the
             * only thing anything reads.
             *
             * The block editor builds the row out of the layout's blocks and
             * then forgets the layout; nothing reads its name when the page
             * renders. This is the same move in a builder with no blocks: the
             * layout's settings become the widget's settings, and the section
             * is drawn from those alone.
             *
             * Tuning is not written. How many to a row, how many to a page,
             * which arrows and which order are the merchant's, and they
             * follow them from one layout to the next - which is also why
             * changing one leaves the layout's name alone, while changing
             * anything else makes this a Custom layout.
             */
            applyLayout: function (value) {
                var layout = this.findLayout(value);

                if (!layout || !layout.settings || _.isEmpty(layout.settings)) {
                    return;
                }

                var container = this.container;

                if (!container || !window.$e) {
                    return;
                }

                var settings = _.extend({}, layout.settings, layout.tuning, this.tuningToCarry());

                this.applying = true;

                window.$e.run('document/elements/settings', {
                    container: container,
                    settings: settings,
                });

                this.applying = false;
            },

            /**
             * The tuning that belongs to the merchant rather than to the
             * layout they are leaving.
             *
             * A value still equal to the outgoing layout's is that layout's
             * opinion and stays behind; anything else was set by hand and
             * comes along. Leaving no layout - Custom - means every tuning
             * value was set by hand, so all of it comes along.
             *
             * The block editor decides this the same way, in tunedAttributes().
             */
            tuningToCarry: function () {
                var settings = this.container && this.container.settings;

                if (!settings) {
                    return {};
                }

                var leaving = this.findLayout(this.layoutInForce());

                // The layout being left answers "was this chosen or has it
                // always said that". Leaving Custom there is no layout to
                // ask, so the controls' own defaults answer instead - which
                // is the block editor falling back to a block's registered
                // default when no preset matches.
                var leavingTuning = (leaving && !_.isEmpty(leaving.tuning))
                    ? leaving.tuning
                    : (this.model.get('tuningDefaults') || {});

                var carried = {};

                _.each(this.model.get('tuningKeys') || [], function (key) {
                    var has = settings.get(key);

                    if (typeof has === 'undefined') {
                        return;
                    }

                    if (_.has(leavingTuning, key) && String(has) === String(leavingTuning[key])) {
                        return;
                    }

                    carried[key] = has;
                });

                return carried;
            },

            findLayout: function (value) {
                return _.find(this.model.get('layouts') || [], function (layout) {
                    return (layout.value || '') === (value || '');
                });
            },

            /**
             * Which layout the widget's settings actually are.
             *
             * The saved name is a record of what was last applied, not a
             * claim about what the settings say now - change the view mode
             * and it is no longer Card Grid, whatever the name remembers.
             * The block editor reads the blocks back for the same reason and
             * answers `custom` when none of its layouts match them.
             */
            layoutInForce: function () {
                var settings = this.container && this.container.settings;

                if (!settings) {
                    return this.getControlValue() || '';
                }

                var match = _.find(this.model.get('layouts') || [], function (layout) {
                    if (!layout.value || _.isEmpty(layout.settings)) {
                        return false;
                    }

                    return _.every(layout.settings, function (want, key) {
                        var has = settings.get(key);

                        // Elementor stores an untouched switcher as undefined
                        // and an off one as '', and the two mean the same
                        // thing to a renderer that reads them as booleans.
                        return String(typeof has === 'undefined' ? '' : has) === String(want);
                    });
                });

                return match ? match.value : '';
            },

            onTabClick: function (event) {
                var $tab = $(event.currentTarget);
                var category = $tab.data('category');

                this.ui.tabs.removeClass('is-active');
                $tab.addClass('is-active');

                this.ui.items.each(function () {
                    var item = $(this);

                    // Custom is not in any category and is not filtered out of
                    // one: it is the way back out of a layout, and hiding it
                    // behind the All tab would hide the exit.
                    if (item.hasClass('fct-el-preset-item--custom')) {
                        return;
                    }

                    item.toggle(category === 'all' || item.data('category') === category);
                });
            },

            onReady: function () {
                // The first tab is "all", so the grid starts whole.
                this.ui.tabs.first().addClass('is-active');

                // Any other control changing can change the answer to "which
                // layout is this". Switch the view mode and the section stops
                // being Card Grid at that moment, not when the panel is next
                // opened, so the picker listens to the whole widget rather
                // than only to itself.
                if (this.container && this.container.settings) {
                    this.listenTo(this.container.settings, 'change', this.onWidgetSettingsChange);
                }
            },

            onWidgetSettingsChange: function (model) {
                // Not while this control is the one doing the writing: it has
                // already checked the card the merchant clicked, and applying
                // a layout changes a dozen settings one after another.
                if (this.applying || (model && model.changed && 'layout_preset' in model.changed)) {
                    return;
                }

                this.applySavedValue();
            },
        });

        elementor.addControlView('fluent_review_layout_preset', ReviewLayoutPicker);
    }

    init();
    $(window).on('elementor:init', init);
}(jQuery));
