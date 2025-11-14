import React from 'react';

export default function SystemLogs() {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-900">System Logs</h2>
        <button className="px-4 py-2 border rounded-lg hover:bg-gray-50">
          Download Logs
        </button>
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        <div className="space-y-2 font-mono text-sm">
          <div className="p-2 bg-gray-50 rounded flex justify-between">
            <span><span className="text-green-600">[INFO]</span> 2025-11-14 10:30:15 - User login: admin@envisagezm.com</span>
            <span className="text-gray-400">2 min ago</span>
          </div>
          <div className="p-2 bg-gray-50 rounded flex justify-between">
            <span><span className="text-blue-600">[INFO]</span> 2025-11-14 10:28:42 - Product created: ID 245</span>
            <span className="text-gray-400">4 min ago</span>
          </div>
          <div className="p-2 bg-gray-50 rounded flex justify-between">
            <span><span className="text-yellow-600">[WARN]</span> 2025-11-14 10:25:10 - High memory usage detected</span>
            <span className="text-gray-400">7 min ago</span>
          </div>
          <div className="p-2 bg-gray-50 rounded flex justify-between">
            <span><span className="text-green-600">[INFO]</span> 2025-11-14 10:20:05 - Order #1234 completed</span>
            <span className="text-gray-400">12 min ago</span>
          </div>
          <div className="p-2 bg-gray-50 rounded flex justify-between">
            <span><span className="text-red-600">[ERROR]</span> 2025-11-14 10:15:33 - Payment gateway timeout</span>
            <span className="text-gray-400">17 min ago</span>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <div className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600 mb-1">Total Logs</div>
          <div className="text-2xl font-bold text-gray-900">12,453</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600 mb-1">Errors Today</div>
          <div className="text-2xl font-bold text-red-600">23</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-600 mb-1">Warnings Today</div>
          <div className="text-2xl font-bold text-yellow-600">47</div>
        </div>
      </div>
    </div>
  );
}
