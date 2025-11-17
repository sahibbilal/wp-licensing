# WP Licensing - WordPress SaaS Licensing & Auto Update System

A professional, commercial-grade WordPress plugin for managing software licenses and providing automatic updates for plugins and themes.

## Features

- ✅ **License Management**: Create, activate, deactivate, and manage license keys
- ✅ **License Validation API**: RESTful API for validating licenses on customer sites
- ✅ **Auto Update System**: Automatic plugin/theme updates via API
- ✅ **Activation Tracking**: Track license activations per domain/IP
- ✅ **Admin Dashboard**: Modern React-based admin interface
- ✅ **Security**: Rate limiting, nonce validation, secure queries
- ✅ **Analytics**: Dashboard with license usage statistics

## Requirements

- WordPress 5.8+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 16+ (for building admin UI)

## Installation

1. **Clone or download the plugin** to your WordPress `wp-content/plugins/` directory:
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

4. **Activate the plugin** from WordPress admin panel.

## Development Setup

For development with hot-reload:

```bash
npm run dev
```

This will start Vite in development mode with hot module replacement.

## Database Tables

The plugin creates the following database tables:

- `wp_wplic_licenses` - License keys and metadata
- `wp_wplic_activations` - License activations per site
- `wp_wplic_products` - Product information
- `wp_wplic_api_logs` - API request logs

## API Endpoints

### Public Endpoints (No Authentication Required)

#### Validate License
```
POST /wp-json/wp-licensing/v1/validate
Body: {
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": 1
}
```

#### Deactivate License
```
POST /wp-json/wp-licensing/v1/deactivate
Body: {
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com"
}
```

#### Check for Updates
```
GET /wp-json/wp-licensing/v1/update?license_key=XXXX&version=1.0.0&product_id=1
```

### Admin Endpoints (Requires `manage_options` capability)

- `GET /wp-json/wp-licensing/v1/licenses` - List licenses
- `POST /wp-json/wp-licensing/v1/licenses` - Create license
- `PUT /wp-json/wp-licensing/v1/licenses/{id}` - Update license
- `DELETE /wp-json/wp-licensing/v1/licenses/{id}` - Delete license
- `GET /wp-json/wp-licensing/v1/products` - List products
- `POST /wp-json/wp-licensing/v1/products` - Create product
- `GET /wp-json/wp-licensing/v1/stats` - Get dashboard statistics

## Client Integration

See `examples/client-updater.php` for a complete example of how to integrate the licensing system into your commercial WordPress plugin or theme.

### Basic Usage

```php
$updater = new Client_Updater(
    'https://your-license-server.com',  // License server URL
    1,                                  // Product ID
    '1.0.0',                            // Current version
    'your-plugin-slug'                  // Plugin/Theme slug
);

// Validate license
$result = $updater->validate_license( 'YOUR_LICENSE_KEY' );
if ( $result['valid'] ) {
    // License is valid
}
```

## Security Features

- **Rate Limiting**: Prevents API abuse (60 requests per minute by default)
- **Nonce Validation**: WordPress nonce verification for admin endpoints
- **SQL Injection Protection**: All queries use `$wpdb->prepare()`
- **Input Sanitization**: All user inputs are sanitized
- **IP Tracking**: Tracks IP addresses for activation monitoring

## License States

- `active` - License is active and can be used
- `inactive` - License is inactive
- `expired` - License has expired
- `blocked` - License has been blocked

## Project Structure

```
wp-licensing/
├── wp-licensing.php          # Main plugin file
├── src/
│   ├── Core/                 # Core classes (Plugin, Database, etc.)
│   ├── Models/               # Data models (License, Activation, Product)
│   ├── API/                  # REST API controllers
│   ├── Controllers/          # Admin controllers
│   └── Helpers/              # Helper classes (Security, RateLimiter)
├── admin-app/                # React admin UI
│   ├── components/           # React components
│   └── styles.css            # Admin styles
├── build/                    # Built assets (generated)
├── examples/                 # Example client code
└── package.json              # Node.js dependencies
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL-2.0+

## Support

For support, please open an issue on the GitHub repository.
