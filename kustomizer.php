<?php
/**
 * Plugin Name: Kustomizer - WooCommerce 3D Product Customizer
 * Plugin URI: https://github.com/yourusername/kustomizer
 * Description: Allow customers to customize 3D STL products with textures, text, and SVG overlays in WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: kustomizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: yourusername/kustomizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KUSTOMIZER_VERSION', '1.0.0');
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
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-product-type.php';
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-cart-handler.php';
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-order-handler.php';
        require_once KUSTOMIZER_PLUGIN_DIR . 'includes/class-admin-settings.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add custom product type
        add_filter('product_type_selector', array('Kustomizer_Product_Type', 'add_product_type'));
        add_action('woocommerce_product_options_general_product_data', array('Kustomizer_Product_Type', 'add_product_options'));
        add_action('woocommerce_process_product_meta', array('Kustomizer_Product_Type', 'save_product_options'));
        
        // Handle AJAX requests
        new Kustomizer_Ajax_Handlers();
        
        // Handle cart and orders
        new Kustomizer_Cart_Handler();
        new Kustomizer_Order_Handler();
        
        // Admin settings
        if (is_admin()) {
            new Kustomizer_Admin_Settings();
        }
        
        // Single product page customization
        add_action('woocommerce_single_product_summary', array($this, 'add_customizer_interface'), 25);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_product()) {
            global $product;
            if ($product && $product->get_type() === 'kustomizer_product') {
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
                wp_enqueue_media();
                wp_enqueue_script(
                    'kustomizer-admin', 
                    KUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js', 
                    array('jquery'), 
                    KUSTOMIZER_VERSION, 
                    true
                );
                wp_enqueue_style(
                    'kustomizer-admin-styles', 
                    KUSTOMIZER_PLUGIN_URL . 'assets/css/admin.css', 
                    array(), 
                    KUSTOMIZER_VERSION
                );
            }
        }
    }
    
    /**
     * Add customizer interface to product page
     */
    public function add_customizer_interface() {
        global $product;
        if ($product && $product->get_type() === 'kustomizer_product') {
            include KUSTOMIZER_PLUGIN_DIR . 'templates/customizer-interface.php';
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
}

// Initialize the plugin
Kustomizer::get_instance();