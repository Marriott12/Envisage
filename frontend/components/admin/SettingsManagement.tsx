import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://envisagezm.com/api';

interface Setting {
  key: string;
  value: string;
  group: string;
  type: string;
  description?: string;
  is_public: boolean;
}

export default function SettingsManagement() {
  const [settings, setSettings] = useState<Record<string, Setting[]>>({});
  const [loading, setLoading] = useState(true);
  const [activeGroup, setActiveGroup] = useState('general');
  const [editedSettings, setEditedSettings] = useState<Record<string, string>>({});

  const fetchSettings = async () => {
    try {
      const token = localStorage.getItem('envisage_auth_token');
      const response = await axios.get(`${API_URL}/admin/settings`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      setSettings(response.data.settings);
    } catch (error: any) {
      toast.error('Failed to load settings');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const handleSaveSettings = async () => {
    try {
      const token = localStorage.getItem('envisage_auth_token');
      const settingsArray = Object.entries(editedSettings).map(([key, value]) => ({
        key,
        value
      }));

      await axios.post(`${API_URL}/admin/settings/batch`, { settings: settingsArray }, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      toast.success('Settings updated successfully');
      setEditedSettings({});
      fetchSettings();
    } catch (error: any) {
      toast.error('Failed to update settings');
    }
  };

  const groups = Object.keys(settings);

  if (loading) {
    return <div className="flex justify-center py-8"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div></div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-900">System Settings</h2>
        {Object.keys(editedSettings).length > 0 && (
          <button
            onClick={handleSaveSettings}
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          >
            Save Changes
          </button>
        )}
      </div>

      <div className="flex gap-6">
        {/* Sidebar */}
        <div className="w-48 bg-white rounded-lg shadow p-4">
          <nav className="space-y-1">
            {groups.map((group) => (
              <button
                key={group}
                onClick={() => setActiveGroup(group)}
                className={`w-full text-left px-3 py-2 rounded transition ${
                  activeGroup === group
                    ? 'bg-primary-100 text-primary-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                {group.charAt(0).toUpperCase() + group.slice(1)}
              </button>
            ))}
          </nav>
        </div>

        {/* Settings Panel */}
        <div className="flex-1 bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4 capitalize">{activeGroup} Settings</h3>
          <div className="space-y-4">
            {settings[activeGroup]?.map((setting) => (
              <div key={setting.key} className="border-b pb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {setting.key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                </label>
                {setting.description && (
                  <p className="text-xs text-gray-500 mb-2">{setting.description}</p>
                )}
                
                {setting.type === 'boolean' ? (
                  <select
                    value={editedSettings[setting.key] ?? setting.value}
                    onChange={(e) => setEditedSettings({ ...editedSettings, [setting.key]: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                  >
                    <option value="true">Enabled</option>
                    <option value="false">Disabled</option>
                  </select>
                ) : setting.type === 'password' ? (
                  <input
                    type="password"
                    value={editedSettings[setting.key] ?? setting.value}
                    onChange={(e) => setEditedSettings({ ...editedSettings, [setting.key]: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="Enter value"
                  />
                ) : (
                  <input
                    type={setting.type === 'number' ? 'number' : 'text'}
                    value={editedSettings[setting.key] ?? setting.value}
                    onChange={(e) => setEditedSettings({ ...editedSettings, [setting.key]: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                  />
                )}
                
                <div className="mt-1 flex items-center gap-2 text-xs text-gray-500">
                  <span className={`px-2 py-0.5 rounded ${setting.is_public ? 'bg-green-100 text-green-700' : 'bg-gray-100'}`}>
                    {setting.is_public ? 'Public' : 'Private'}
                  </span>
                  <span>Type: {setting.type}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
