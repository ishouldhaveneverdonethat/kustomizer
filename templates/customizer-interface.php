<?php
/**
 * Customizer Interface Template
 * Displays the 3D customizer interface on product pages
 *
 * @package Kustomizer
 * @since 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $product;

if (!$product || $product->get_type() !== 'kustomizer_product') {
    return;
}

$stl_file = get_post_meta($product->get_id(), '_kustomizer_stl_file', true);
$allow_texture = get_post_meta($product->get_id(), '_kustomizer_allow_texture', true);
$allow_text = get_post_meta($product->get_id(), '_kustomizer_allow_text', true);
$allow_svg = get_post_meta($product->get_id(), '_kustomizer_allow_svg', true);

if (empty($stl_file)) {
    return;
}
?>

<div id="kustomizer-interface" class="kustomizer-interface">
    <h3><?php _e('Customize Your Product', 'kustomizer'); ?></h3>
    
    <div class="kustomizer-container">
        <!-- 3D Viewer -->
        <div class="kustomizer-viewer-section">
            <div id="kustomizer-viewer" class="kustomizer-viewer">
                <div class="kustomizer-loading">
                    <p><?php _e('Loading 3D Model...', 'kustomizer'); ?></p>
                </div>
            </div>
            
            <div class="kustomizer-viewer-controls">
                <button type="button" class="kustomizer-reset-view">
                    <?php _e('Reset View', 'kustomizer'); ?>
                </button>
                <button type="button" class="kustomizer-fullscreen">
                    <?php _e('Fullscreen', 'kustomizer'); ?>
                </button>
            </div>
        </div>
        
        <!-- Customization Panel -->
        <div class="kustomizer-panel-section">
            <div class="kustomizer-panel">
                
                <?php if ($allow_texture === 'yes') : ?>
                <!-- Texture Upload -->
                <div class="kustomizer-option-group">
                    <h4><?php _e('Apply Texture', 'kustomizer'); ?></h4>
                    <div class="kustomizer-texture-upload">
                        <input type="file" id="kustomizer-texture-input" accept="image/*" style="display: none;">
                        <button type="button" id="kustomizer-texture-btn" class="kustomizer-upload-btn">
                            <?php _e('Upload Texture', 'kustomizer'); ?>
                        </button>
                        <div id="kustomizer-texture-preview" class="kustomizer-preview"></div>
                        <button type="button" id="kustomizer-texture-remove" class="kustomizer-remove-btn" style="display: none;">
                            <?php _e('Remove Texture', 'kustomizer'); ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($allow_text === 'yes') : ?>
                <!-- Text Customization -->
                <div class="kustomizer-option-group">
                    <h4><?php _e('Add Text', 'kustomizer'); ?></h4>
                    <div class="kustomizer-text-options">
                        <div class="kustomizer-field">
                            <label for="kustomizer-text-input"><?php _e('Text:', 'kustomizer'); ?></label>
                            <input type="text" id="kustomizer-text-input" maxlength="50" placeholder="<?php _e('Enter your text', 'kustomizer'); ?>">
                        </div>
                        
                        <div class="kustomizer-field-row">
                            <div class="kustomizer-field">
                                <label for="kustomizer-text-font"><?php _e('Font:', 'kustomizer'); ?></label>
                                <select id="kustomizer-text-font">
                                    <option value="Arial">Arial</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Courier New">Courier New</option>
                                </select>
                            </div>
                            
                            <div class="kustomizer-field">
                                <label for="kustomizer-text-size"><?php _e('Size:', 'kustomizer'); ?></label>
                                <input type="range" id="kustomizer-text-size" min="8" max="48" value="16">
                                <span id="kustomizer-text-size-value">16px</span>
                            </div>
                        </div>
                        
                        <div class="kustomizer-field">
                            <label for="kustomizer-text-color"><?php _e('Color:', 'kustomizer'); ?></label>
                            <input type="color" id="kustomizer-text-color" value="#000000">
                        </div>
                        
                        <div class="kustomizer-field">
                            <button type="button" id="kustomizer-text-apply" class="kustomizer-apply-btn">
                                <?php _e('Apply Text', 'kustomizer'); ?>
                            </button>
                            <button type="button" id="kustomizer-text-remove" class="kustomizer-remove-btn" style="display: none;">
                                <?php _e('Remove Text', 'kustomizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($allow_svg === 'yes') : ?>
                <!-- SVG Upload -->
                <div class="kustomizer-option-group">
                    <h4><?php _e('Add Graphics', 'kustomizer'); ?></h4>
                    <div class="kustomizer-svg-upload">
                        <input type="file" id="kustomizer-svg-input" accept=".svg,image/svg+xml" style="display: none;">
                        <button type="button" id="kustomizer-svg-btn" class="kustomizer-upload-btn">
                            <?php _e('Upload SVG', 'kustomizer'); ?>
                        </button>
                        <div id="kustomizer-svg-preview" class="kustomizer-preview"></div>
                        
                        <div id="kustomizer-svg-options" class="kustomizer-svg-controls" style="display: none;">
                            <div class="kustomizer-field">
                                <label for="kustomizer-svg-scale"><?php _e('Scale:', 'kustomizer'); ?></label>
                                <input type="range" id="kustomizer-svg-scale" min="0.1" max="2" step="0.1" value="1">
                                <span id="kustomizer-svg-scale-value">1.0x</span>
                            </div>
                            
                            <div class="kustomizer-field">
                                <label for="kustomizer-svg-color"><?php _e('Color:', 'kustomizer'); ?></label>
                                <input type="color" id="kustomizer-svg-color" value="#000000">
                            </div>
                            
                            <button type="button" id="kustomizer-svg-remove" class="kustomizer-remove-btn">
                                <?php _e('Remove SVG', 'kustomizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Generate Layout -->
                <div class="kustomizer-option-group">
                    <h4><?php _e('Generate Final Design', 'kustomizer'); ?></h4>
                    <div class="kustomizer-generate-section">
                        <button type="button" id="kustomizer-generate-layout" class="kustomizer-generate-btn">
                            <?php _e('Generate Layout', 'kustomizer'); ?>
                        </button>
                        <div id="kustomizer-layout-preview" class="kustomizer-layout-preview" style="display: none;">
                            <img id="kustomizer-layout-image" alt="<?php _e('Generated Layout', 'kustomizer'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Add to Cart -->
                <div class="kustomizer-option-group">
                    <div class="kustomizer-cart-section">
                        <button type="button" id="kustomizer-add-to-cart" class="kustomizer-cart-btn" disabled>
                            <?php _e('Add Customized Product to Cart', 'kustomizer'); ?>
                        </button>
                        <p class="kustomizer-cart-note">
                            <?php _e('Please generate layout before adding to cart.', 'kustomizer'); ?>
                        </p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Hidden form data -->
    <form id="kustomizer-form" style="display: none;">
        <input type="hidden" id="kustomizer-product-id" value="<?php echo esc_attr($product->get_id()); ?>">
        <input type="hidden" id="kustomizer-data" name="kustomizer_data" value="">
        <?php wp_nonce_field('kustomizer_add_to_cart', 'kustomizer_nonce'); ?>
    </form>
</div>

<script type="text/javascript">
// Initialize Kustomizer when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Kustomizer !== 'undefined') {
        window.kustomizerInstance = new Kustomizer({
            container: 'kustomizer-viewer',
            stlFile: '<?php echo esc_js($stl_file); ?>',
            productId: <?php echo (int) $product->get_id(); ?>,
            allowTexture: <?php echo $allow_texture === 'yes' ? 'true' : 'false'; ?>,
            allowText: <?php echo $allow_text === 'yes' ? 'true' : 'false'; ?>,
            allowSVG: <?php echo $allow_svg === 'yes' ? 'true' : 'false'; ?>
        });
    }
});
</script>