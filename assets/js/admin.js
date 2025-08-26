/**
 * Kustomizer Admin JavaScript
 * Handles admin interface functionality
 *
 * @package Kustomizer
 * @since 1.0.1
 */

(function($) {
    'use strict';

    /**
     * Kustomizer Admin
     */
    var KustomizerAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initMediaUploader();
            this.initColorPicker();
            this.initProductTypeHandler();
        },
        
        /**
         * Initialize product type handler
         */
        initProductTypeHandler: function() {
            // Force check on page load
            setTimeout(function() {
                var productType = $('select#product-type').val();
                console.log('Initial product type:', productType);
                
                if (productType === 'kustomizer_product') {
                    $('.show_if_kustomizer_product').show();
                    console.log('Showed Kustomizer options on init');
                } else {
                    $('.show_if_kustomizer_product').hide();
                }
            }, 500);
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // STL file upload - multiple selectors to catch all buttons
            $(document).on('click', '.upload_stl_file', this.openSTLUploader);
            $(document).on('click', '.upload_texture_file', this.openTextureUploader);
            $(document).on('click', '.kustomizer-upload-stl', this.openSTLUploader);
            $(document).on('click', '.kustomizer-remove-stl', this.removeSTLFile);
            
            // Debug: Log when buttons are clicked
            $(document).on('click', '.upload_stl_file, .upload_texture_file', function(e) {
                console.log('Kustomizer: Upload button clicked:', $(this).attr('class'));
            });
            
            // Settings test
            $(document).on('click', '.kustomizer-test-settings', this.testSettings);
            
            // Product type change
            $('select#product-type').on('change', this.toggleProductOptions);
            
            // Form validation
            $('#publish, #save-post').on('click', this.validateForm);
            
            // Ensure WordPress media uploader is available
            if (typeof wp !== 'undefined' && wp.media) {
                console.log('Kustomizer: WordPress media uploader is available');
            } else {
                console.error('Kustomizer: WordPress media uploader is NOT available');
            }
        },
        
        /**
         * Initialize media uploader
         */
        initMediaUploader: function() {
            // Media uploader is initialized on demand
        },
        
        /**
         * Initialize color picker
         */
        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('.kustomizer-color-picker').wpColorPicker();
            }
        },
        
        /**
         * Open STL file uploader
         */
        openSTLUploader: function(e) {
            e.preventDefault();
            console.log('Kustomizer: STL uploader clicked');
            
            // Check if WordPress media uploader is available
            if (typeof wp === 'undefined' || !wp.media) {
                alert('WordPress media uploader is not available. Please refresh the page.');
                console.error('Kustomizer: wp.media is not defined');
                return;
            }
            
            var button = $(this);
            var targetInput = $('#_kustomizer_stl_file');
            
            console.log('Kustomizer: Target input found:', targetInput.length > 0);
            
            // Create media uploader
            var mediaUploader = wp.media({
                title: 'Select STL File',
                button: {
                    text: 'Use This File'
                },
                multiple: false
            });
            
            // Media selected
            mediaUploader.on('select', function() {
                try {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    console.log('Kustomizer: File selected:', attachment.filename);
                    
                    targetInput.val(attachment.url);
                    console.log('Kustomizer: Input value set to:', attachment.url);
                    
                    // Show success message
                    $('.upload-success').remove(); // Remove any existing success messages
                    button.after('<span class="upload-success" style="color: green; margin-left: 10px; font-weight: bold;">✓ File uploaded: ' + attachment.filename + '</span>');
                    
                    setTimeout(function() {
                        $('.upload-success').fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                } catch (error) {
                    console.error('Kustomizer: Error processing file selection:', error);
                    alert('Error processing file selection. Please try again.');
                }
            });
            
            // Open the media uploader
            try {
                mediaUploader.open();
                console.log('Kustomizer: Media uploader opened successfully');
            } catch (error) {
                console.error('Kustomizer: Error opening media uploader:', error);
                alert('Error opening media uploader. Please try refreshing the page.');
            }
        },
        
        /**
         * Open texture file uploader
         */
        openTextureUploader: function(e) {
            e.preventDefault();
            console.log('Kustomizer: Texture uploader clicked');
            
            // Check if WordPress media uploader is available
            if (typeof wp === 'undefined' || !wp.media) {
                alert('WordPress media uploader is not available. Please refresh the page.');
                console.error('Kustomizer: wp.media is not defined');
                return;
            }
            
            var button = $(this);
            var targetInput = $('#_kustomizer_default_texture');
            
            console.log('Kustomizer: Target input found:', targetInput.length > 0);
            
            // Create media uploader
            var mediaUploader = wp.media({
                title: 'Select Texture Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false,
                library: {
                    type: ['image']
                }
            });
            
            // Media selected
            mediaUploader.on('select', function() {
                try {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    console.log('Kustomizer: Image selected:', attachment.filename);
                    
                    targetInput.val(attachment.url);
                    console.log('Kustomizer: Input value set to:', attachment.url);
                    
                    // Show success message
                    $('.upload-success').remove(); // Remove any existing success messages
                    button.after('<span class="upload-success" style="color: green; margin-left: 10px; font-weight: bold;">✓ Image uploaded: ' + attachment.filename + '</span>');
                    
                    setTimeout(function() {
                        $('.upload-success').fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                } catch (error) {
                    console.error('Kustomizer: Error processing image selection:', error);
                    alert('Error processing image selection. Please try again.');
                }
            });
            
            // Open the media uploader
            try {
                mediaUploader.open();
                console.log('Kustomizer: Media uploader opened successfully');
            } catch (error) {
                console.error('Kustomizer: Error opening media uploader:', error);
                alert('Error opening media uploader. Please try refreshing the page.');
            }
        },
        openMediaUploader: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var targetInput = button.siblings('input[type="hidden"]');
            var previewContainer = button.siblings('.kustomizer-file-preview');
            
            // Create media uploader
            var mediaUploader = wp.media({
                title: 'Select STL File',
                button: {
                    text: 'Use This File'
                },
                multiple: false,
                library: {
                    type: ['application/octet-stream', 'model/stl']
                }
            });
            
            // Media selected
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Validate file type
                if (!KustomizerAdmin.isValidSTLFile(attachment)) {
                    alert('Please select a valid STL file.');
                    return;
                }
                
                // Update input and preview
                targetInput.val(attachment.url);
                KustomizerAdmin.updateFilePreview(previewContainer, attachment);
                
                // Show remove button
                button.siblings('.kustomizer-remove-stl').show();
            });
            
            mediaUploader.open();
        },
        
        /**
         * Remove STL file
         */
        removeSTLFile: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var targetInput = button.siblings('input[type="hidden"]');
            var previewContainer = button.siblings('.kustomizer-file-preview');
            
            // Confirm removal
            if (confirm('Are you sure you want to remove this file?')) {
                targetInput.val('');
                previewContainer.empty();
                button.hide();
            }
        },
        
        /**
         * Update file preview
         */
        updateFilePreview: function(container, attachment) {
            var preview = '<div class="kustomizer-file-info">';
            preview += '<strong>' + attachment.filename + '</strong><br>';
            preview += '<small>Size: ' + KustomizerAdmin.formatFileSize(attachment.filesizeInBytes) + '</small>';
            preview += '</div>';
            
            container.html(preview);
        },
        
        /**
         * Validate STL file
         */
        isValidSTLFile: function(attachment) {
            var validExtensions = ['stl'];
            var extension = attachment.filename.split('.').pop().toLowerCase();
            return validExtensions.indexOf(extension) !== -1;
        },
        
        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Toggle product options
         */
        toggleProductOptions: function() {
            var productType = $(this).val();
            var customizerOptions = $('.show_if_kustomizer_product');
            
            console.log('Product type changed to:', productType);
            console.log('Found Kustomizer options:', customizerOptions.length);
            
            if (productType === 'kustomizer_product') {
                console.log('Showing Kustomizer options');
                customizerOptions.show();
                customizerOptions.find('input, select, textarea').prop('disabled', false);
            } else {
                console.log('Hiding Kustomizer options');
                customizerOptions.hide();
            }
        },
        
        /**
         * Validate form
         */
        validateForm: function(e) {
            var productType = $('select#product-type').val();
            
            if (productType === 'kustomizer_product') {
                var stlFile = $('#_kustomizer_stl_file').val();
                
                if (!stlFile) {
                    alert('Please upload an STL file for this Kustomizer product.');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        },
        
        /**
         * Test settings
         */
        testSettings: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            
            button.text('Testing...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kustomizer_test_settings',
                    nonce: $('#kustomizer_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('Settings test successful: ' + response.data.message);
                    } else {
                        alert('Settings test failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Settings test failed: Unable to connect to server.');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        }
    };

    /**
     * Document ready
     */
    $(document).ready(function() {
        console.log('Kustomizer Admin: Document ready - initializing admin interface');
        console.log('Kustomizer Admin: jQuery version:', $.fn.jquery);
        console.log('Kustomizer Admin: wp object available:', typeof wp !== 'undefined');
        console.log('Kustomizer Admin: wp.media available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
        
        // Check if upload buttons exist
        var stlButtons = $('.upload_stl_file');
        var textureButtons = $('.upload_texture_file');
        console.log('Kustomizer Admin: Found STL upload buttons:', stlButtons.length);
        console.log('Kustomizer Admin: Found texture upload buttons:', textureButtons.length);
        
        // Log button elements for debugging
        stlButtons.each(function(index) {
            console.log('Kustomizer Admin: STL button ' + index + ':', this);
        });
        
        textureButtons.each(function(index) {
            console.log('Kustomizer Admin: Texture button ' + index + ':', this);
        });
        
        KustomizerAdmin.init();
    });

})(jQuery);