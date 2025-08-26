<?php
/**
 * Admin Settings Class
 * Handles admin settings and configuration for Kustomizer
 *
 * @package Kustomizer
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kustomizer Admin Settings
 */
class Kustomizer_Admin_Settings {
    
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
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        
        // Settings link on plugins page
        add_filter('plugin_action_links_' . KUSTOMIZER_BASENAME, array($this, 'add_settings_link'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // AJAX handlers for admin
        add_action('wp_ajax_kustomizer_test_settings', array($this, 'test_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Kustomizer Settings', 'kustomizer'),
            __('Kustomizer', 'kustomizer'),
            'manage_woocommerce',
            'kustomizer-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('kustomizer_settings', 'kustomizer_options', array($this, 'sanitize_settings'));
        
        // General settings section
        add_settings_section(
            'kustomizer_general',
            __('General Settings', 'kustomizer'),
            array($this, 'general_section_callback'),
            'kustomizer-settings'
        );
        
        // File upload settings
        add_settings_field(
            'max_file_size',
            __('Maximum File Size (MB)', 'kustomizer'),
            array($this, 'max_file_size_callback'),
            'kustomizer-settings',
            'kustomizer_general'
        );
        
        add_settings_field(
            'allowed_file_types',
            __('Allowed File Types', 'kustomizer'),
            array($this, 'allowed_file_types_callback'),
            'kustomizer-settings',
            'kustomizer_general'
        );
        
        // 3D Viewer settings section
        add_settings_section(
            'kustomizer_viewer',
            __('3D Viewer Settings', 'kustomizer'),
            array($this, 'viewer_section_callback'),
            'kustomizer-settings'
        );
        
        add_settings_field(
            'viewer_background',
            __('Viewer Background Color', 'kustomizer'),
            array($this, 'viewer_background_callback'),
            'kustomizer-settings',
            'kustomizer_viewer'
        );
        
        add_settings_field(
            'viewer_width',
            __('Viewer Width (px)', 'kustomizer'),
            array($this, 'viewer_width_callback'),
            'kustomizer-settings',
            'kustomizer_viewer'
        );
        
        add_settings_field(
            'viewer_height',
            __('Viewer Height (px)', 'kustomizer'),
            array($this, 'viewer_height_callback'),
            'kustomizer-settings',
            'kustomizer_viewer'
        );
        
        // Text customization settings section
        add_settings_section(
            'kustomizer_text',
            __('Text Customization Settings', 'kustomizer'),
            array($this, 'text_section_callback'),
            'kustomizer-settings'
        );
        
        add_settings_field(
            'max_text_length',
            __('Maximum Text Length', 'kustomizer'),
            array($this, 'max_text_length_callback'),
            'kustomizer-settings',
            'kustomizer_text'
        );
        
        add_settings_field(
            'available_fonts',
            __('Available Fonts', 'kustomizer'),
            array($this, 'available_fonts_callback'),
            'kustomizer-settings',
            'kustomizer_text'
        );
    }
    
    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=kustomizer-settings') . '">' . __('Settings', 'kustomizer') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="kustomizer-admin-header">
                <p><?php _e('Configure your Kustomizer plugin settings to customize the 3D product experience.', 'kustomizer'); ?></p>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('kustomizer_settings');
                do_settings_sections('kustomizer-settings');
                submit_button(__('Save Settings', 'kustomizer'));
                ?>
            </form>
            
            <div class="kustomizer-admin-footer">
                <h3><?php _e('System Information', 'kustomizer'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Plugin Version:', 'kustomizer'); ?></strong></td>
                        <td><?php echo KUSTOMIZER_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WordPress Version:', 'kustomizer'); ?></strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WooCommerce Version:', 'kustomizer'); ?></strong></td>
                        <td><?php echo defined('WC_VERSION') ? WC_VERSION : __('Not installed', 'kustomizer'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('PHP Version:', 'kustomizer'); ?></strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('HPOS Status:', 'kustomizer'); ?></strong></td>
                        <td>
                            <?php
                            if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
                                method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')) {
                                echo \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() 
                                    ? __('Enabled', 'kustomizer') 
                                    : __('Disabled', 'kustomizer');
                            } else {
                                echo __('Not available', 'kustomizer');
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'kustomizer') . '</p>';
    }
    
    public function viewer_section_callback() {
        echo '<p>' . __('Configure 3D viewer appearance and behavior.', 'kustomizer') . '</p>';
    }
    
    public function text_section_callback() {
        echo '<p>' . __('Configure text customization options.', 'kustomizer') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function max_file_size_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['max_file_size']) ? $options['max_file_size'] : '10';
        echo '<input type="number" id="max_file_size" name="kustomizer_options[max_file_size]" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">' . __('Maximum file size for uploads in megabytes.', 'kustomizer') . '</p>';
    }
    
    public function allowed_file_types_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['allowed_file_types']) ? $options['allowed_file_types'] : 'jpg,jpeg,png,gif,svg';
        echo '<input type="text" id="allowed_file_types" name="kustomizer_options[allowed_file_types]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Comma-separated list of allowed file extensions.', 'kustomizer') . '</p>';
    }
    
    public function viewer_background_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['viewer_background']) ? $options['viewer_background'] : '#f0f0f0';
        echo '<input type="color" id="viewer_background" name="kustomizer_options[viewer_background]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Background color for the 3D viewer.', 'kustomizer') . '</p>';
    }
    
    public function viewer_width_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['viewer_width']) ? $options['viewer_width'] : '600';
        echo '<input type="number" id="viewer_width" name="kustomizer_options[viewer_width]" value="' . esc_attr($value) . '" min="300" max="1200" />';
        echo '<p class="description">' . __('Width of the 3D viewer in pixels.', 'kustomizer') . '</p>';
    }
    
    public function viewer_height_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['viewer_height']) ? $options['viewer_height'] : '400';
        echo '<input type="number" id="viewer_height" name="kustomizer_options[viewer_height]" value="' . esc_attr($value) . '" min="200" max="800" />';
        echo '<p class="description">' . __('Height of the 3D viewer in pixels.', 'kustomizer') . '</p>';
    }
    
    public function max_text_length_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['max_text_length']) ? $options['max_text_length'] : '50';
        echo '<input type="number" id="max_text_length" name="kustomizer_options[max_text_length]" value="' . esc_attr($value) . '" min="1" max="500" />';
        echo '<p class="description">' . __('Maximum number of characters for custom text.', 'kustomizer') . '</p>';
    }
    
    public function available_fonts_callback() {
        $options = get_option('kustomizer_options');
        $value = isset($options['available_fonts']) ? $options['available_fonts'] : "Arial\nHelvetica\nTimes New Roman\nCourier New";
        echo '<textarea id="available_fonts" name="kustomizer_options[available_fonts]" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('One font name per line. These fonts will be available for text customization.', 'kustomizer') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['max_file_size'])) {
            $sanitized['max_file_size'] = absint($input['max_file_size']);
        }
        
        if (isset($input['allowed_file_types'])) {
            $sanitized['allowed_file_types'] = sanitize_text_field($input['allowed_file_types']);
        }
        
        if (isset($input['viewer_background'])) {
            $sanitized['viewer_background'] = sanitize_hex_color($input['viewer_background']);
        }
        
        if (isset($input['viewer_width'])) {
            $sanitized['viewer_width'] = absint($input['viewer_width']);
        }
        
        if (isset($input['viewer_height'])) {
            $sanitized['viewer_height'] = absint($input['viewer_height']);
        }
        
        if (isset($input['max_text_length'])) {
            $sanitized['max_text_length'] = absint($input['max_text_length']);
        }
        
        if (isset($input['available_fonts'])) {
            $sanitized['available_fonts'] = sanitize_textarea_field($input['available_fonts']);
        }
        
        return $sanitized;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>';
            echo __('Kustomizer requires WooCommerce to be installed and activated.', 'kustomizer');
            echo '</p></div>';
        }
        
        // Check HPOS compatibility
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
            method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            
            $screen = get_current_screen();
            if ($screen && $screen->id === 'woocommerce_page_kustomizer-settings') {
                echo '<div class="notice notice-success"><p>';
                echo __('Kustomizer is compatible with WooCommerce High-Performance Order Storage (HPOS).', 'kustomizer');
                echo '</p></div>';
            }
        }
    }
    
    /**
     * Test settings AJAX handler
     */
    public function test_settings() {
        check_ajax_referer('kustomizer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'kustomizer'));
        }
        
        $response = array(
            'success' => true,
            'message' => __('Settings are working correctly.', 'kustomizer')
        );
        
        wp_send_json($response);
    }
}