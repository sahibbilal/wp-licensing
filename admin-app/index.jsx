import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

// Error boundary for development
window.addEventListener('error', (event) => {
  console.error('WP Licensing Error:', event.error);
});

const container = document.getElementById('wp-licensing-admin-app');
if (container) {
  try {
    const root = createRoot(container);
    root.render(<App />);
  } catch (error) {
    console.error('Failed to render WP Licensing app:', error);
    container.innerHTML = `
      <div style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h3>Error Loading Admin Interface</h3>
        <p>There was an error loading the WP Licensing admin interface.</p>
        <p><strong>Error:</strong> ${error.message}</p>
        <p>Please check the browser console (F12) for more details.</p>
      </div>
    `;
  }
} else {
  console.error('WP Licensing: Container element not found');
}

