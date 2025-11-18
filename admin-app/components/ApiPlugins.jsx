import React, { useState, useEffect } from 'react';

const ApiPlugins = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [copiedEndpoint, setCopiedEndpoint] = useState(null);
  const baseUrl = wpLicensing.siteUrl || window.location.origin;
  const apiBaseUrl = `${baseUrl}/wp-json/wp-licensing/v1`;

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}products`,
        {
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
          },
        }
      );
      const data = await response.json();
      setProducts(data.products || []);
    } catch (error) {
      console.error('Error fetching products:', error);
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = (text, endpoint) => {
    navigator.clipboard.writeText(text).then(() => {
      setCopiedEndpoint(endpoint);
      setTimeout(() => setCopiedEndpoint(null), 2000);
    });
  };

  const apiEndpoints = [
    {
      name: 'Validate License',
      method: 'POST',
      endpoint: '/validate',
      description: 'Validates a license key and activates it for a site.',
      params: [
        { name: 'license_key', type: 'string', required: true, description: 'The license key to validate' },
        { name: 'site_url', type: 'string', required: true, description: 'The site URL where the license is being activated' },
        { name: 'product_id', type: 'integer', required: true, description: 'The product ID this license belongs to' },
      ],
      example: {
        url: `${apiBaseUrl}/validate`,
        body: {
          license_key: 'XXXX-XXXX-XXXX-XXXX',
          site_url: 'https://example.com',
          product_id: 1,
        },
      },
      response: {
        success: {
          valid: true,
          message: 'License is valid.',
          expires_at: '2024-12-31 23:59:59',
          status: 'active',
        },
        error: {
          valid: false,
          message: 'License key not found.',
        },
      },
    },
    {
      name: 'Deactivate License',
      method: 'POST',
      endpoint: '/deactivate',
      description: 'Deactivates a license for a specific site.',
      params: [
        { name: 'license_key', type: 'string', required: true, description: 'The license key to deactivate' },
        { name: 'site_url', type: 'string', required: true, description: 'The site URL to deactivate the license for' },
      ],
      example: {
        url: `${apiBaseUrl}/deactivate`,
        body: {
          license_key: 'XXXX-XXXX-XXXX-XXXX',
          site_url: 'https://example.com',
        },
      },
      response: {
        success: {
          success: true,
          message: 'License deactivated successfully.',
        },
        error: {
          success: false,
          message: 'License not found.',
        },
      },
    },
    {
      name: 'Check for Updates',
      method: 'GET',
      endpoint: '/update',
      description: 'Checks if a new version is available for a product.',
      params: [
        { name: 'license_key', type: 'string', required: true, description: 'The license key' },
        { name: 'version', type: 'string', required: true, description: 'Current version of the plugin/theme' },
        { name: 'product_id', type: 'integer', required: true, description: 'The product ID to check updates for' },
      ],
      example: {
        url: `${apiBaseUrl}/update?license_key=XXXX-XXXX-XXXX-XXXX&version=1.0.0&product_id=1`,
        body: null,
      },
      response: {
        success: {
          version: '1.1.0',
          update: true,
          download_url: 'https://example.com/downloads/product.zip',
          changelog: 'Version 1.1.0 changelog...',
        },
        noUpdate: {
          version: '1.0.0',
          update: false,
          message: 'You are running the latest version.',
        },
      },
    },
  ];

  const adminEndpoints = [
    {
      name: 'Get Licenses',
      method: 'GET',
      endpoint: '/licenses',
      description: 'Retrieves a list of all licenses (requires admin authentication).',
      params: [
        { name: 'page', type: 'integer', required: false, description: 'Page number (default: 1)' },
        { name: 'per_page', type: 'integer', required: false, description: 'Items per page (default: 20, max: 100)' },
        { name: 'status', type: 'string', required: false, description: 'Filter by status (active, inactive, expired, blocked)' },
        { name: 'product_id', type: 'integer', required: false, description: 'Filter by product ID' },
        { name: 'search', type: 'string', required: false, description: 'Search in license key, email, or name' },
      ],
    },
    {
      name: 'Create License',
      method: 'POST',
      endpoint: '/licenses',
      description: 'Creates a new license (requires admin authentication).',
      params: [
        { name: 'product_id', type: 'integer', required: true, description: 'The product ID' },
        { name: 'customer_email', type: 'string', required: true, description: 'Customer email address' },
        { name: 'customer_name', type: 'string', required: false, description: 'Customer name' },
        { name: 'activation_limit', type: 'integer', required: false, description: 'Maximum number of activations (default: 1)' },
        { name: 'expires_at', type: 'string', required: false, description: 'Expiration date (YYYY-MM-DD HH:MM:SS)' },
      ],
    },
    {
      name: 'Get Products',
      method: 'GET',
      endpoint: '/products',
      description: 'Retrieves a list of all products (requires admin authentication).',
    },
    {
      name: 'Get Stats',
      method: 'GET',
      endpoint: '/stats',
      description: 'Retrieves dashboard statistics (requires admin authentication).',
    },
  ];

  return (
    <div className="wp-licensing-api-plugins">
      <div className="api-plugins-header">
        <h2>API Documentation & Plugins</h2>
        <p>Use these APIs to integrate license validation and automatic updates into your WordPress plugins and themes.</p>
      </div>

      <div className="api-sections">
        {/* Public API Endpoints */}
        <section className="api-section">
          <h3>Public API Endpoints</h3>
          <p className="section-description">
            These endpoints are publicly accessible and do not require authentication. They are used by client plugins/themes to validate licenses and check for updates.
          </p>

          {apiEndpoints.map((endpoint, index) => (
            <div key={index} className="endpoint-card">
              <div className="endpoint-header">
                <div className="endpoint-title">
                  <span className={`method-badge method-${endpoint.method.toLowerCase()}`}>
                    {endpoint.method}
                  </span>
                  <code className="endpoint-path">{endpoint.endpoint}</code>
                  <h4>{endpoint.name}</h4>
                </div>
                <button
                  className="button button-small copy-button"
                  onClick={() => copyToClipboard(endpoint.example.url, `endpoint-${index}`)}
                >
                  {copiedEndpoint === `endpoint-${index}` ? '✓ Copied' : 'Copy URL'}
                </button>
              </div>
              
              <p className="endpoint-description">{endpoint.description}</p>

              <div className="endpoint-details">
                <div className="detail-section">
                  <h5>Parameters</h5>
                  <table className="params-table">
                    <thead>
                      <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                      </tr>
                    </thead>
                    <tbody>
                      {endpoint.params.map((param, pIndex) => (
                        <tr key={pIndex}>
                          <td><code>{param.name}</code></td>
                          <td>{param.type}</td>
                          <td>{param.required ? 'Yes' : 'No'}</td>
                          <td>{param.description}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <div className="detail-section">
                  <h5>Example Request</h5>
                  <div className="code-block">
                    <div className="code-header">
                      <span>cURL</span>
                      <button
                        className="button-link copy-code"
                        onClick={() => {
                          const curlCommand = endpoint.method === 'GET'
                            ? `curl "${endpoint.example.url}"`
                            : `curl -X ${endpoint.method} "${endpoint.example.url}" \\\n  -H "Content-Type: application/json" \\\n  -d '${JSON.stringify(endpoint.example.body, null, 2)}'`;
                          copyToClipboard(curlCommand, `curl-${index}`);
                        }}
                      >
                        {copiedEndpoint === `curl-${index}` ? '✓ Copied' : 'Copy'}
                      </button>
                    </div>
                    <pre>
                      <code>
                        {endpoint.method === 'GET' ? (
                          `curl "${endpoint.example.url}"`
                        ) : (
                          `curl -X ${endpoint.method} "${endpoint.example.url}" \\\n  -H "Content-Type: application/json" \\\n  -d '${JSON.stringify(endpoint.example.body, null, 2)}'`
                        )}
                      </code>
                    </pre>
                  </div>
                </div>

                <div className="detail-section">
                  <h5>Example Response</h5>
                  <div className="code-block">
                    <div className="code-header">
                      <span>Success Response</span>
                    </div>
                    <pre>
                      <code>{JSON.stringify(endpoint.response.success, null, 2)}</code>
                    </pre>
                  </div>
                  {endpoint.response.error && (
                    <div className="code-block">
                      <div className="code-header">
                        <span>Error Response</span>
                      </div>
                      <pre>
                        <code>{JSON.stringify(endpoint.response.error, null, 2)}</code>
                      </pre>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </section>

        {/* Admin API Endpoints */}
        <section className="api-section">
          <h3>Admin API Endpoints</h3>
          <p className="section-description">
            These endpoints require admin authentication (manage_options capability). Use the WordPress REST API nonce for authentication.
          </p>

          {adminEndpoints.map((endpoint, index) => (
            <div key={index} className="endpoint-card">
              <div className="endpoint-header">
                <div className="endpoint-title">
                  <span className={`method-badge method-${endpoint.method.toLowerCase()}`}>
                    {endpoint.method}
                  </span>
                  <code className="endpoint-path">{endpoint.endpoint}</code>
                  <h4>{endpoint.name}</h4>
                </div>
              </div>
              
              <p className="endpoint-description">{endpoint.description}</p>

              {endpoint.params && endpoint.params.length > 0 && (
                <div className="endpoint-details">
                  <div className="detail-section">
                    <h5>Parameters</h5>
                    <table className="params-table">
                      <thead>
                        <tr>
                          <th>Parameter</th>
                          <th>Type</th>
                          <th>Required</th>
                          <th>Description</th>
                        </tr>
                      </thead>
                      <tbody>
                        {endpoint.params.map((param, pIndex) => (
                          <tr key={pIndex}>
                            <td><code>{param.name}</code></td>
                            <td>{param.type}</td>
                            <td>{param.required ? 'Yes' : 'No'}</td>
                            <td>{param.description}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          ))}
        </section>

        {/* Registered Products/Plugins */}
        <section className="api-section">
          <h3>Registered Products/Plugins</h3>
          <p className="section-description">
            These are the products currently registered in your licensing system. Use the Product ID when integrating with the API.
          </p>

          {loading ? (
            <div className="loading">Loading products...</div>
          ) : (
            <div className="products-list">
              {products.length === 0 ? (
                <div className="no-products">
                  <p>No products registered yet. Create a product in the Products tab to get started.</p>
                </div>
              ) : (
                <table className="wp-list-table widefat fixed striped">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Slug</th>
                      <th>Version</th>
                      <th>API Usage</th>
                    </tr>
                  </thead>
                  <tbody>
                    {products.map((product) => (
                      <tr key={product.id}>
                        <td><strong>{product.id}</strong></td>
                        <td>{product.name}</td>
                        <td><code>{product.slug}</code></td>
                        <td>{product.version}</td>
                        <td>
                          <div className="api-usage-example">
                            <p><strong>Product ID:</strong> <code>{product.id}</code></p>
                            <p><strong>Update Check URL:</strong></p>
                            <code className="usage-code">
                              {apiBaseUrl}/update?license_key=YOUR_KEY&version=1.0.0&product_id={product.id}
                            </code>
                            <button
                              className="button button-small"
                              onClick={() => copyToClipboard(
                                `${apiBaseUrl}/update?license_key=YOUR_KEY&version=1.0.0&product_id=${product.id}`,
                                `product-${product.id}`
                              )}
                            >
                              {copiedEndpoint === `product-${product.id}` ? '✓ Copied' : 'Copy'}
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}
        </section>

        {/* Integration Guide */}
        <section className="api-section">
          <h3>Integration Guide</h3>
          <div className="integration-guide">
            <h4>Quick Start</h4>
            <ol>
              <li>Create a product in the <strong>Products</strong> tab</li>
              <li>Create licenses for that product in the <strong>Licenses</strong> tab</li>
              <li>Use the Product ID and API endpoints shown above in your client plugin/theme</li>
              <li>See the example plugin <code>wp-licensed-product</code> for a complete implementation</li>
            </ol>

            <h4>Client Integration Example</h4>
            <div className="code-block">
              <div className="code-header">
                <span>PHP - License Validation</span>
                <button
                  className="button-link copy-code"
                  onClick={() => {
                    const code = `$response = wp_remote_post(
  '${apiBaseUrl}/validate',
  array(
    'timeout' => 15,
    'body' => array(
      'license_key' => 'YOUR_LICENSE_KEY',
      'site_url' => home_url(),
      'product_id' => 1,
    ),
  )
);

$data = json_decode(wp_remote_retrieve_body($response), true);
if (isset($data['valid']) && $data['valid']) {
  // License is valid
}`;
                    copyToClipboard(code, 'php-validate');
                  }}
                >
                  {copiedEndpoint === 'php-validate' ? '✓ Copied' : 'Copy'}
                </button>
              </div>
              <pre>
                <code>{`$response = wp_remote_post(
  '${apiBaseUrl}/validate',
  array(
    'timeout' => 15,
    'body' => array(
      'license_key' => 'YOUR_LICENSE_KEY',
      'site_url' => home_url(),
      'product_id' => 1,
    ),
  )
);

$data = json_decode(wp_remote_retrieve_body($response), true);
if (isset($data['valid']) && $data['valid']) {
  // License is valid
}`}</code>
              </pre>
            </div>

            <div className="code-block">
              <div className="code-header">
                <span>PHP - Check for Updates</span>
                <button
                  className="button-link copy-code"
                  onClick={() => {
                    const code = `$url = add_query_arg(
  array(
    'license_key' => 'YOUR_LICENSE_KEY',
    'version' => '1.0.0',
    'product_id' => 1,
  ),
  '${apiBaseUrl}/update'
);

$response = wp_remote_get($url, array('timeout' => 15));
$data = json_decode(wp_remote_retrieve_body($response), true);

if (isset($data['update']) && $data['update']) {
  // New version available: $data['version']
  // Download URL: $data['download_url']
}`;
                    copyToClipboard(code, 'php-update');
                  }}
                >
                  {copiedEndpoint === 'php-update' ? '✓ Copied' : 'Copy'}
                </button>
              </div>
              <pre>
                <code>{`$url = add_query_arg(
  array(
    'license_key' => 'YOUR_LICENSE_KEY',
    'version' => '1.0.0',
    'product_id' => 1,
  ),
  '${apiBaseUrl}/update'
);

$response = wp_remote_get($url, array('timeout' => 15));
$data = json_decode(wp_remote_retrieve_body($response), true);

if (isset($data['update']) && $data['update']) {
  // New version available: $data['version']
  // Download URL: $data['download_url']
}`}</code>
              </pre>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
};

export default ApiPlugins;

