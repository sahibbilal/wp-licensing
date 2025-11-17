import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

const container = document.getElementById('wp-licensing-admin-app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}

