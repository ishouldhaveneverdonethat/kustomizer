# Kustomizer - WooCommerce 3D Product Customizer

![Kustomizer Logo](https://img.shields.io/badge/Kustomizer-3D%20Product%20Customizer-blue?style=for-the-badge)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue?style=flat-square)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0+-purple?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue?style=flat-square)
![License](https://img.shields.io/badge/License-GPL%20v2-green?style=flat-square)

A powerful WordPress plugin that transforms WooCommerce into a 3D product customization platform. Allow customers to personalize STL models with textures, text, and SVG graphics in real-time.

## 🚀 Features

### 🎯 Core Functionality
- **Interactive 3D Viewer**: WebGL-powered STL model viewer using Three.js
- **Real-time Customization**: Apply textures, add text, and overlay SVG graphics
- **Live Preview**: See changes instantly in the 3D environment
- **Layout Generation**: Capture final designs for manufacturing
- **Seamless Integration**: Native WooCommerce cart and order processing

### 🎨 Customization Options
- **Texture Mapping**: Upload and apply custom images as textures
- **Text Overlay**: Add custom text with font, size, and color controls
- **SVG Graphics**: Import vector graphics with scaling and positioning
- **Interactive Controls**: Rotate, zoom, and pan the 3D model
- **Mobile Responsive**: Works on desktop, tablet, and mobile devices

### 🛠 Admin Features
- **Custom Product Type**: "Kustomizer Product" for WooCommerce
- **Product Configuration**: Set customization options per product
- **Global Settings**: Plugin-wide configuration panel
- **Order Management**: View customization details in orders
- **File Management**: Automatic file organization and cleanup

## 📦 Installation

### Requirements
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Modern web browser with WebGL support

### Quick Install

1. **Download the plugin**
   ```bash
   git clone https://github.com/yourusername/kustomizer.git
   ```

2. **Upload to WordPress**
   ```
   wp-content/plugins/kustomizer/
   ```

3. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Activate "Kustomizer - WooCommerce 3D Product Customizer"

4. **Configure settings**
   - Navigate to WooCommerce → Kustomizer
   - Configure global settings

## 🔧 Configuration

### Creating Kustomizer Products

1. **Add New Product**
   - Products → Add New
   - Product Type: "Kustomizer Product"

2. **Configure Product**
   - Upload STL file
   - Set default texture (optional)
   - Enable customization features:
     - ✅ Allow Text Customization
     - ✅ Allow SVG Upload  
     - ✅ Allow Texture Upload
   - Set character limits and fonts
   - Define customization pricing

### Global Settings

Navigate to **WooCommerce → Kustomizer** to configure:

- **File Upload Limits**: Maximum file sizes and allowed types
- **3D Viewer Settings**: Background colors and camera positions  
- **Text Options**: Global fonts and character limits
- **Security Settings**: File type restrictions and validation

## 🎮 Usage

### Customer Experience

1. **Product Page**
   - View 3D model in interactive viewer
   - Access customization panel

2. **Apply Texture**
   - Upload image (JPG, PNG, GIF)
   - See texture applied in real-time

3. **Add Text**
   - Enter custom text
   - Choose font, size, color
   - Position on model

4. **Upload Graphics**
   - Add SVG files
   - Scale and color adjustment
   - Position on 3D model

5. **Generate Layout**
   - Capture final design
   - Add to cart with customizations

### Order Processing

Orders include:
- Customization summary
- Layout preview images
- Manufacturing-ready files
- Customer download links

## 🧩 API Reference

### JavaScript Integration

```javascript
// Initialize Kustomizer
const kustomizer = new Kustomizer('viewer-container', {
    stlFile: '/path/to/model.stl',
    defaultTexture: '/path/to/texture.jpg',
    allowTextCustomization: true,
    allowSVGUpload: true
});

// Apply texture
kustomizer.applyTexture(fileInput.files[0]);

// Add text
kustomizer.addText('Custom Text', {
    font: 'Arial',
    size: 12,
    color: '#ff0000'
});

// Generate layout
const layout = kustomizer.generateLayout();
```

### PHP Hooks

```php
// Modify allowed file types
add_filter('kustomizer_allowed_file_types', function($types) {
    $types[] = 'image/webp';
    return $types;
});

// Process order customizations
add_action('kustomizer_order_processed', function($order_id, $customization_data) {
    // Send to manufacturing API
    send_to_manufacturer($order_id, $customization_data);
}, 10, 2);
```

## 📁 File Structure

```
kustomizer/
├── kustomizer.php                 # Main plugin file
├── includes/
│   ├── class-product-type.php     # Custom product type
│   ├── class-ajax-handlers.php    # AJAX endpoints
│   ├── class-cart-handler.php     # Cart integration
│   ├── class-order-handler.php    # Order processing
│   └── class-admin-settings.php   # Admin settings
├── assets/
│   ├── js/
│   │   ├── kustomizer.js          # Main JavaScript
│   │   ├── admin.js               # Admin interface
│   │   ├── STLLoader.js           # Three.js STL loader
│   │   └── OrbitControls.js       # Camera controls
│   └── css/
│       ├── kustomizer.css         # Frontend styles
│       └── admin.css              # Admin styles
├── templates/
│   └── customizer-interface.php   # Product page template
├── languages/                     # Translation files
└── README.md
```

## 🔒 Security Features

- **File Type Validation**: Strict file type checking
- **Nonce Verification**: CSRF protection on all AJAX calls
- **Input Sanitization**: All user inputs properly sanitized
- **Upload Restrictions**: Configurable file size and type limits
- **Session Management**: Secure temporary data storage

## 🌐 Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 60+ | ✅ Full Support |
| Firefox | 55+ | ✅ Full Support |
| Safari | 11+ | ✅ Full Support |
| Edge | 79+ | ✅ Full Support |
| Mobile Safari | 11+ | ✅ Full Support |
| Chrome Mobile | 60+ | ✅ Full Support |

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone repository
git clone https://github.com/yourusername/kustomizer.git

# Install development dependencies
npm install

# Start development server
npm run dev
```

### Coding Standards

- Follow WordPress coding standards
- Use semantic versioning
- Include PHPDoc comments
- Write comprehensive tests

## 📝 Changelog

### v1.0.0 (2024-01-XX)
- Initial release
- 3D STL viewer integration
- Texture mapping functionality
- Text and SVG overlay features
- WooCommerce integration
- Order processing system

## 🆘 Support

- **Documentation**: [Wiki](https://github.com/yourusername/kustomizer/wiki)
- **Issues**: [GitHub Issues](https://github.com/yourusername/kustomizer/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/kustomizer/discussions)
- **Email**: support@kustomizer.com

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

Built with love using:
- [Three.js](https://threejs.org/) - 3D Graphics Library
- [WooCommerce](https://woocommerce.com/) - E-commerce Platform  
- [WordPress](https://wordpress.org/) - Content Management System

---

**Made with ❤️ for the WordPress community**

[![GitHub stars](https://img.shields.io/github/stars/yourusername/kustomizer?style=social)](https://github.com/yourusername/kustomizer/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/yourusername/kustomizer?style=social)](https://github.com/yourusername/kustomizer/network)
[![GitHub issues](https://img.shields.io/github/issues/yourusername/kustomizer)](https://github.com/yourusername/kustomizer/issues)