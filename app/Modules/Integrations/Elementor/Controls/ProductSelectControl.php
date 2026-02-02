<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls;

use Elementor\Control_Select2;

if (!defined('ABSPATH')) {
    exit;
}

class ProductSelectControl extends Control_Select2
{
    public function get_type()
    {
        return 'fluent_product_select';
    }

    protected function get_default_settings()
    {
        return array_merge(parent::get_default_settings(), [
            'multiple' => true,
            'label_block' => true,
            'options' => [],
        ]);
    }

    public function content_template()
    {
        $control_uid = $this->get_control_uid();
        ?>
        <div class="elementor-control-field">
            <# if ( data.label ) { #>
            <label for="<?php echo esc_attr($control_uid); ?>" class="elementor-control-title">{{{ data.label }}}</label>
            <# } #>
            <div class="elementor-control-input-wrapper elementor-control-unit-5">
                <# var multiple = ( data.multiple ) ? 'multiple' : ''; #>
                <select id="<?php echo esc_attr($control_uid); ?>"
                        class="elementor-select2 elementor-control-tag-area"
                        type="select2"
                        {{ multiple }}
                        data-setting="{{ data.name }}">
                    <# _.each( data.options, function( option_title, option_value ) { #>
                    <option value="{{ option_value }}">{{{ option_title }}}</option>
                    <# } ); #>
                </select>
            </div>
        </div>
        <# if ( data.description ) { #>
        <div class="elementor-control-field-description">{{{ data.description }}}</div>
        <# } #>
        <?php
    }
}