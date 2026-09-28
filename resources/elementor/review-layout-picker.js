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
                // Empty is a real value here - it is Custom - so an unset
                // control and a control set to Custom must land in the same
                // place. Without the coercion the selector below goes looking
                // for [value="undefined"] and checks nothing at all, leaving
                // the picker showing no choice when the answer is Custom.
                var value = this.getControlValue() || '';

                this.ui.inputs.prop('checked', false);
                this.ui.inputs.filter('[value="' + value + '"]').prop('checked', true);

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

                this.showHelp($(event.currentTarget).val());
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
            },
        });

        elementor.addControlView('fluent_review_layout_preset', ReviewLayoutPicker);
    }

    init();
    $(window).on('elementor:init', init);
}(jQuery));
