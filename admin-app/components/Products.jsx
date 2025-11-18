import React, { useState, useEffect } from 'react';

const Products = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    version: '1.0.0',
    download_url: '',
    changelog: '',
    description: '',
  });
  const [pluginFile, setPluginFile] = useState(null);
  const [slugManuallyEdited, setSlugManuallyEdited] = useState(false);

  useEffect(() => {
    fetchProducts();
  }, []);

  // Function to generate slug from name (similar to WordPress sanitize_title)
  const generateSlug = (name) => {
    return name
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '') // Remove special characters
      .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
      .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
  };

  // Auto-generate slug when name changes (if slug hasn't been manually edited)
  useEffect(() => {
    if (!slugManuallyEdited && formData.name) {
      const generatedSlug = generateSlug(formData.name);
      setFormData(prev => ({ ...prev, slug: generatedSlug }));
    }
  }, [formData.name, slugManuallyEdited]);

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

  const openEditModal = (product) => {
    setEditingProduct(product);
    setFormData({
      name: product.name || '',
      slug: product.slug || '',
      version: product.version || '1.0.0',
      download_url: product.download_url || '',
      changelog: product.changelog || '',
      description: product.description || '',
    });
    setSlugManuallyEdited(true);
    setPluginFile(null);
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setEditingProduct(null);
    setFormData({
      name: '',
      slug: '',
      version: '1.0.0',
      download_url: '',
      changelog: '',
      description: '',
    });
    setSlugManuallyEdited(false);
    setPluginFile(null);
    const fileInput = document.getElementById('plugin_file');
    if (fileInput) {
      fileInput.value = '';
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setUploading(true);
    
    try {
      // If a file is uploaded, use FormData; otherwise use JSON
      let body;
      let headers = {
        'X-WP-Nonce': wpLicensing.nonce,
      };

      if (pluginFile) {
        // Use FormData for file upload
        body = new FormData();
        // Only include name and slug when creating (not editing)
        if (!editingProduct) {
          body.append('name', formData.name);
          body.append('slug', formData.slug);
        }
        body.append('version', formData.version);
        body.append('changelog', formData.changelog);
        body.append('description', formData.description);
        body.append('plugin_file', pluginFile);
        // If download_url is also provided, use it as fallback
        if (formData.download_url) {
          body.append('download_url', formData.download_url);
        }
      } else {
        // Use JSON for regular submission
        headers['Content-Type'] = 'application/json';
        // Only include name and slug when creating (not editing)
        const submitData = editingProduct 
          ? { version: formData.version, changelog: formData.changelog, description: formData.description, download_url: formData.download_url }
          : formData;
        body = JSON.stringify(submitData);
      }

      // Use POST for all updates (works with both JSON and file uploads)
      // PHP $_FILES only works with POST, so we use POST for consistency
      let url, method;
      if (editingProduct) {
        url = `${wpLicensing.apiUrl}products/${editingProduct.id}`;
        method = 'POST'; // Always use POST for updates (supports both JSON and file uploads)
      } else {
        url = `${wpLicensing.apiUrl}products`;
        method = 'POST';
      }

      const response = await fetch(url, {
        method: method,
        headers: headers,
        body: body,
      });

      if (response.ok) {
        closeModal();
        fetchProducts();
      } else {
        const error = await response.json();
        alert('Error: ' + (error.error || `Failed to ${editingProduct ? 'update' : 'create'} product`));
      }
    } catch (error) {
      console.error('Error creating product:', error);
      alert('Error creating product: ' + error.message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="wp-licensing-products">
      <div className="products-header">
        <h2>Product Management</h2>
        <button 
          className="button button-primary" 
          onClick={() => {
            setShowModal(true);
            setSlugManuallyEdited(false);
          }}
        >
          Create Product
        </button>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>{editingProduct ? 'Edit Product' : 'Create New Product'}</h3>
            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label>Product Name</label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                  disabled={!!editingProduct}
                  style={editingProduct ? { backgroundColor: '#f0f0f1', cursor: 'not-allowed' } : {}}
                />
                {editingProduct && (
                  <p className="description" style={{ color: '#646970', fontSize: '12px', marginTop: '5px' }}>
                    Product name cannot be changed.
                  </p>
                )}
              </div>
              <div className="form-group">
                <label>Slug</label>
                <input
                  type="text"
                  value={formData.slug}
                  onChange={(e) => {
                    setFormData({ ...formData, slug: e.target.value });
                    setSlugManuallyEdited(true);
                  }}
                  placeholder="auto-generated from name"
                  disabled={!!editingProduct}
                  style={editingProduct ? { backgroundColor: '#f0f0f1', cursor: 'not-allowed' } : {}}
                />
                <p className="description">
                  {editingProduct 
                    ? 'Product slug cannot be changed.'
                    : 'Auto-generated from product name. You can edit it if needed.'}
                </p>
              </div>
              <div className="form-group">
                <label>Version</label>
                <input
                  type="text"
                  value={formData.version}
                  onChange={(e) => setFormData({ ...formData, version: e.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label>Download URL or Upload Plugin ZIP</label>
                {editingProduct && formData.download_url && (
                  <p style={{ margin: '0 0 10px 0', fontSize: '12px', color: '#646970' }}>
                    Current: <a href={formData.download_url} target="_blank" rel="noopener noreferrer">{formData.download_url}</a>
                  </p>
                )}
                <div style={{ marginBottom: '10px' }}>
                  <input
                    type="url"
                    value={formData.download_url}
                    onChange={(e) => setFormData({ ...formData, download_url: e.target.value })}
                    placeholder="https://example.com/plugin.zip"
                    style={{ width: '100%', marginBottom: '10px' }}
                    disabled={!!pluginFile}
                  />
                  <p style={{ margin: '5px 0', fontSize: '12px', color: '#666' }}>
                    OR
                  </p>
                  <input
                    type="file"
                    id="plugin_file"
                    accept=".zip"
                    onChange={(e) => {
                      const file = e.target.files[0];
                      if (file) {
                        if (file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
                          alert('Please upload a ZIP file.');
                          e.target.value = '';
                          return;
                        }
                        setPluginFile(file);
                        // Clear download URL when file is selected
                        setFormData({ ...formData, download_url: '' });
                      } else {
                        setPluginFile(null);
                      }
                    }}
                    style={{ width: '100%' }}
                  />
                  {pluginFile && (
                    <p style={{ margin: '5px 0', fontSize: '12px', color: '#2271b1' }}>
                      Selected: {pluginFile.name} ({(pluginFile.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                  )}
                </div>
                <p className="description">
                  {editingProduct 
                    ? 'Upload a new ZIP file to replace the current one, or update the download URL. Leave empty to keep current file.'
                    : 'Enter a download URL or upload a plugin ZIP file. The uploaded file will be stored in your WordPress uploads directory.'}
                </p>
              </div>
              <div className="form-group">
                <label>Changelog</label>
                <textarea
                  value={formData.changelog}
                  onChange={(e) => setFormData({ ...formData, changelog: e.target.value })}
                  rows="5"
                />
              </div>
              <div className="form-group">
                <label>Description</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows="5"
                />
              </div>
              <div className="form-actions">
                <button 
                  type="submit" 
                  className="button button-primary"
                  disabled={uploading}
                >
                  {uploading ? (editingProduct ? 'Updating...' : 'Uploading...') : (editingProduct ? 'Update' : 'Create')}
                </button>
                <button 
                  type="button" 
                  className="button" 
                  onClick={closeModal}
                  disabled={uploading}
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {loading ? (
        <div className="loading">Loading products...</div>
      ) : (
        <table className="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Version</th>
              <th>Download URL</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {products.length === 0 ? (
              <tr>
                <td colSpan="7">No products found. Create your first product!</td>
              </tr>
            ) : (
              products.map((product) => (
                <tr key={product.id}>
                  <td>{product.id}</td>
                  <td><strong>{product.name}</strong></td>
                  <td><code>{product.slug}</code></td>
                  <td><strong>{product.version}</strong></td>
                  <td>
                    {product.download_url ? (
                      <a href={product.download_url} target="_blank" rel="noopener noreferrer">
                        Download
                      </a>
                    ) : (
                      'N/A'
                    )}
                  </td>
                  <td>{new Date(product.created_at).toLocaleDateString()}</td>
                  <td>
                    <button
                      className="button button-small"
                      onClick={() => openEditModal(product)}
                    >
                      Edit
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </div>
  );
};

export default Products;

