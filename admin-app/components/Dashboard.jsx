import React from 'react';
import Stats from './Stats';

const Dashboard = ({ stats }) => {
  return (
    <div className="wp-licensing-dashboard">
      <h2>Dashboard Overview</h2>
      <Stats stats={stats} />
    </div>
  );
};

export default Dashboard;

