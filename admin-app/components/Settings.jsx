import React, { useState, useEffect } from 'react';

const Settings = () => {
  const [settings, setSettings] = useState({
    max_upload_size: 50, // MB
    license_expiry_days: 365,
    max_activations: 5,
    enable_auto_updates: true,
    update_check_interval: 12, // hours
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}settings`,
        {
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
          },
        }
      );
      if (response.ok) {
        const data = await response.json();
        if (data.settings) {
          setSettings(data.settings);
        }
      }
    } catch (error) {
      console.error('Error fetching settings:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      const response = await fetch(
        `${wpLicensing.apiUrl}settings`,
        {
          method: 'POST',
          headers: {
            'X-WP-Nonce': wpLicensing.nonce,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(settings),
        }
      );

      if (response.ok) {
        setMessage({ type: 'success', text: 'Settings saved successfully!' });
        setTimeout(() => setMessage(null), 3000);
      } else {
        const error = await response.json();
        setMessage({ type: 'error', text: error.error || 'Failed to save settings' });
      }
    } catch (error) {
      setMessage({ type: 'error', text: 'Error saving settings: ' + error.message });
    } finally {
      setSaving(false);
    }
  };

  const handleChange = (key, value) => {
    setSettings(prev => ({
      ...prev,
      [key]: value,
    }));
  };

  if (loading) {
    return <div className="loading">Loading settings...</div>;
  }

  return (
    <div className="wp-licensing-settings">
      <h2>Plugin Settings</h2>
      <p className="description">
        Configure important settings for the WP Licensing plugin.
      </p>

      {message && (
        <div className={`notice notice-${message.type}`} style={{ 
          padding: '10px', 
          marginBottom: '20px',
          backgroundColor: message.type === 'success' ? '#d4edda' : '#f8d7da',
          border: `1px solid ${message.type === 'success' ? '#c3e6cb' : '#f5c6cb'}`,
          borderRadius: '4px',
          color: message.type === 'success' ? '#155724' : '#721c24'
        }}>
          {message.text}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="settings-section">
          <h3>File Upload Settings</h3>
          
          <div className="form-group">
            <label htmlFor="max_upload_size">
              Maximum Upload Size (MB)
              <span className="required">*</span>
            </label>
            <input
              type="number"
              id="max_upload_size"
              min="1"
              max="1000"
              value={settings.max_upload_size}
              onChange={(e) => handleChange('max_upload_size', parseInt(e.target.value) || 50)}
              required
            />
            <p className="description">
              Maximum file size allowed for plugin ZIP uploads (in megabytes). 
              Default: 50 MB. Note: This cannot exceed your server's PHP upload limit.
            </p>
          </div>
        </div>

        <div className="settings-section">
          <h3>License Settings</h3>
          
          <div className="form-group">
            <label htmlFor="license_expiry_days">
              License Expiry Days
              <span className="required">*</span>
            </label>
            <input
              type="number"
              id="license_expiry_days"
              min="1"
              max="3650"
              value={settings.license_expiry_days}
              onChange={(e) => handleChange('license_expiry_days', parseInt(e.target.value) || 365)}
              required
            />
            <p className="description">
              Number of days before a license expires. Default: 365 days (1 year).
            </p>
          </div>

          <div className="form-group">
            <label htmlFor="max_activations">
              Maximum Activations per License
              <span className="required">*</span>
            </label>
            <input
              type="number"
              id="max_activations"
              min="1"
              max="100"
              value={settings.max_activations}
              onChange={(e) => handleChange('max_activations', parseInt(e.target.value) || 5)}
              required
            />
            <p className="description">
              Maximum number of sites where a single license can be activated. Default: 5.
            </p>
          </div>
        </div>

        <div className="settings-section">
          <h3>Update Settings</h3>
          
          <div className="form-group">
            <label htmlFor="enable_auto_updates">
              <input
                type="checkbox"
                id="enable_auto_updates"
                checked={settings.enable_auto_updates}
                onChange={(e) => handleChange('enable_auto_updates', e.target.checked)}
              />
              Enable Automatic Update Checks
            </label>
            <p className="description">
              When enabled, the plugin will automatically check for updates at the specified interval.
            </p>
          </div>

          <div className="form-group">
            <label htmlFor="update_check_interval">
              Update Check Interval (hours)
              <span className="required">*</span>
            </label>
            <input
              type="number"
              id="update_check_interval"
              min="1"
              max="168"
              value={settings.update_check_interval}
              onChange={(e) => handleChange('update_check_interval', parseInt(e.target.value) || 12)}
              required
              disabled={!settings.enable_auto_updates}
            />
            <p className="description">
              How often to check for plugin updates (in hours). Default: 12 hours.
            </p>
          </div>
        </div>

        <div className="form-actions">
          <button 
            type="submit" 
            className="button button-primary"
            disabled={saving}
          >
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
          <button 
            type="button" 
            className="button"
            onClick={fetchSettings}
            disabled={saving}
          >
            Reset
          </button>
        </div>
      </form>
    </div>
  );
};

export default Settings;

