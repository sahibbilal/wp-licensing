import React from 'react';

const Stats = ({ stats }) => {
  if (!stats) {
    return <div>Loading stats...</div>;
  }

  return (
    <div className="wp-licensing-stats">
      <div className="stats-grid">
        <div className="stat-card">
          <h3>Total Licenses</h3>
          <p className="stat-value">{stats.total_licenses || 0}</p>
        </div>
        <div className="stat-card">
          <h3>Active Licenses</h3>
          <p className="stat-value">{stats.active_licenses || 0}</p>
        </div>
        <div className="stat-card">
          <h3>Total Activations</h3>
          <p className="stat-value">{stats.total_activations || 0}</p>
        </div>
        <div className="stat-card">
          <h3>Products</h3>
          <p className="stat-value">{stats.total_products || 0}</p>
        </div>
      </div>
    </div>
  );
};

export default Stats;

