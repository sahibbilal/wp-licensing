import React, { useState, useEffect } from 'react';
import Dashboard from './components/Dashboard';
import Licenses from './components/Licenses';
import Products from './components/Products';
import Stats from './components/Stats';
import ApiPlugins from './components/ApiPlugins';
import Settings from './components/Settings';

const App = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}stats`,
        {
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
          },
        }
      );
      const data = await response.json();
      setStats(data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="wp-licensing-admin">
      <div className="wp-licensing-header">
        <h2>WP Licensing System</h2>
        <nav className="wp-licensing-tabs">
          <button
            className={activeTab === 'dashboard' ? 'active' : ''}
            onClick={() => setActiveTab('dashboard')}
          >
            Dashboard
          </button>
          <button
            className={activeTab === 'licenses' ? 'active' : ''}
            onClick={() => setActiveTab('licenses')}
          >
            Licenses
          </button>
          <button
            className={activeTab === 'products' ? 'active' : ''}
            onClick={() => setActiveTab('products')}
          >
            Products
          </button>
          <button
            className={activeTab === 'api-plugins' ? 'active' : ''}
            onClick={() => setActiveTab('api-plugins')}
          >
            API & Plugins
          </button>
          <button
            className={activeTab === 'settings' ? 'active' : ''}
            onClick={() => setActiveTab('settings')}
          >
            Settings
          </button>
        </nav>
      </div>

      <div className="wp-licensing-content">
        {loading ? (
          <div className="wp-licensing-loading">Loading...</div>
        ) : (
          <>
            {activeTab === 'dashboard' && <Dashboard stats={stats} />}
            {activeTab === 'licenses' && <Licenses />}
            {activeTab === 'products' && <Products />}
            {activeTab === 'api-plugins' && <ApiPlugins />}
            {activeTab === 'settings' && <Settings />}
          </>
        )}
      </div>
    </div>
  );
};

export default App;

