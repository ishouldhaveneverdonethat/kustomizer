<?php
/**
 * Plugin Name: Kustomizer - WooCommerce 3D Product Customizer
 * Plugin URI: https://github.com/ishouldhaveneverdonethat/kustomizer
 * Description: Allow customers to customize 3D STL products with textures, text, and SVG overlays in WooCommerce
 * Version: 1.0.1
 * Author: ishouldhaveneverdonethat
 * Author URI: https://github.com/ishouldhaveneverdonethat
 * Text Domain: kustomizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: ishouldhaveneverdonethat/kustomizer
 * WC Feature: High-Performance Order Storage (HPOS)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KUSTOMIZER_VERSION', '1.0.1');
define('KUSTOMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KUSTOMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KUSTOMIZER_BASENAME', plugin_basename(__FILE__));

/**
 * Main Kustomizer plugin class
 */
class Kustomizer {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        add_action('admin_init', array($this, 'manual_convert_product'));
        add_action('admin_menu', array($this, 'add_conversion_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Load text domain
        load_plugin_textdomain('kustomizer', false, dirname(KUSTOMIZER_BASENAME) . '/languages');
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-product-type.php';
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        
        // Load optional classes if they exist
        if (file_exists(KUSTOMIZER_PLUGIN_DIR . 'includes/class-cart-handler.php')) {
            require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-cart-handler.php';
        }
        if (file_exists(KUSTOMIZER_PLUGIN_DIR . 'includes/class-order-handler.php')) {
            require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-order-handler.php';
        }
        if (file_exists(KUSTOMIZER_PLUGIN_DIR . 'includes/class-admin-settings.php')) {
            require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-admin-settings.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Debug logging
        error_log('Kustomizer: init_hooks called');
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add custom product type
        if (class_exists('Kustomizer_Product_Type')) {
            error_log('Kustomizer: Registering product type hooks');
            add_filter('product_type_selector', array('Kustomizer_Product_Type', 'add_product_type'));
            add_action('woocommerce_product_options_general_product_data', array('Kustomizer_Product_Type', 'add_product_options'));
            add_action('woocommerce_process_product_meta', array('Kustomizer_Product_Type', 'save_product_options'));
        } else {
            error_log('Kustomizer: Kustomizer_Product_Type class not found!');
        }
        
        // Handle AJAX requests
        if (class_exists('Kustomizer_Ajax_Handlers')) {
            new Kustomizer_Ajax_Handlers();
        }
        
        // Handle cart and orders
        if (class_exists('Kustomizer_Cart_Handler')) {
            new Kustomizer_Cart_Handler();
        }
        if (class_exists('Kustomizer_Order_Handler')) {
            new Kustomizer_Order_Handler();
        }
        
        // Admin settings
        if (is_admin() && class_exists('Kustomizer_Admin_Settings')) {
            new Kustomizer_Admin_Settings();
        }
        
        // Single product page customization
        add_action('woocommerce_single_product_summary', array($this, 'add_customizer_interface'), 25);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Debug: Always load on product pages for testing
        if (is_product()) {
            global $product;
            
            // Debug logging
            error_log('Kustomizer: On product page, Product ID: ' . get_the_ID());
            
            if ($product) {
                error_log('Kustomizer: Product type: ' . $product->get_type());
                
                if ($product->get_type() === 'kustomizer_product') {
                    error_log('Kustomizer: Loading assets for Kustomizer product');
                    
                    // Three.js library
                    wp_enqueue_script(
                        'threejs', 
                        'https://cdnjs.cloudflare.com/ajax/libs/three.js/r160/three.min.js', 
                        array(), 
                        '160', 
                        true
                    );
                    
                    // STL Loader
                    wp_enqueue_script(
                        'stl-loader', 
                        KUSTOMIZER_PLUGIN_URL . 'assets/js/STLLoader.js', 
                        array('threejs'), 
                        KUSTOMIZER_VERSION, 
                        true
                    );
                    
                    // Orbit Controls
                    wp_enqueue_script(
                        'orbit-controls', 
                        KUSTOMIZER_PLUGIN_URL . 'assets/js/OrbitControls.js', 
                        array('threejs'), 
                        KUSTOMIZER_VERSION, 
                        true
                    );
                    
                    // Main customizer script
                    wp_enqueue_script(
                        'kustomizer', 
                        KUSTOMIZER_PLUGIN_URL . 'assets/js/kustomizer.js', 
                        array('jquery', 'threejs', 'stl-loader', 'orbit-controls'), 
                        KUSTOMIZER_VERSION, 
                        true
                    );
                    
                    // Customizer styles
                    wp_enqueue_style(
                        'kustomizer-styles', 
                        KUSTOMIZER_PLUGIN_URL . 'assets/css/kustomizer.css', 
                        array(), 
                        KUSTOMIZER_VERSION
                    );
                    
                    // Localize script
                    wp_localize_script('kustomizer', 'kustomizer', array(
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('kustomizer_nonce'),
                        'productId' => get_the_ID(),
                        'stlFile' => get_post_meta(get_the_ID(), '_kustomizer_stl_file', true),
                        'translations' => array(
                            'uploadTexture' => __('Upload Texture', 'kustomizer'),
                            'addText' => __('Add Text', 'kustomizer'),
                            'uploadSVG' => __('Upload SVG', 'kustomizer'),
                            'generateLayout' => __('Generate Layout', 'kustomizer'),
                        )
                    ));
                } else {
                    error_log('Kustomizer: Product is not kustomizer_product type');
                }
            } else {
                error_log('Kustomizer: No product object found');
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post;
            if ($post && get_post_type($post) === 'product') {
                // Enqueue WordPress media uploader
                wp_enqueue_media();
                
                // Enqueue admin scripts
                wp_enqueue_script(
                    'kustomizer-admin', 
                    KUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js', 
                    array('jquery', 'media-upload', 'media-views'), 
                    KUSTOMIZER_VERSION, 
                    true
                );
                
                // Enqueue admin styles
                wp_enqueue_style(
                    'kustomizer-admin-styles', 
                    KUSTOMIZER_PLUGIN_URL . 'assets/css/admin.css', 
                    array(), 
                    KUSTOMIZER_VERSION
                );
                
                // Localize script for admin
                wp_localize_script('kustomizer-admin', 'kustomizer_admin', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('kustomizer_admin_nonce'),
                    'strings' => array(
                        'selectSTL' => __('Select STL File', 'kustomizer'),
                        'selectTexture' => __('Select Texture Image', 'kustomizer'),
                        'useFile' => __('Use This File', 'kustomizer'),
                        'fileUploaded' => __('File uploaded', 'kustomizer'),
                    )
                ));
            }
        }
    }
    
    /**
     * Add customizer interface to product page
     */
    public function add_customizer_interface() {
        global $product;
        
        // Debug logging
        error_log('Kustomizer: add_customizer_interface called');
        
        if ($product) {
            error_log('Kustomizer: Product type in interface: ' . $product->get_type());
            
            if ($product->get_type() === 'kustomizer_product') {
                error_log('Kustomizer: Including customizer interface template');
                include KUSTOMIZER_PLUGIN_DIR . 'templates/customizer-interface.php';
            } else {
                error_log('Kustomizer: Product is not kustomizer_product, not showing interface');
                // For debugging, show a message
                echo '<div style="background: #ff0; padding: 10px; margin: 10px 0;">Debug: Product type is "' . $product->get_type() . '", expected "kustomizer_product"</div>';
            }
        } else {
            error_log('Kustomizer: No product found in add_customizer_interface');
            echo '<div style="background: #f00; color: #fff; padding: 10px; margin: 10px 0;">Debug: No product object found</div>';
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create upload directories
        $upload_dir = wp_upload_dir();
        $kustomizer_dir = $upload_dir['basedir'] . '/kustomizer';
        
        if (!file_exists($kustomizer_dir)) {
            wp_mkdir_p($kustomizer_dir);
        }
        
        // Set default options
        add_option('kustomizer_version', KUSTOMIZER_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             __('Kustomizer requires WooCommerce to be installed and active.', 'kustomizer') . 
             '</strong></p></div>';
    }
    
    /**
     * Manual function to convert a product to kustomizer type
     * Access via: /wp-admin/admin.php?kustomizer_convert=PRODUCT_ID
     */
    public function manual_convert_product() {
        if (isset($_GET['kustomizer_convert'])) {
            $product_id = intval($_GET['kustomizer_convert']);
            
            // Always show debug info
            echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
            echo '<h2>Kustomizer Product Conversion</h2>';
            echo '<p>Product ID: ' . $product_id . '</p>';
            echo '<p>Current User Can Manage WooCommerce: ' . (current_user_can('manage_woocommerce') ? 'Yes' : 'No') . '</p>';
            echo '<p>Is Admin: ' . (is_admin() ? 'Yes' : 'No') . '</p>';
            
            if ($product_id > 0) {
                if (current_user_can('manage_woocommerce')) {
                    // Get current product type
                    $current_type = wp_get_object_terms($product_id, 'product_type');
                    echo '<p>Current product type: ' . (isset($current_type[0]) ? $current_type[0]->slug : 'none') . '</p>';
                    
                    // Set product type to kustomizer_product
                    $result = wp_set_object_terms($product_id, 'kustomizer_product', 'product_type');
                    echo '<p>Set product type result: ' . print_r($result, true) . '</p>';
                    
                    // Enable all customization options
                    update_post_meta($product_id, '_kustomizer_allow_text', 'yes');
                    update_post_meta($product_id, '_kustomizer_allow_texture', 'yes');
                    update_post_meta($product_id, '_kustomizer_allow_svg', 'yes');
                    
                    echo '<p><strong>Conversion completed!</strong></p>';
                    echo '<p><a href="' . admin_url('post.php?post=' . $product_id . '&action=edit') . '">Edit Product</a></p>';
                    echo '<p><a href="' . get_permalink($product_id) . '">View Product</a></p>';
                } else {
                    echo '<p><strong>Error:</strong> You don\'t have permission to manage WooCommerce.</p>';
                }
            } else {
                echo '<p><strong>Error:</strong> Invalid product ID.</p>';
            }
            echo '</div>';
            
            // Stop further execution
            exit;
        }
    }
    
    /**
     * Add conversion menu to admin
     */
    public function add_conversion_menu() {
        add_submenu_page(
            'tools.php',
            'Kustomizer Converter',
            'Kustomizer Converter',
            'manage_woocommerce',
            'kustomizer-converter',
            array($this, 'conversion_page')
        );
    }
    
    /**
     * Conversion admin page
     */
    public function conversion_page() {
        if (isset($_POST['convert_product']) && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            if ($product_id > 0) {
                // Convert the product
                wp_set_object_terms($product_id, 'kustomizer_product', 'product_type');
                update_post_meta($product_id, '_kustomizer_allow_text', 'yes');
                update_post_meta($product_id, '_kustomizer_allow_texture', 'yes');
                update_post_meta($product_id, '_kustomizer_allow_svg', 'yes');
                
                echo '<div class="notice notice-success"><p>Product #' . $product_id . ' converted successfully!</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Kustomizer Product Converter</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Product ID</th>
                        <td>
                            <input type="number" name="product_id" value="632" />
                            <p class="description">Enter the product ID you want to convert to Kustomizer Product</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Convert Product', 'primary', 'convert_product'); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
Kustomizer::get_instance();