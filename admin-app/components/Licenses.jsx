import React, { useState, useEffect } from 'react';

const Licenses = () => {
  const [licenses, setLicenses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [showModal, setShowModal] = useState(false);
  const [products, setProducts] = useState([]);
  const [formData, setFormData] = useState({
    product_id: '',
    customer_email: '',
    customer_name: '',
    activation_limit: 1,
    status: 'active',
    expires_at: '',
  });

  useEffect(() => {
    fetchLicenses();
    fetchProducts();
  }, [page]);

  const fetchLicenses = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}licenses?page=${page}&per_page=20`,
        {
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
          },
        }
      );
      const data = await response.json();
      setLicenses(data.licenses || []);
      setTotal(data.total || 0);
    } catch (error) {
      console.error('Error fetching licenses:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchProducts = async () => {
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
    }
  };

  const handleCreateLicense = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}licenses`,
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
          product_id: '',
          customer_email: '',
          customer_name: '',
          activation_limit: 1,
          status: 'active',
          expires_at: '',
        });
        fetchLicenses();
      } else {
        const error = await response.json();
        alert('Error: ' + (error.error || 'Failed to create license'));
      }
    } catch (error) {
      console.error('Error creating license:', error);
      alert('Error creating license');
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this license?')) {
      return;
    }

    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}licenses/${id}`,
        {
          method: 'DELETE',
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
          },
        }
      );

      if (response.ok) {
        fetchLicenses();
      }
    } catch (error) {
      console.error('Error deleting license:', error);
    }
  };

  return (
    <div className="wp-licensing-licenses">
      <div className="licenses-header">
        <h2>License Management</h2>
        <button className="button button-primary" onClick={() => setShowModal(true)}>
          Create License
        </button>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>Create New License</h3>
            <form onSubmit={handleCreateLicense}>
              <div className="form-group">
                <label>Product</label>
                <select
                  value={formData.product_id}
                  onChange={(e) => setFormData({ ...formData, product_id: e.target.value })}
                  required
                >
                  <option value="">Select Product</option>
                  {products.map((product) => (
                    <option key={product.id} value={product.id}>
                      {product.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Customer Email</label>
                <input
                  type="email"
                  value={formData.customer_email}
                  onChange={(e) => setFormData({ ...formData, customer_email: e.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label>Customer Name</label>
                <input
                  type="text"
                  value={formData.customer_name}
                  onChange={(e) => setFormData({ ...formData, customer_name: e.target.value })}
                />
              </div>
              <div className="form-group">
                <label>Activation Limit</label>
                <input
                  type="number"
                  min="1"
                  value={formData.activation_limit}
                  onChange={(e) => setFormData({ ...formData, activation_limit: parseInt(e.target.value) })}
                  required
                />
              </div>
              <div className="form-group">
                <label>Status</label>
                <select
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="expired">Expired</option>
                  <option value="blocked">Blocked</option>
                </select>
              </div>
              <div className="form-group">
                <label>Expires At (optional)</label>
                <input
                  type="datetime-local"
                  value={formData.expires_at}
                  onChange={(e) => setFormData({ ...formData, expires_at: e.target.value })}
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
        <div className="loading">Loading licenses...</div>
      ) : (
        <>
          <table className="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th>License Key</th>
                <th>Product</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Activations</th>
                <th>Expires</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {licenses.length === 0 ? (
                <tr>
                  <td colSpan="7">No licenses found.</td>
                </tr>
              ) : (
                licenses.map((license) => (
                  <tr key={license.id}>
                    <td>
                      <code>{license.license_key}</code>
                    </td>
                    <td>{license.product_id}</td>
                    <td>
                      {license.customer_name || license.customer_email}
                      <br />
                      <small>{license.customer_email}</small>
                    </td>
                    <td>
                      <span className={`status-badge status-${license.status}`}>
                        {license.status}
                      </span>
                    </td>
                    <td>
                      {license.activations} / {license.activation_limit}
                    </td>
                    <td>{license.expires_at || 'Never'}</td>
                    <td>
                      <button
                        className="button button-small"
                        onClick={() => handleDelete(license.id)}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>

          {total > 20 && (
            <div className="pagination">
              <button
                className="button"
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
              >
                Previous
              </button>
              <span>Page {page} of {Math.ceil(total / 20)}</span>
              <button
                className="button"
                disabled={page >= Math.ceil(total / 20)}
                onClick={() => setPage(page + 1)}
              >
                Next
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default Licenses;

