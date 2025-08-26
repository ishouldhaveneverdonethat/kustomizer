<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handlers for Kustomizer
 */
class Kustomizer_Ajax_Handlers {
    
    public function __construct() {
        // Public AJAX actions (for both logged in and non-logged in users)
        add_action('wp_ajax_kustomizer_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_kustomizer_upload_file', array($this, 'handle_file_upload'));
        
        add_action('wp_ajax_kustomizer_save_customization', array($this, 'save_customization'));
        add_action('wp_ajax_nopriv_kustomizer_save_customization', array($this, 'save_customization'));
        
        add_action('wp_ajax_kustomizer_generate_layout', array($this, 'generate_layout'));
        add_action('wp_ajax_nopriv_kustomizer_generate_layout', array($this, 'generate_layout'));
        
        // Admin AJAX actions
        add_action('wp_ajax_kustomizer_admin_upload', array($this, 'admin_file_upload'));
    }
    
    /**
     * Handle file uploads (textures, SVGs)
     */
    public function handle_file_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kustomizer_nonce')) {
            wp_die(__('Security check failed', 'kustomizer'));
        }
        
        $file_type = sanitize_text_field($_POST['file_type']); // 'texture' or 'svg'
        $product_id = absint($_POST['product_id']);
        
        if (!$product_id || !in_array($file_type, array('texture', 'svg'))) {
            wp_send_json_error(__('Invalid parameters', 'kustomizer'));
        }
        
        // Check if uploads are allowed for this product
        $allow_uploads = get_post_meta($product_id, '_kustomizer_allow_' . $file_type, true);
        if ($allow_uploads !== 'yes') {
            wp_send_json_error(__('File uploads not allowed for this product', 'kustomizer'));
        }
        
        // Handle file upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['file'];
        
        // Validate file type
        $allowed_types = array();
        if ($file_type === 'texture') {
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        } elseif ($file_type === 'svg') {
            $allowed_types = array('image/svg+xml');
        }
        
        if (!in_array($uploadedfile['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type', 'kustomizer'));
        }
        
        // Set upload overrides
        $upload_overrides = array(
            'test_form' => false,
            'upload_error_handler' => array($this, 'upload_error_handler'),
        );
        
        // Upload file
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Store file info in session or temporary storage
            $file_data = array(
                'url' => $movefile['url'],
                'file' => $movefile['file'],
                'type' => $file_type,
                'product_id' => $product_id,
                'uploaded_at' => current_time('mysql')
            );
            
            // Store in session for later use
            if (!session_id()) {
                session_start();
            }
            
            if (!isset($_SESSION['kustomizer_uploads'])) {
                $_SESSION['kustomizer_uploads'] = array();
            }
            
            $_SESSION['kustomizer_uploads'][] = $file_data;
            
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'message' => __('File uploaded successfully', 'kustomizer')
            ));
        } else {
            wp_send_json_error($movefile['error']);
        }
    }
    
    /**
     * Save customization data
     */
    public function save_customization() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kustomizer_nonce')) {
            wp_die(__('Security check failed', 'kustomizer'));
        }
        
        $product_id = absint($_POST['product_id']);
        $customization_data = wp_unslash($_POST['customization_data']);
        
        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'kustomizer'));
        }
        
        // Validate and sanitize customization data
        $customization = json_decode($customization_data, true);
        if (!$customization) {
            wp_send_json_error(__('Invalid customization data', 'kustomizer'));
        }
        
        // Store in session for cart integration
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['kustomizer_customization_' . $product_id] = array(
            'product_id' => $product_id,
            'data' => $customization,
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success(array(
            'message' => __('Customization saved', 'kustomizer')
        ));
    }
    
    /**
     * Generate final layout
     */
    public function generate_layout() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kustomizer_nonce')) {
            wp_die(__('Security check failed', 'kustomizer'));
        }
        
        $product_id = absint($_POST['product_id']);
        $layout_data = wp_unslash($_POST['layout_data']);
        
        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'kustomizer'));
        }
        
        // Decode layout data
        $layout = json_decode($layout_data, true);
        if (!$layout || !isset($layout['image'])) {
            wp_send_json_error(__('Invalid layout data', 'kustomizer'));
        }
        
        // Save layout image
        $image_data = $layout['image'];
        if (strpos($image_data, 'data:image/png;base64,') === 0) {
            $image_data = str_replace('data:image/png;base64,', '', $image_data);
            $image_data = base64_decode($image_data);
            
            $upload_dir = wp_upload_dir();
            $filename = 'layout_' . $product_id . '_' . time() . '.png';
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            if (file_put_contents($file_path, $image_data)) {
                $file_url = $upload_dir['url'] . '/' . $filename;
                
                // Store layout info in session
                if (!session_id()) {
                    session_start();
                }
                
                $_SESSION['kustomizer_layout_' . $product_id] = array(
                    'product_id' => $product_id,
                    'image_url' => $file_url,
                    'image_path' => $file_path,
                    'data' => $layout,
                    'timestamp' => current_time('mysql')
                );
                
                wp_send_json_success(array(
                    'layout_url' => $file_url,
                    'message' => __('Layout generated successfully', 'kustomizer')
                ));
            } else {
                wp_send_json_error(__('Failed to save layout image', 'kustomizer'));
            }
        } else {
            wp_send_json_error(__('Invalid image data', 'kustomizer'));
        }
    }
    
    /**
     * Admin file upload (for STL files and default textures)
     */
    public function admin_file_upload() {
        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'kustomizer'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kustomizer_admin_nonce')) {
            wp_die(__('Security check failed', 'kustomizer'));
        }
        
        $file_type = sanitize_text_field($_POST['file_type']); // 'stl' or 'texture'
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['file'];
        
        // Validate file type
        $allowed_types = array();
        if ($file_type === 'stl') {
            $allowed_types = array('application/octet-stream', 'model/stl');
        } elseif ($file_type === 'texture') {
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        }
        
        $file_extension = strtolower(pathinfo($uploadedfile['name'], PATHINFO_EXTENSION));
        if ($file_type === 'stl' && $file_extension !== 'stl') {
            wp_send_json_error(__('Please upload an STL file', 'kustomizer'));
        }
        
        // Set upload overrides
        $upload_overrides = array(
            'test_form' => false,
        );
        
        // Upload file
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'file' => $movefile['file'],
                'message' => __('File uploaded successfully', 'kustomizer')
            ));
        } else {
            wp_send_json_error($movefile['error']);
        }
    }
    
    /**
     * Custom upload error handler
     */
    public function upload_error_handler($file, $message) {
        return array('error' => $message);
    }
}