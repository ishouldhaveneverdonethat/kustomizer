<?php
/**
 * Cart Handler Class
 * Handles WooCommerce cart integration for Kustomizer products
 * Compatible with High-Performance Order Storage (HPOS)
 *
 * @package Kustomizer
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kustomizer Cart Handler
 */
class Kustomizer_Cart_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Cart item data
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
        
        // Cart display
        add_filter('woocommerce_cart_item_name', array($this, 'display_cart_item_customization'), 10, 3);
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'display_cart_item_thumbnail'), 10, 3);
        
        // Order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
        
        // Cart validation
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 6);
        
        // Cart item permalink
        add_filter('woocommerce_cart_item_permalink', array($this, 'cart_item_permalink'), 10, 3);
    }
    
    /**
     * Add customization data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id) {
        if (isset($_POST['kustomizer_data'])) {
            $customization_data = $this->sanitize_customization_data($_POST['kustomizer_data']);
            if (!empty($customization_data)) {
                $cart_item_data['kustomizer_data'] = $customization_data;
                $cart_item_data['unique_key'] = md5(microtime() . rand());
            }
        }
        return $cart_item_data;
    }
    
    /**
     * Get cart item from session
     */
    public function get_cart_item_from_session($cart_item, $values) {
        if (isset($values['kustomizer_data'])) {
            $cart_item['kustomizer_data'] = $values['kustomizer_data'];
        }
        return $cart_item;
    }
    
    /**
     * Display customization info in cart
     */
    public function display_cart_item_customization($name, $cart_item, $cart_item_key) {
        if (isset($cart_item['kustomizer_data'])) {
            $customization = $cart_item['kustomizer_data'];
            $details = '<div class="kustomizer-cart-details">';
            
            if (!empty($customization['texture'])) {
                $details .= '<p><small>' . __('Custom Texture Applied', 'kustomizer') . '</small></p>';
            }
            
            if (!empty($customization['text'])) {
                $details .= '<p><small>' . sprintf(__('Text: %s', 'kustomizer'), esc_html($customization['text'])) . '</small></p>';
            }
            
            if (!empty($customization['svg'])) {
                $details .= '<p><small>' . __('Custom SVG Applied', 'kustomizer') . '</small></p>';
            }
            
            $details .= '</div>';
            $name .= $details;
        }
        return $name;
    }
    
    /**
     * Display custom thumbnail in cart
     */
    public function display_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['kustomizer_data']['preview_image'])) {
            $preview_url = $cart_item['kustomizer_data']['preview_image'];
            if (filter_var($preview_url, FILTER_VALIDATE_URL)) {
                $thumbnail = '<img src="' . esc_url($preview_url) . '" alt="' . __('Custom Preview', 'kustomizer') . '" />';
            }
        }
        return $thumbnail;
    }
    
    /**
     * Add order item meta (HPOS compatible)
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['kustomizer_data'])) {
            $customization_data = $values['kustomizer_data'];
            
            // Add meta data
            if (!empty($customization_data['texture'])) {
                $item->add_meta_data(__('Custom Texture', 'kustomizer'), $customization_data['texture'], true);
            }
            
            if (!empty($customization_data['text'])) {
                $item->add_meta_data(__('Custom Text', 'kustomizer'), $customization_data['text'], true);
            }
            
            if (!empty($customization_data['svg'])) {
                $item->add_meta_data(__('Custom SVG', 'kustomizer'), $customization_data['svg'], true);
            }
            
            if (!empty($customization_data['preview_image'])) {
                $item->add_meta_data(__('Preview Image', 'kustomizer'), $customization_data['preview_image'], true);
            }
            
            // Store complete customization data as JSON
            $item->add_meta_data('_kustomizer_data', wp_json_encode($customization_data), true);
        }
    }
    
    /**
     * Validate add to cart
     */
    public function validate_add_to_cart($passed, $product_id, $quantity, $variation_id = '', $variations = array(), $cart_item_data = array()) {
        $product = wc_get_product($product_id);
        
        if ($product && $product->get_type() === 'kustomizer_product') {
            // Check if customization is required
            $require_customization = get_post_meta($product_id, '_kustomizer_require_customization', true);
            
            if ($require_customization === 'yes' && !isset($_POST['kustomizer_data'])) {
                wc_add_notice(__('Please customize this product before adding to cart.', 'kustomizer'), 'error');
                $passed = false;
            }
        }
        
        return $passed;
    }
    
    /**
     * Cart item permalink
     */
    public function cart_item_permalink($permalink, $cart_item, $cart_item_key) {
        // Return to product page for re-customization
        return $permalink;
    }
    
    /**
     * Sanitize customization data
     */
    private function sanitize_customization_data($data) {
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }
        
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        if (isset($data['texture'])) {
            $sanitized['texture'] = sanitize_text_field($data['texture']);
        }
        
        if (isset($data['text'])) {
            $sanitized['text'] = sanitize_text_field($data['text']);
        }
        
        if (isset($data['svg'])) {
            $sanitized['svg'] = sanitize_text_field($data['svg']);
        }
        
        if (isset($data['preview_image'])) {
            $sanitized['preview_image'] = esc_url_raw($data['preview_image']);
        }
        
        return $sanitized;
    }
}