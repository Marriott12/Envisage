import React, { useEffect, useState } from 'react';
import api from '../lib/api';
import { useAuth } from '../hooks/useAuth';

interface Role {
  id: number;
  name: string;
  guard_name: string;
}

interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

const RolePermissionManager: React.FC = () => {
  const { user } = useAuth();
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [selectedRole, setSelectedRole] = useState<Role | null>(null);
  const [rolePermissions, setRolePermissions] = useState<number[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Only allow admins
  const userRole = (user as any)?.role || '';
  if (!user || userRole !== 'admin') {
    return null;
  }

  useEffect(() => {
    const fetchRolesAndPermissions = async () => {
      setLoading(true);
      try {
        const [rolesRes, permsRes] = await Promise.all([
          api.get('/roles'),
          api.get('/permissions'),
        ]);
        setRoles(rolesRes.data.roles || rolesRes.data);
        setPermissions(permsRes.data.permissions || permsRes.data);
      } catch (err: any) {
        setError('Failed to load roles or permissions');
      } finally {
        setLoading(false);
      }
    };
    fetchRolesAndPermissions();
  }, []);

  const handleRoleSelect = async (role: Role) => {
    setSelectedRole(role);
    setLoading(true);
    try {
      const res = await api.get(`/roles/${role.id}/permissions`);
      setRolePermissions(res.data.permission_ids || res.data);
    } catch {
      setRolePermissions([]);
    } finally {
      setLoading(false);
    }
  };

  const handlePermissionToggle = (permId: number) => {
    setRolePermissions((prev) =>
      prev.includes(permId)
        ? prev.filter((id) => id !== permId)
        : [...prev, permId]
    );
  };

  const handleSave = async () => {
    if (!selectedRole) return;
    setLoading(true);
    setError(null);
    try {
      await api.post(`/roles/${selectedRole.id}/permissions`, {
        permissions: rolePermissions,
      });
      alert('Permissions updated!');
    } catch {
      setError('Failed to update permissions');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-xl shadow p-6 mt-8">
      <h2 className="text-2xl font-bold mb-4">Role & Permission Management</h2>
      {error && <div className="text-red-500 mb-2">{error}</div>}
      <div className="flex gap-8">
        <div className="w-1/3">
          <h3 className="font-semibold mb-2">Roles</h3>
          <ul>
            {roles.map((role) => (
              <li
                key={role.id}
                className={`cursor-pointer p-2 rounded mb-1 ${selectedRole?.id === role.id ? 'bg-primary-100' : ''}`}
                onClick={() => handleRoleSelect(role)}
              >
                {role.name}
              </li>
            ))}
          </ul>
        </div>
        <div className="w-2/3">
          <h3 className="font-semibold mb-2">Permissions</h3>
          {selectedRole ? (
            <div>
              <ul className="grid grid-cols-2 gap-2">
                {permissions.map((perm) => (
                  <li key={perm.id}>
                    <label className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={rolePermissions.includes(perm.id)}
                        onChange={() => handlePermissionToggle(perm.id)}
                      />
                      {perm.name}
                    </label>
                  </li>
                ))}
              </ul>
              <button
                className="btn-primary mt-4"
                onClick={handleSave}
                disabled={loading}
              >
                Save Permissions
              </button>
            </div>
          ) : (
            <div className="text-gray-500">Select a role to manage permissions</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default RolePermissionManager;
