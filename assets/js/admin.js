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
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // STL file upload
            $(document).on('click', '.upload_stl_file', this.openSTLUploader);
            $(document).on('click', '.upload_texture_file', this.openTextureUploader);
            $(document).on('click', '.kustomizer-upload-stl', this.openMediaUploader);
            $(document).on('click', '.kustomizer-remove-stl', this.removeSTLFile);
            
            // Settings test
            $(document).on('click', '.kustomizer-test-settings', this.testSettings);
            
            // Product type change
            $('select#product-type').on('change', this.toggleProductOptions);
            
            // Form validation
            $('#publish, #save-post').on('click', this.validateForm);
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
            
            var button = $(this);
            var targetInput = $('#_kustomizer_stl_file');
            
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
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
                
                // Show success message
                button.after('<span class="upload-success" style="color: green; margin-left: 10px;">✓ File uploaded</span>');
                setTimeout(function() {
                    $('.upload-success').fadeOut();
                }, 3000);
            });
            
            mediaUploader.open();
        },
        
        /**
         * Open texture file uploader
         */
        openTextureUploader: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var targetInput = $('#_kustomizer_default_texture');
            
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
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
                
                // Show success message
                button.after('<span class="upload-success" style="color: green; margin-left: 10px;">✓ Image uploaded</span>');
                setTimeout(function() {
                    $('.upload-success').fadeOut();
                }, 3000);
            });
            
            mediaUploader.open();
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
            var customizerOptions = $('.kustomizer-product-options');
            
            if (productType === 'kustomizer_product') {
                customizerOptions.show();
            } else {
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
        KustomizerAdmin.init();
    });

})(jQuery);