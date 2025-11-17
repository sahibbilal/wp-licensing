import React, { useState, useEffect } from 'react';

const Products = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    version: '1.0.0',
    download_url: '',
    changelog: '',
    description: '',
  });

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

  const handleCreateProduct = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}products`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpLicensing.nonce,
          },
          body: JSON.stringify(formData),
        }
      );

      if (response.ok) {
        setShowModal(false);
        setFormData({
          name: '',
          slug: '',
          version: '1.0.0',
          download_url: '',
          changelog: '',
          description: '',
        });
        fetchProducts();
      } else {
        const error = await response.json();
        alert('Error: ' + (error.error || 'Failed to create product'));
      }
    } catch (error) {
      console.error('Error creating product:', error);
      alert('Error creating product');
    }
  };

  return (
    <div className="wp-licensing-products">
      <div className="products-header">
        <h2>Product Management</h2>
        <button className="button button-primary" onClick={() => setShowModal(true)}>
          Create Product
        </button>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>Create New Product</h3>
            <form onSubmit={handleCreateProduct}>
              <div className="form-group">
                <label>Product Name</label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label>Slug</label>
                <input
                  type="text"
                  value={formData.slug}
                  onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                  placeholder="auto-generated from name"
                />
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
                <label>Download URL</label>
                <input
                  type="url"
                  value={formData.download_url}
                  onChange={(e) => setFormData({ ...formData, download_url: e.target.value })}
                />
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
                <button type="submit" className="button button-primary">Create</button>
                <button type="button" className="button" onClick={() => setShowModal(false)}>
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
            </tr>
          </thead>
          <tbody>
            {products.length === 0 ? (
              <tr>
                <td colSpan="6">No products found. Create your first product!</td>
              </tr>
            ) : (
              products.map((product) => (
                <tr key={product.id}>
                  <td>{product.id}</td>
                  <td><strong>{product.name}</strong></td>
                  <td><code>{product.slug}</code></td>
                  <td>{product.version}</td>
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

