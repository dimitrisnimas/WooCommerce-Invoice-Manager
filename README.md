# WooCommerce Invoice Manager

A comprehensive WordPress plugin for managing and sending PDF invoices to WooCommerce customers. This plugin allows manual PDF upload and automatic email delivery, making invoice management simple and efficient.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)
![License](https://img.shields.io/badge/License-GPL%20v2-green)

## 🌟 Features

- **📤 Manual PDF Upload**: Upload invoice PDFs for any WooCommerce order
- **📧 Automatic Email Delivery**: Send invoices directly to customers via email
- **🔐 Secure File Management**: Files are stored securely with restricted access
- **👥 Customer Portal**: Customers can view and download their invoices from My Account
- **📊 Invoice Tracking**: Track upload dates, send dates, and invoice status
- **🎨 Beautiful Email Templates**: Professional HTML email templates in Greek
- **🔍 Order Search**: Easy order selection with customer details preview
- **📱 Responsive Design**: Works seamlessly on desktop and mobile devices
- **🌐 Multilingual Ready**: Full Greek language support (easily translatable)
- **🔒 Security Features**: Nonce verification, user capability checks, and file access control

## 📋 Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- At least 10MB PHP upload limit

## 🚀 Installation

### Method 1: Manual Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin** and choose the ZIP file
5. Click **Install Now**
6. After installation, click **Activate Plugin**

### Method 2: FTP Installation

1. Download and extract the plugin ZIP file
2. Upload the `woocommerce-invoice-manager` folder to `/wp-content/plugins/`
3. Log in to WordPress admin panel
4. Navigate to **Plugins**
5. Activate **WooCommerce Invoice Manager**

### Method 3: Clone from GitHub

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/yourusername/woocommerce-invoice-manager.git
```

Then activate the plugin from WordPress admin panel.

## 📁 Directory Structure

```
woocommerce-invoice-manager/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── style.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-admin.php
│   ├── class-email-manager.php
│   ├── class-file-manager.php
│   ├── class-frontend.php
│   └── class-wc-email-invoice.php
├── languages/
│   └── wc-invoice-manager.pot
├── templates/
│   └── emails/
│       ├── customer-invoice.php
│       └── plain/
│           └── customer-invoice.php
├── woocommerce-invoice-manager.php
├── README.md
└── LICENSE
```

## 🎯 Usage

### Admin Interface

1. Navigate to **WooCommerce > Παραστατικά** in WordPress admin
2. Select an order from the dropdown
3. Upload a PDF invoice file (max 10MB)
4. Click **Ανέβασμα Παραστατικού** (Upload Invoice)
5. View the invoice in the list below
6. Click **Αποστολή** (Send) to email the invoice to the customer

### Customer Portal

Customers can access their invoices:
1. Log in to their account
2. Navigate to **My Account > Παραστατικά**
3. View all their invoices
4. Download PDFs directly

## 🔧 Configuration

### PHP Configuration

Ensure your PHP settings allow file uploads:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
memory_limit = 128M
```

### WordPress Debug Mode

For troubleshooting, enable debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### File Permissions

The plugin automatically creates the upload directory at:
```
/wp-content/uploads/invoices/
```

Ensure proper permissions:
```bash
chmod 755 /wp-content/uploads/invoices/
chown www-data:www-data /wp-content/uploads/invoices/
```

## 🗄️ Database

The plugin creates a custom table:

```sql
wp_wc_invoice_manager
├── id (PRIMARY KEY)
├── order_id
├── customer_email
├── file_name
├── file_path
├── upload_date
├── sent_date
└── status
```

## 🔒 Security Features

- **File Type Validation**: Only PDF files are accepted
- **Size Limitation**: Maximum 10MB per file
- **Nonce Verification**: All AJAX requests are protected
- **Capability Checks**: Only users with `manage_woocommerce` capability can upload
- **Secure Storage**: Files stored outside public directory with `.htaccess` protection
- **User Verification**: Customers can only download their own invoices

## 🎨 Customization

### Email Templates

Customize email templates in:
```
templates/emails/customer-invoice.php
templates/emails/plain/customer-invoice.php
```

### Styling

Modify admin styles:
```
assets/css/admin.css
```

Modify frontend styles:
```
assets/css/style.css
```

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify email content
add_filter('wc_invoice_email_content', 'custom_email_content', 10, 2);

// Modify upload directory
add_filter('wc_invoice_upload_dir', 'custom_upload_dir');

// Action after invoice sent
add_action('wc_invoice_sent', 'after_invoice_sent', 10, 2);
```

## 🐛 Troubleshooting

### Upload Issues

**Problem**: Files won't upload
**Solutions**:
1. Check PHP upload limits
2. Verify directory permissions
3. Check server error logs
4. Enable WordPress debug mode
5. Test with a smaller PDF file

### Email Issues

**Problem**: Emails not sending
**Solutions**:
1. Install and configure an SMTP plugin
2. Check email logs in WordPress
3. Verify customer email address
4. Test with `wp_mail()` function
5. Check spam folders

### Permission Issues

**Problem**: Cannot access certain features
**Solutions**:
1. Ensure user has `manage_woocommerce` capability
2. Check WooCommerce plugin is active
3. Verify WordPress user roles

### Database Issues

**Problem**: Table not created
**Solutions**:
1. Deactivate and reactivate plugin
2. Check database user permissions
3. Manually run SQL from plugin activation

## 📊 System Requirements Check

Run this diagnostic from the admin panel:

1. Go to **WooCommerce > Παραστατικά**
2. Scroll to bottom for **System Information**
3. Verify all requirements are met:
   - ✅ Upload directory exists
   - ✅ Upload directory is writable
   - ✅ PHP upload limits sufficient
   - ✅ Database table exists

## 🌍 Localization

The plugin is fully translatable. Current languages:
- 🇬🇷 Greek (default)

To add a new language:
1. Copy `languages/wc-invoice-manager.pot`
2. Translate using Poedit or similar tool
3. Save as `wc-invoice-manager-{locale}.po` and `.mo`
4. Place in `languages/` directory

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
WooCommerce Invoice Manager
Copyright (C) 2024 KUBIK

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👥 Credits

Developed by [KUBIK](https://kubik.gr) and [Dimitris Nimas](https://dimitrisnimas.gr)

### Third-Party Libraries

- WordPress Core
- WooCommerce
- jQuery

---

Made with ❤️ by [KUBIK](https://kubik.gr )and [Dimitris Nimas](https://dimitrisnimas.gr)
