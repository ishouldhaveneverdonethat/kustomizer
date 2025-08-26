<?php
/**
 * Order Handler Class
 * Handles WooCommerce order processing for Kustomizer products
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
 * Kustomizer Order Handler
 */
class Kustomizer_Order_Handler {
    
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
        // Order item display
        add_action('woocommerce_order_item_meta_start', array($this, 'display_order_item_meta'), 10, 3);
        
        // Admin order display
        add_action('woocommerce_admin_order_item_headers', array($this, 'admin_order_item_headers'));
        add_action('woocommerce_admin_order_item_values', array($this, 'admin_order_item_values'), 10, 3);
        
        // Order processing hooks (HPOS compatible)
        add_action('woocommerce_order_status_processing', array($this, 'process_customization_order'));
        add_action('woocommerce_order_status_completed', array($this, 'complete_customization_order'));
        
        // Email hooks
        add_action('woocommerce_email_order_meta', array($this, 'add_email_order_meta'), 10, 4);
        
        // Download permissions
        add_action('woocommerce_order_status_completed', array($this, 'grant_download_permissions'));
        
        // REST API support
        add_action('woocommerce_rest_prepare_shop_order_object', array($this, 'rest_prepare_order'), 10, 3);
    }
    
    /**
     * Display order item meta on frontend
     */
    public function display_order_item_meta($item_id, $item, $order) {
        if (!is_a($item, 'WC_Order_Item_Product')) {
            return;
        }
        
        $customization_data = $item->get_meta('_kustomizer_data');
        if (!empty($customization_data)) {
            $data = json_decode($customization_data, true);
            if (is_array($data)) {
                echo '<div class="kustomizer-order-meta">';
                echo '<h4>' . __('Customization Details', 'kustomizer') . '</h4>';
                
                if (!empty($data['text'])) {
                    echo '<p><strong>' . __('Custom Text:', 'kustomizer') . '</strong> ' . esc_html($data['text']) . '</p>';
                }
                
                if (!empty($data['texture'])) {
                    echo '<p><strong>' . __('Custom Texture:', 'kustomizer') . '</strong> ' . __('Applied', 'kustomizer') . '</p>';
                }
                
                if (!empty($data['svg'])) {
                    echo '<p><strong>' . __('Custom Graphics:', 'kustomizer') . '</strong> ' . __('Applied', 'kustomizer') . '</p>';
                }
                
                if (!empty($data['preview_image'])) {
                    echo '<p><strong>' . __('Preview:', 'kustomizer') . '</strong><br>';
                    echo '<img src="' . esc_url($data['preview_image']) . '" style="max-width: 200px; height: auto;" alt="' . __('Customization Preview', 'kustomizer') . '" /></p>';
                }
                
                echo '</div>';
            }
        }
    }
    
    /**
     * Add headers to admin order items table
     */
    public function admin_order_item_headers() {
        echo '<th class="kustomizer-column">' . __('Customization', 'kustomizer') . '</th>';
    }
    
    /**
     * Add values to admin order items table
     */
    public function admin_order_item_values($product, $item, $item_id) {
        if (!is_a($item, 'WC_Order_Item_Product')) {
            echo '<td class="kustomizer-column">-</td>';
            return;
        }
        
        $customization_data = $item->get_meta('_kustomizer_data');
        echo '<td class="kustomizer-column">';
        
        if (!empty($customization_data)) {
            $data = json_decode($customization_data, true);
            if (is_array($data)) {
                echo '<div class="kustomizer-admin-meta">';
                
                if (!empty($data['text'])) {
                    echo '<div><strong>' . __('Text:', 'kustomizer') . '</strong> ' . esc_html($data['text']) . '</div>';
                }
                
                if (!empty($data['texture'])) {
                    echo '<div><strong>' . __('Texture:', 'kustomizer') . '</strong> ' . __('Custom', 'kustomizer') . '</div>';
                }
                
                if (!empty($data['svg'])) {
                    echo '<div><strong>' . __('Graphics:', 'kustomizer') . '</strong> ' . __('Custom', 'kustomizer') . '</div>';
                }
                
                if (!empty($data['preview_image'])) {
                    echo '<div><a href="' . esc_url($data['preview_image']) . '" target="_blank">' . __('View Preview', 'kustomizer') . '</a></div>';
                }
                
                echo '</div>';
            } else {
                echo __('Customized', 'kustomizer');
            }
        } else {
            echo '-';
        }
        
        echo '</td>';
    }
    
    /**
     * Process customization order
     */
    public function process_customization_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        foreach ($order->get_items() as $item_id => $item) {
            if (!is_a($item, 'WC_Order_Item_Product')) {
                continue;
            }
            
            $product = $item->get_product();
            if ($product && $product->get_type() === 'kustomizer_product') {
                $customization_data = $item->get_meta('_kustomizer_data');
                if (!empty($customization_data)) {
                    // Process the customization
                    $this->process_item_customization($order, $item, $customization_data);
                }
            }
        }
    }
    
    /**
     * Complete customization order
     */
    public function complete_customization_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Generate final files and send notifications
        $this->generate_final_files($order);
    }
    
    /**
     * Add customization info to order emails
     */
    public function add_email_order_meta($order, $sent_to_admin, $plain_text, $email) {
        if ($plain_text) {
            return;
        }
        
        $has_customization = false;
        foreach ($order->get_items() as $item) {
            if (!is_a($item, 'WC_Order_Item_Product')) {
                continue;
            }
            
            $customization_data = $item->get_meta('_kustomizer_data');
            if (!empty($customization_data)) {
                $has_customization = true;
                break;
            }
        }
        
        if ($has_customization) {
            echo '<h2>' . __('Customization Details', 'kustomizer') . '</h2>';
            echo '<p>' . __('This order contains customized products. Detailed customization information is available in the order details.', 'kustomizer') . '</p>';
        }
    }
    
    /**
     * Grant download permissions for customization files
     */
    public function grant_download_permissions($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        foreach ($order->get_items() as $item) {
            if (!is_a($item, 'WC_Order_Item_Product')) {
                continue;
            }
            
            $customization_data = $item->get_meta('_kustomizer_data');
            if (!empty($customization_data)) {
                // Create downloadable files for the customization
                $this->create_downloadable_files($order, $item);
            }
        }
    }
    
    /**
     * REST API support
     */
    public function rest_prepare_order($response, $object, $request) {
        $order = $object;
        $line_items = $response->get_data()['line_items'];
        
        foreach ($line_items as $key => $line_item) {
            $item = $order->get_item($line_item['id']);
            if ($item) {
                $customization_data = $item->get_meta('_kustomizer_data');
                if (!empty($customization_data)) {
                    $line_items[$key]['kustomizer_data'] = json_decode($customization_data, true);
                }
            }
        }
        
        $data = $response->get_data();
        $data['line_items'] = $line_items;
        $response->set_data($data);
        
        return $response;
    }
    
    /**
     * Process individual item customization
     */
    private function process_item_customization($order, $item, $customization_data) {
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Customization processing started for item: %s', 'kustomizer'),
                $item->get_name()
            )
        );
        
        // Hook for external processing
        do_action('kustomizer_process_item_customization', $order, $item, $customization_data);
    }
    
    /**
     * Generate final files
     */
    private function generate_final_files($order) {
        // Hook for generating manufacturing files
        do_action('kustomizer_generate_final_files', $order);
        
        // Add order note
        $order->add_order_note(__('Customization files generated and ready for production.', 'kustomizer'));
    }
    
    /**
     * Create downloadable files
     */
    private function create_downloadable_files($order, $item) {
        // Hook for creating customer download files
        do_action('kustomizer_create_downloadable_files', $order, $item);
    }
}