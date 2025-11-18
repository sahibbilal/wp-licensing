# WP Licensing - WordPress SaaS Licensing & Auto Update System

A professional, commercial-grade WordPress plugin for managing software licenses and providing automatic updates for plugins and themes.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [How It Works](#how-it-works)
- [Installation](#installation)
- [Admin Dashboard](#admin-dashboard)
- [Integration Guide](#integration-guide)
- [API Reference](#api-reference)
- [Security Features](#security-features)
- [License States](#license-states)
- [Project Structure](#project-structure)
- [Development Setup](#development-setup)
- [Database Tables](#database-tables)
- [Common Integration Patterns](#common-integration-patterns)
- [Troubleshooting](#troubleshooting)
- [Support](#support)
- [Download Integration Files](#download-integration-files)

## 🎯 Overview

WP Licensing is a complete licensing and update management system for WordPress plugins and themes. It allows you to:

- **Manage Licenses**: Create, activate, deactivate, and track license keys
- **Control Updates**: Automatically deliver updates to licensed customers
- **Track Activations**: Monitor where licenses are being used
- **Secure Distribution**: Protect your premium plugins/themes from unauthorized use

## ✨ Features

- ✅ **License Management**: Create, activate, deactivate, and manage license keys
- ✅ **License Validation API**: RESTful API for validating licenses on customer sites
- ✅ **Auto Update System**: Automatic plugin/theme updates via API
- ✅ **Activation Tracking**: Track license activations per domain/IP
- ✅ **Admin Dashboard**: Modern React-based admin interface
- ✅ **Product Management**: Manage multiple products with version control
- ✅ **File Upload**: Upload plugin ZIP files directly from admin
- ✅ **Settings Management**: Configure upload limits, expiry days, and more
- ✅ **Security**: Rate limiting, nonce validation, secure queries
- ✅ **Analytics**: Dashboard with license usage statistics

## 🔧 How It Works

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    WP Licensing Server                       │
│  (Installed on your main WordPress site)                    │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Products   │  │   Licenses   │  │   Activations│      │
│  │   Database   │  │   Database   │  │   Database   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         REST API Endpoints                           │   │
│  │  • /validate  • /deactivate  • /update              │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTP Requests
                            │
┌─────────────────────────────────────────────────────────────┐
│              Customer WordPress Site                         │
│  (Your premium plugin/theme installed here)                 │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Your Plugin/Theme with License Integration          │   │
│  │  • License Manager (validates license)              │   │
│  │  • Update Checker (checks for updates)              │   │
│  │  • Admin Interface (license settings page)          │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Workflow

1. **License Creation**: Admin creates a license key in WP Licensing dashboard
2. **Customer Activation**: Customer enters license key in their plugin settings
3. **Validation**: Plugin sends license key to WP Licensing server for validation
4. **Activation**: Server validates and activates license, tracks the site
5. **Update Checks**: Plugin periodically checks server for new versions
6. **Auto Updates**: When new version is available, plugin downloads and installs it

## 📦 Installation

### Requirements

- WordPress 5.8+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 16+ (for building admin UI)

### Step 1: Install the Plugin

1. **Download or clone** the plugin to your WordPress `wp-content/plugins/` directory:
   ```bash
   cd wp-content/plugins
   git clone <repository-url> wp-licensing
   ```

2. **Install dependencies**:
   ```bash
   cd wp-licensing
   npm install
   ```

3. **Build the admin UI**:
   ```bash
   npm run build
   ```

4. **Activate the plugin** from WordPress admin panel → Plugins

### Step 2: Initial Setup

1. Go to **WP Licensing** in your WordPress admin menu
2. Navigate to **Settings** tab
3. Configure:
   - Maximum Upload Size (for plugin ZIP files)
   - License Expiry Days (default: 365)
   - Maximum Activations per License
   - Update Check Interval

### Step 3: Create Your First Product

1. Go to **Products** tab
2. Click **Create Product**
3. Fill in:
   - Product Name
   - Slug (auto-generated)
   - Version (e.g., 1.0.0)
   - Upload plugin ZIP file OR enter download URL
   - Changelog
   - Description
4. Click **Create**

### Step 4: Create Licenses

1. Go to **Licenses** tab
2. Click **Create License**
3. Fill in:
   - Product (select from dropdown)
   - Customer Email
   - Customer Name
   - Expiry Date (optional, leave empty for lifetime)
   - Activation Limit
4. Click **Create**

The license key will be automatically generated and can be sent to your customer.

## 🎨 Admin Dashboard

The WP Licensing admin dashboard provides a modern, React-based interface with the following sections:

### Dashboard
- Overview statistics
- Total licenses, products, activations
- Recent activity

### Licenses
- View all licenses
- Create new licenses
- Edit license expiry
- Delete licenses
- Filter by status

### Products
- View all products
- Create new products
- Edit products (version, file, changelog, description)
- Upload ZIP files
- Delete products

### API & Plugins
- API endpoint information
- Integration code examples
- Plugin details

### Settings
- Maximum upload size
- License expiry days
- Maximum activations
- Auto-update settings
- Update check interval

## 🔌 Integration Guide

This guide will show you how to integrate WP Licensing into your own WordPress plugin or theme.

### Quick Start (5 Minutes)

1. **Download the integration files** from the [Integration Files Repository](https://github.com/sahibbilal/wp-licensing-product) (or see example plugin below)
2. **Copy the files** to your plugin's `includes/` directory
3. **Rename the classes** to match your plugin name
4. **Configure** the license server URL and Product ID
5. **Initialize** in your main plugin file
6. **Test** the integration

### Download Integration Files

You can download the ready-to-use integration files from:

**🔗 [Integration Files Repository](https://github.com/sahibbilal/wp-licensing-product)**

The repository contains:
- `class-license-manager.php` - Handles license validation and deactivation
- `class-update-checker.php` - Handles automatic updates
- `class-admin.php` - Creates admin settings page for license management
- `README.md` - Detailed integration instructions
- `example-plugin.php` - Complete example plugin implementation

**Alternative:** You can also copy the files from the example plugin `wp-licensed-product` located in the same repository or download the complete example plugin.

### Detailed Integration Steps

#### Step 1: Download and Copy Required Files

Download the integration files from the repository above, then copy them to your plugin's `includes/` directory:

```
your-plugin/
├── your-plugin.php          # Main plugin file
└── includes/
    ├── class-license-manager.php    # Download from integration files repo
    ├── class-update-checker.php     # Download from integration files repo
    └── class-admin.php              # Download from integration files repo (optional)
```

#### Step 2: Rename Classes

In each copied file, rename the classes to match your plugin:

**class-license-manager.php:**
```php
// Change from:
class WP_Licensed_Product_License_Manager

// To:
class Your_Plugin_License_Manager
```

**class-update-checker.php:**
```php
// Change from:
class WP_Licensed_Product_Update_Checker

// To:
class Your_Plugin_Update_Checker
```

**class-admin.php:**
```php
// Change from:
class WP_Licensed_Product_Admin

// To:
class Your_Plugin_Admin
```

#### Step 3: Update Constants and Options

Update all option names and constants in the copied files:

**In class-license-manager.php:**
```php
// Change:
get_option( 'wp_licensed_product_license_key', '' )
get_option( 'wp_licensed_product_server_url', '' )
get_option( 'wp_licensed_product_id', 1 )

// To:
get_option( 'your_plugin_license_key', '' )
get_option( 'your_plugin_server_url', '' )
get_option( 'your_plugin_id', 1 )
```

**In class-update-checker.php:**
```php
// Change:
get_option( 'wp_licensed_product_license_key', '' )
get_option( 'wp_licensed_product_server_url', '' )
get_option( 'wp_licensed_product_id', 1 )

// To:
get_option( 'your_plugin_license_key', '' )
get_option( 'your_plugin_server_url', '' )
get_option( 'your_plugin_id', 1 )
```

**In class-admin.php:**
```php
// Change all occurrences of:
'wp_licensed_product_'

// To:
'your_plugin_'
```

#### Step 4: Update Plugin Main File

Add this to your main plugin file (`your-plugin.php`):

```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Version: 1.0.0
 * ...
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define constants
define( 'YOUR_PLUGIN_VERSION', '1.0.0' );
define( 'YOUR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YOUR_PLUGIN_SLUG', 'your-plugin-slug' );

/**
 * Initialize the plugin
 */
function your_plugin_init() {
	// Include required files
	require_once YOUR_PLUGIN_DIR . 'includes/class-license-manager.php';
	require_once YOUR_PLUGIN_DIR . 'includes/class-update-checker.php';
	require_once YOUR_PLUGIN_DIR . 'includes/class-admin.php';

	// Initialize license manager
	$license_manager = new Your_Plugin_License_Manager();
	$license_manager->init();

	// Initialize update checker
	$update_checker = new Your_Plugin_Update_Checker();
	$update_checker->init();

	// Initialize admin
	$admin = new Your_Plugin_Admin();
	$admin->init();
}
add_action( 'plugins_loaded', 'your_plugin_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'your_plugin_activate' );
function your_plugin_activate() {
	// Optionally validate license on activation
	$license_key = get_option( 'your_plugin_license_key', '' );
	if ( ! empty( $license_key ) ) {
		require_once YOUR_PLUGIN_DIR . 'includes/class-license-manager.php';
		$license_manager = new Your_Plugin_License_Manager();
		$license_manager->validate_license( $license_key );
	}
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'your_plugin_deactivate' );
function your_plugin_deactivate() {
	// Optionally deactivate license on deactivation
	$license_key = get_option( 'your_plugin_license_key', '' );
	if ( ! empty( $license_key ) ) {
		require_once YOUR_PLUGIN_DIR . 'includes/class-license-manager.php';
		$license_manager = new Your_Plugin_License_Manager();
		$license_manager->deactivate_license( $license_key );
	}
}
```

#### Step 5: Configure License Server Settings

In `class-license-manager.php` and `class-update-checker.php`, update the constructor:

```php
public function __construct() {
	// Set your license server URL (where WP Licensing is installed)
	$this->license_server_url = get_option( 'your_plugin_server_url', 'https://your-license-server.com' );
	
	// Set your Product ID (from WP Licensing admin → Products)
	$this->product_id = (int) get_option( 'your_plugin_id', 1 );
}
```

In `class-update-checker.php`, also set:

```php
public function __construct() {
	$this->license_server_url = get_option( 'your_plugin_server_url', 'https://your-license-server.com' );
	$this->product_id = (int) get_option( 'your_plugin_id', 1 );
	
	// Set your plugin version
	$this->version = YOUR_PLUGIN_VERSION; // or '1.0.0'
	
	// Set your plugin slug (must match the plugin folder name)
	$this->slug = 'your-plugin-slug';
}
```

#### Step 6: Customize Admin Settings Page (Optional)

The `class-admin.php` file creates a settings page where customers can enter their license key. You can customize:

- Page title
- Menu position
- Settings fields
- Success/error messages

#### Step 7: Test the Integration

1. **Install your plugin** on a test WordPress site
2. **Go to your plugin's settings page** (usually under Settings or your plugin menu)
3. **Enter the license server URL**: `https://your-license-server.com`
4. **Enter the Product ID**: (from WP Licensing admin)
5. **Enter a license key**: (from WP Licensing admin → Licenses)
6. **Click Activate License**
7. **Check for updates**: Go to Plugins page and see if updates are available

### Complete Integration Example

Here's a complete example of how the integration works:

```php
// In your plugin's main functionality file

// Check if license is valid before allowing access
$license_key = get_option( 'your_plugin_license_key', '' );
$license_status = get_option( 'your_plugin_license_status', 'invalid' );

if ( $license_status !== 'valid' ) {
	// Show license activation notice
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p>';
		echo 'Please activate your license key in <a href="' . admin_url( 'options-general.php?page=your-plugin-settings' ) . '">Settings</a>.';
		echo '</p></div>';
	});
	
	// Optionally disable plugin features
	return;
}

// License is valid, proceed with plugin functionality
// ... your plugin code here ...
```

## 📡 API Reference

### Public Endpoints (No Authentication Required)

#### Validate License

**Endpoint:** `POST /wp-json/wp-licensing/v1/validate`

**Request Body:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": 1
}
```

**Response:**
```json
{
  "valid": true,
  "status": "active",
  "expires_at": "2024-12-31 23:59:59",
  "activations_left": 4,
  "message": "License activated successfully"
}
```

#### Deactivate License

**Endpoint:** `POST /wp-json/wp-licensing/v1/deactivate`

**Request Body:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "License deactivated successfully"
}
```

#### Check for Updates

**Endpoint:** `GET /wp-json/wp-licensing/v1/update`

**Query Parameters:**
- `license_key` (required): The license key
- `version` (required): Current plugin version
- `product_id` (required): Product ID

**Example:**
```
GET /wp-json/wp-licensing/v1/update?license_key=XXXX&version=1.0.0&product_id=1
```

**Response:**
```json
{
  "update_available": true,
  "version": "1.0.1",
  "download_url": "https://example.com/wp-content/uploads/wp-licensing/product-slug-1.0.1.zip",
  "changelog": "Fixed bugs and improved performance",
  "description": "New version with improvements"
}
```

### Admin Endpoints (Requires `manage_options` capability)

All admin endpoints require authentication via WordPress nonce in the `X-WP-Nonce` header.

#### Licenses

- `GET /wp-json/wp-licensing/v1/licenses` - List all licenses
- `POST /wp-json/wp-licensing/v1/licenses` - Create new license
- `PUT /wp-json/wp-licensing/v1/licenses/{id}` - Update license
- `DELETE /wp-json/wp-licensing/v1/licenses/{id}` - Delete license

#### Products

- `GET /wp-json/wp-licensing/v1/products` - List all products
- `POST /wp-json/wp-licensing/v1/products` - Create new product
- `POST /wp-json/wp-licensing/v1/products/{id}` - Update product (with file upload)
- `DELETE /wp-json/wp-licensing/v1/products/{id}` - Delete product

#### Statistics

- `GET /wp-json/wp-licensing/v1/stats` - Get dashboard statistics

#### Settings

- `GET /wp-json/wp-licensing/v1/settings` - Get settings
- `POST /wp-json/wp-licensing/v1/settings` - Update settings

## 🔒 Security Features

- **Rate Limiting**: Prevents API abuse (60 requests per minute by default)
- **Nonce Validation**: WordPress nonce verification for admin endpoints
- **SQL Injection Protection**: All queries use `$wpdb->prepare()`
- **Input Sanitization**: All user inputs are sanitized
- **IP Tracking**: Tracks IP addresses for activation monitoring
- **License Key Encryption**: License keys are stored securely
- **Domain Validation**: Validates site URLs to prevent unauthorized use

## 📊 License States

- `active` - License is active and can be used
- `inactive` - License is inactive
- `expired` - License has expired
- `blocked` - License has been blocked by admin

## 🗂️ Project Structure

```
wp-licensing/
├── wp-licensing.php          # Main plugin file
├── src/
│   ├── Core/                 # Core classes (Plugin, Database, Autoloader)
│   ├── Models/               # Data models (License, Activation, Product)
│   ├── API/                  # REST API controllers
│   │   ├── Routes.php        # Route registration
│   │   ├── ManagementController.php  # Admin endpoints
│   │   └── LicenseController.php     # Public license endpoints
│   └── Helpers/              # Helper classes (Security, RateLimiter)
├── admin-app/                # React admin UI
│   ├── App.jsx              # Main React component
│   ├── components/          # React components
│   │   ├── Dashboard.jsx
│   │   ├── Licenses.jsx
│   │   ├── Products.jsx
│   │   ├── Settings.jsx
│   │   └── ApiPlugins.jsx
│   └── styles.css           # Admin styles
├── build/                    # Built assets (generated)
├── package.json              # Node.js dependencies
└── README.md                 # This file
```

## 🚀 Development Setup

For development with hot-reload:

```bash
npm run dev
```

This will start Vite in development mode with hot module replacement.

## 📝 Database Tables

The plugin creates the following database tables:

- `wp_wplic_licenses` - License keys and metadata
- `wp_wplic_activations` - License activations per site
- `wp_wplic_products` - Product information
- `wp_wplic_api_logs` - API request logs (optional)

## 🎓 Common Integration Patterns

### Pattern 1: Feature Gating

```php
// Only allow premium features if license is valid
if ( get_option( 'your_plugin_license_status' ) === 'valid' ) {
	// Enable premium features
	add_action( 'init', 'your_premium_feature' );
}
```

### Pattern 2: Update Notifications

```php
// Show update notification in admin
add_action( 'admin_notices', function() {
	$update_available = get_transient( 'your_plugin_update_available' );
	if ( $update_available ) {
		echo '<div class="notice notice-info"><p>';
		echo 'A new version is available! <a href="' . admin_url( 'plugins.php' ) . '">Update now</a>.';
		echo '</p></div>';
	}
});
```

### Pattern 3: License Expiry Warnings

```php
// Warn users when license is about to expire
add_action( 'admin_notices', function() {
	$expires_at = get_option( 'your_plugin_license_expires_at' );
	if ( $expires_at ) {
		$days_left = ( strtotime( $expires_at ) - time() ) / DAY_IN_SECONDS;
		if ( $days_left > 0 && $days_left <= 30 ) {
			echo '<div class="notice notice-warning"><p>';
			echo 'Your license expires in ' . round( $days_left ) . ' days. Please renew.';
			echo '</p></div>';
		}
	}
});
```

## 🐛 Troubleshooting

### License validation fails

1. Check that the license server URL is correct
2. Verify the Product ID matches in both server and client
3. Ensure the license key is correct
4. Check server logs for errors

### Updates not showing

1. Verify the plugin slug matches exactly
2. Check that version numbers are correct
3. Ensure license is active
4. Clear WordPress transients: `delete_transient( 'your_plugin_update_check' )`

### File upload fails

1. Check PHP upload limits in Settings
2. Verify file permissions on uploads directory
3. Ensure ZIP file is valid
4. Check server error logs

## 📞 Support

For support, documentation, and updates:

- **Website**: https://wpcorex.com
- **Plugin URI**: https://wpcorex.com/products/wp-licensing
- **Author**: Bilal Mahmood

## 📄 License

GPL-2.0+

## 🙏 Credits

Developed by Bilal Mahmood for the WordPress community.

---

## 📥 Download Integration Files

To integrate WP Licensing into your plugin, download the integration files:

**🔗 [Download Integration Files](hhttps://github.com/sahibbilal/wp-licensing-product)**

The integration files include:
- ✅ `class-license-manager.php` - License validation and management
- ✅ `class-update-checker.php` - Automatic update checking
- ✅ `class-admin.php` - Admin settings page
- ✅ Complete documentation and examples

**Need help?** Check out the example plugin in `wp-licensed-product/` for a complete working implementation, or download the integration files from the repository above.
