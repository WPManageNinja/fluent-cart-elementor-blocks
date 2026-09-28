<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls;

use Elementor\Base_Data_Control;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The layout picker: a grid of cards, the way the block editor draws it.
 *
 * It was a dropdown, which named eleven layouts and showed none of them. A
 * layout is a shape, and a list of names is the one form in which a merchant
 * cannot tell one shape from another - so they picked by reading, and read
 * eleven help texts to choose once.
 *
 * Radio inputs under the cards, not click handlers. A radio group is what this
 * is: one of several, exactly one at a time. Taking the native control gives
 * arrow-key movement, the group's announcement to a screen reader, and a
 * disabled state that actually disables - none of which a div with a click
 * handler has, and all of which the block editor's picker had to write by
 * hand.
 *
 * Locked layouts stay in the grid wearing a PRO badge. A choice that has
 * disappeared looks like something the plugin cannot do; one that is there and
 * cannot be taken is an invitation.
 */
class ReviewLayoutPresetControl extends Base_Data_Control
{
    const TYPE = 'fluent_review_layout_preset';

    public function get_type()
    {
        return static::TYPE;
    }

    protected function get_default_settings()
    {
        return array_merge(parent::get_default_settings(), [
            'label_block' => true,
            'separator'   => 'before',
            // [{value, label, help, category, pro}], and 'categories' as
            // [{value, label}]. Both are built by the widget, from the same
            // declaration core builds the layouts themselves from.
            'layouts'     => [],
            'categories'  => [],
        ]);
    }

    public function content_template()
    {
        ?>
        <div class="elementor-control-field fct-el-preset-field">
            <# if ( data.label ) { #>
            <label class="elementor-control-title">{{{ data.label }}}</label>
            <# } #>

            <# if ( data.categories && data.categories.length > 1 ) { #>
            <div class="fct-el-preset-tabs">
                <# _.each( data.categories, function( category ) { #>
                <button type="button" class="fct-el-preset-tab" data-category="{{ category.value }}">
                    {{{ category.label }}}
                </button>
                <# } ); #>
            </div>
            <# } #>

            <div class="fct-el-preset-grid">
                <# _.each( data.layouts, function( layout ) { #>
                <div class="fct-el-preset-item" data-category="{{ layout.category }}">
                    <input type="radio"
                           class="fct-el-preset-input"
                           id="fct-el-preset-{{ data._cid }}-{{ layout.value }}"
                           name="fct-el-preset-{{ data._cid }}"
                           value="{{ layout.value }}"
                           <# if ( layout.locked ) { #>disabled aria-disabled="true"<# } #>>
                    <label class="fct-el-preset-card<# if ( layout.locked ) { #> is-locked<# } #>"
                           for="fct-el-preset-{{ data._cid }}-{{ layout.value }}"
                           title="{{ layout.help }}">
                        <span class="fct-el-preset-thumb">{{{ layout.thumb }}}</span>
                        <span class="fct-el-preset-name">{{{ layout.label }}}</span>
                        <# if ( layout.locked ) { #>
                        <span class="fct-el-preset-pro"><?php echo esc_html__('Pro', 'fluent-cart-elementor-blocks'); ?></span>
                        <# } #>
                    </label>
                </div>
                <# } ); #>
            </div>

            <# if ( data.layouts && data.layouts.length ) { #>
            <div class="fct-el-preset-help" aria-live="polite"></div>
            <# } #>
        </div>

        <# if ( data.description ) { #>
        <div class="elementor-control-field-description">{{{ data.description }}}</div>
        <# } #>
        <?php
    }
}
