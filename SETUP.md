# Setup Guide

## Quick Start

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Build Admin UI**
   ```bash
   npm run build
   ```

3. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "WP Licensing"

4. **Create Your First Product**
   - Go to Licensing → Products
   - Click "Create Product"
   - Fill in product details (name, version, download URL, etc.)

5. **Create Licenses**
   - Go to Licensing → Licenses
   - Click "Create License"
   - Select product, enter customer email, set activation limit

## Development Mode

For development with hot-reload:

```bash
npm run dev
```

This will watch for changes and rebuild automatically.

## API Testing

### Test License Validation

```bash
curl -X POST https://your-site.com/wp-json/wp-licensing/v1/validate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "YOUR_LICENSE_KEY",
    "site_url": "https://example.com",
    "product_id": 1
  }'
```

### Test Update Check

```bash
curl "https://your-site.com/wp-json/wp-licensing/v1/update?license_key=YOUR_KEY&version=1.0.0&product_id=1"
```

## Integration

See `examples/client-updater.php` for a complete example of integrating the licensing system into your commercial WordPress plugin or theme.

