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
        
        echo '<div class="options_group show_if_kustomizer_product kustomizer-product-options" style="display: block;">';
        echo '<h3>' . __('Kustomizer Settings', 'kustomizer') . '</h3>';
        
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
            console.log('Kustomizer product type script loaded');
            
            // Function to toggle Kustomizer options
            function toggleKustomizerOptions() {
                var productType = $('select#product-type').val();
                console.log('Current product type:', productType);
                
                if (productType === 'kustomizer_product') {
                    $('.show_if_kustomizer_product, .kustomizer-product-options').show();
                    console.log('Showing Kustomizer options');
                } else {
                    $('.show_if_kustomizer_product, .kustomizer-product-options').hide();
                    console.log('Hiding Kustomizer options');
                }
            }
            
            // Show/hide Kustomizer options based on product type
            $('select#product-type').on('change', toggleKustomizerOptions);
            
            // Force correct product type selection on page load
            <?php 
            $current_type = wp_get_object_terms($post->ID, 'product_type', array('fields' => 'slugs'));
            if (in_array('kustomizer_product', $current_type)) {
                echo "setTimeout(function() { 
                    $('select#product-type').val('kustomizer_product').trigger('change'); 
                    $('.show_if_kustomizer_product, .kustomizer-product-options').show();
                }, 100);";
            }
            ?>
            
            // Run toggle function immediately
            toggleKustomizerOptions();
            
            // Also run after delays to handle dynamic content
            setTimeout(toggleKustomizerOptions, 500);
            setTimeout(toggleKustomizerOptions, 1000);
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
            $stl_file = sanitize_text_field($_POST['_kustomizer_stl_file']);
            update_post_meta($post_id, '_kustomizer_stl_file', $stl_file);
            
            // If STL file is set, mark this as a kustomizer product
            if (!empty($stl_file)) {
                update_post_meta($post_id, '_kustomizer_product', 'yes');
                
                // Force set the product type
                wp_set_object_terms($post_id, 'kustomizer_product', 'product_type');
                error_log('Kustomizer: Set product ' . $post_id . ' as kustomizer_product due to STL file');
            }
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
        
        // Check if we should set as kustomizer product based on settings
        $has_customization = $allow_text === 'yes' || $allow_svg === 'yes' || $allow_texture === 'yes';
        $has_stl = !empty(get_post_meta($post_id, '_kustomizer_stl_file', true));
        
        if ($has_customization || $has_stl) {
            update_post_meta($post_id, '_kustomizer_product', 'yes');
            wp_set_object_terms($post_id, 'kustomizer_product', 'product_type');
        }
    }
}