<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kustomizer Product Type
 */
class Kustomizer_Product_Type {
    
    /**
     * Add Kustomizer product type to WooCommerce
     */
    public static function add_product_type($types) {
        error_log('Kustomizer: add_product_type called, adding kustomizer_product type');
        $types['kustomizer_product'] = __('Kustomizer Product', 'kustomizer');
        error_log('Kustomizer: Product types now: ' . print_r($types, true));
        return $types;
    }
    
    /**
     * Add product options for Kustomizer products
     */
    public static function add_product_options() {
        global $post;
        
        echo '<div class="options_group show_if_kustomizer_product">';
        
        // STL File Upload
        echo '<p class="form-field">';
        echo '<label for="_kustomizer_stl_file">' . __('STL File', 'kustomizer') . '</label>';
        echo '<input type="text" id="_kustomizer_stl_file" name="_kustomizer_stl_file" value="' . 
             esc_attr(get_post_meta($post->ID, '_kustomizer_stl_file', true)) . '" placeholder="' . 
             __('STL file URL', 'kustomizer') . '" />';
        echo '<button type="button" class="button upload_stl_file">' . __('Upload STL', 'kustomizer') . '</button>';
        echo '</p>';
        
        // Default Texture
        echo '<p class="form-field">';
        echo '<label for="_kustomizer_default_texture">' . __('Default Texture', 'kustomizer') . '</label>';
        echo '<input type="text" id="_kustomizer_default_texture" name="_kustomizer_default_texture" value="' . 
             esc_attr(get_post_meta($post->ID, '_kustomizer_default_texture', true)) . '" placeholder="' . 
             __('Default texture URL', 'kustomizer') . '" />';
        echo '<button type="button" class="button upload_texture_file">' . __('Upload Texture', 'kustomizer') . '</button>';
        echo '</p>';
        
        // Allow Text Customization
        woocommerce_wp_checkbox(array(
            'id' => '_kustomizer_allow_text',
            'label' => __('Allow Text Customization', 'kustomizer'),
            'description' => __('Allow customers to add custom text', 'kustomizer')
        ));
        
        // Allow SVG Upload
        woocommerce_wp_checkbox(array(
            'id' => '_kustomizer_allow_svg',
            'label' => __('Allow SVG Upload', 'kustomizer'),
            'description' => __('Allow customers to upload SVG graphics', 'kustomizer')
        ));
        
        // Allow Texture Upload
        woocommerce_wp_checkbox(array(
            'id' => '_kustomizer_allow_texture',
            'label' => __('Allow Texture Upload', 'kustomizer'),
            'description' => __('Allow customers to upload custom textures', 'kustomizer')
        ));
        
        // Max Text Length
        woocommerce_wp_text_input(array(
            'id' => '_kustomizer_max_text_length',
            'label' => __('Max Text Length', 'kustomizer'),
            'description' => __('Maximum number of characters for custom text', 'kustomizer'),
            'type' => 'number',
            'custom_attributes' => array(
                'min' => '1',
                'max' => '500'
            )
        ));
        
        // Available Fonts
        woocommerce_wp_textarea_input(array(
            'id' => '_kustomizer_available_fonts',
            'label' => __('Available Fonts', 'kustomizer'),
            'description' => __('Comma-separated list of available fonts', 'kustomizer'),
            'placeholder' => 'Arial, Helvetica, Times New Roman'
        ));
        
        // Customization Price
        woocommerce_wp_text_input(array(
            'id' => '_kustomizer_customization_price',
            'label' => __('Customization Price', 'kustomizer') . ' (' . get_woocommerce_currency_symbol() . ')',
            'description' => __('Additional price for customization', 'kustomizer'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));
        
        echo '</div>';
        
        // Add JavaScript to show/hide options
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show/hide Kustomizer options based on product type
            $('select#product-type').change(function() {
                if ($(this).val() === 'kustomizer_product') {
                    $('.show_if_kustomizer_product').show();
                } else {
                    $('.show_if_kustomizer_product').hide();
                }
            }).change();
            
            // Force correct product type selection on page load
            <?php 
            $current_type = wp_get_object_terms($post->ID, 'product_type', array('fields' => 'slugs'));
            if (in_array('kustomizer_product', $current_type)) {
                echo "setTimeout(function() { $('select#product-type').val('kustomizer_product').trigger('change'); }, 100);";
            }
            ?>
        });
        </script>
        <?php
    }
    
    /**
     * Save Kustomizer product options
     */
    public static function save_product_options($post_id) {
        // STL File URL
        if (isset($_POST['_kustomizer_stl_file'])) {
            update_post_meta($post_id, '_kustomizer_stl_file', sanitize_text_field($_POST['_kustomizer_stl_file']));
        }
        
        // Default Texture URL
        if (isset($_POST['_kustomizer_default_texture'])) {
            update_post_meta($post_id, '_kustomizer_default_texture', sanitize_text_field($_POST['_kustomizer_default_texture']));
        }
        
        // Allow Text Customization
        $allow_text = isset($_POST['_kustomizer_allow_text']) ? 'yes' : 'no';
        update_post_meta($post_id, '_kustomizer_allow_text', $allow_text);
        
        // Allow SVG Upload
        $allow_svg = isset($_POST['_kustomizer_allow_svg']) ? 'yes' : 'no';
        update_post_meta($post_id, '_kustomizer_allow_svg', $allow_svg);
        
        // Allow Texture Upload
        $allow_texture = isset($_POST['_kustomizer_allow_texture']) ? 'yes' : 'no';
        update_post_meta($post_id, '_kustomizer_allow_texture', $allow_texture);
        
        // Max Text Length
        if (isset($_POST['_kustomizer_max_text_length'])) {
            update_post_meta($post_id, '_kustomizer_max_text_length', absint($_POST['_kustomizer_max_text_length']));
        }
        
        // Available Fonts
        if (isset($_POST['_kustomizer_available_fonts'])) {
            update_post_meta($post_id, '_kustomizer_available_fonts', sanitize_textarea_field($_POST['_kustomizer_available_fonts']));
        }
        
        // Customization Price
        if (isset($_POST['_kustomizer_customization_price'])) {
            update_post_meta($post_id, '_kustomizer_customization_price', floatval($_POST['_kustomizer_customization_price']));
        }
    }
}