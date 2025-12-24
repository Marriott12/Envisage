'use client';

import { useState, useRef } from 'react';
import { importExportApi } from '../../../lib/highPriorityApi';
import {
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  DocumentArrowDownIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'react-hot-toast';

interface ImportResult {
  imported: number;
  updated: number;
  failed: number;
  errors: Array<{ row: number; error: string }>;
}

interface ValidationResult {
  valid: boolean;
  total_rows: number;
  errors: Array<{ row: number; error: string }>;
  preview: Array<any>;
}

export default function ProductImportExport() {
  const [activeTab, setActiveTab] = useState<'import' | 'export'>('import');
  const [importing, setImporting] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [validationResult, setValidationResult] = useState<ValidationResult | null>(null);
  const [importResult, setImportResult] = useState<ImportResult | null>(null);
  const [updateExisting, setUpdateExisting] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Export filters
  const [exportFilters, setExportFilters] = useState({
    category_id: '',
    status: 'active',
  });

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      if (!file.name.endsWith('.csv')) {
        toast.error('Please select a CSV file');
        return;
      }
      setSelectedFile(file);
      setValidationResult(null);
      setImportResult(null);
    }
  };

  const handleValidate = async () => {
    if (!selectedFile) {
      toast.error('Please select a file first');
      return;
    }

    try {
      setImporting(true);
      const response = await importExportApi.validateImport(selectedFile);
      
      if (response.success) {
        setValidationResult(response.data);
        if (response.data.valid) {
          toast.success('File validation passed!');
        } else {
          toast.error(`Found ${response.data.errors.length} validation errors`);
        }
      }
    } catch (error: any) {
      toast.error(error.message || 'Validation failed');
    } finally {
      setImporting(false);
    }
  };

  const handleImport = async () => {
    if (!selectedFile) {
      toast.error('Please select a file first');
      return;
    }

    if (validationResult && !validationResult.valid) {
      toast.error('Please fix validation errors before importing');
      return;
    }

    try {
      setImporting(true);
      const response = await importExportApi.importProducts(selectedFile, updateExisting);
      
      if (response.success) {
        setImportResult(response.data);
        toast.success(`Successfully imported ${response.data.imported} products!`);
        setSelectedFile(null);
        setValidationResult(null);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      }
    } catch (error: any) {
      toast.error(error.message || 'Import failed');
    } finally {
      setImporting(false);
    }
  };

  const handleDownloadTemplate = async () => {
    try {
      const blob = await importExportApi.downloadTemplate('products');
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'product-import-template.csv';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      toast.success('Template downloaded');
    } catch (error) {
      toast.error('Failed to download template');
    }
  };

  const handleExport = async () => {
    try {
      setExporting(true);
      const filters: any = { status: exportFilters.status };
      if (exportFilters.category_id) {
        filters.category_id = parseInt(exportFilters.category_id);
      }

      const blob = await importExportApi.exportProducts(filters);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `products-export-${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      toast.success('Products exported successfully');
    } catch (error) {
      toast.error('Export failed');
    } finally {
      setExporting(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Product Import/Export</h1>
        <p className="mt-1 text-sm text-gray-500">
          Bulk manage your products using CSV files
        </p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="flex -mb-px space-x-8">
          <button
            onClick={() => setActiveTab('import')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'import'
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            <ArrowUpTrayIcon className="w-5 h-5 inline mr-2" />
            Import Products
          </button>
          <button
            onClick={() => setActiveTab('export')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'export'
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            <ArrowDownTrayIcon className="w-5 h-5 inline mr-2" />
            Export Products
          </button>
        </nav>
      </div>

      {/* Import Tab */}
      {activeTab === 'import' && (
        <div className="space-y-6">
          {/* Download Template */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-start">
              <DocumentArrowDownIcon className="h-6 w-6 text-blue-600 mt-0.5" />
              <div className="ml-3 flex-1">
                <h3 className="text-sm font-medium text-blue-800">
                  First time importing?
                </h3>
                <p className="mt-1 text-sm text-blue-700">
                  Download our CSV template to see the correct format for your product data.
                </p>
                <button
                  onClick={handleDownloadTemplate}
                  className="mt-3 inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50"
                >
                  <DocumentArrowDownIcon className="h-4 w-4 mr-2" />
                  Download Template
                </button>
              </div>
            </div>
          </div>

          {/* File Upload */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <label className="block text-sm font-medium text-gray-700 mb-4">
              Select CSV File
            </label>
            
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary-400 transition-colors">
              <input
                ref={fileInputRef}
                type="file"
                accept=".csv"
                onChange={handleFileSelect}
                className="hidden"
                id="file-upload"
              />
              <label
                htmlFor="file-upload"
                className="cursor-pointer flex flex-col items-center"
              >
                <ArrowUpTrayIcon className="h-12 w-12 text-gray-400 mb-3" />
                <span className="text-sm font-medium text-gray-900">
                  Click to upload or drag and drop
                </span>
                <span className="text-xs text-gray-500 mt-1">CSV files only</span>
              </label>
            </div>

            {selectedFile && (
              <div className="mt-4 flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div className="flex items-center">
                  <DocumentArrowDownIcon className="h-5 w-5 text-gray-400 mr-2" />
                  <span className="text-sm font-medium text-gray-900">{selectedFile.name}</span>
                  <span className="text-xs text-gray-500 ml-2">
                    ({(selectedFile.size / 1024).toFixed(2)} KB)
                  </span>
                </div>
                <button
                  onClick={() => {
                    setSelectedFile(null);
                    setValidationResult(null);
                    if (fileInputRef.current) fileInputRef.current.value = '';
                  }}
                  className="text-red-600 hover:text-red-800"
                >
                  <XCircleIcon className="h-5 w-5" />
                </button>
              </div>
            )}

            {/* Options */}
            <div className="mt-4">
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={updateExisting}
                  onChange={(e) => setUpdateExisting(e.target.checked)}
                  className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span className="ml-2 text-sm text-gray-700">
                  Update existing products (match by SKU)
                </span>
              </label>
            </div>

            {/* Actions */}
            <div className="mt-6 flex gap-3">
              <button
                onClick={handleValidate}
                disabled={!selectedFile || importing}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {importing ? 'Validating...' : 'Validate File'}
              </button>
              <button
                onClick={handleImport}
                disabled={!selectedFile || importing || (validationResult && !validationResult.valid)}
                className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {importing ? 'Importing...' : 'Import Products'}
              </button>
            </div>
          </div>

          {/* Validation Results */}
          {validationResult && (
            <div className={`rounded-lg border p-4 ${
              validationResult.valid ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
            }`}>
              <div className="flex items-start">
                {validationResult.valid ? (
                  <CheckCircleIcon className="h-6 w-6 text-green-600" />
                ) : (
                  <XCircleIcon className="h-6 w-6 text-red-600" />
                )}
                <div className="ml-3 flex-1">
                  <h3 className={`text-sm font-medium ${
                    validationResult.valid ? 'text-green-800' : 'text-red-800'
                  }`}>
                    {validationResult.valid ? 'Validation Passed' : 'Validation Failed'}
                  </h3>
                  <p className={`mt-1 text-sm ${
                    validationResult.valid ? 'text-green-700' : 'text-red-700'
                  }`}>
                    Found {validationResult.total_rows} rows
                    {validationResult.errors.length > 0 && ` with ${validationResult.errors.length} errors`}
                  </p>
                  
                  {validationResult.errors.length > 0 && (
                    <div className="mt-3 space-y-1">
                      <p className="text-xs font-medium text-red-800">Errors:</p>
                      <ul className="text-xs text-red-700 space-y-1 max-h-40 overflow-y-auto">
                        {validationResult.errors.slice(0, 10).map((error, idx) => (
                          <li key={idx}>Row {error.row}: {error.error}</li>
                        ))}
                        {validationResult.errors.length > 10 && (
                          <li className="font-medium">
                            ... and {validationResult.errors.length - 10} more errors
                          </li>
                        )}
                      </ul>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Import Results */}
          {importResult && (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 className="text-lg font-medium text-gray-900 mb-4">Import Results</h3>
              
              <div className="grid grid-cols-3 gap-4 mb-4">
                <div className="bg-green-50 rounded-lg p-4 text-center">
                  <p className="text-2xl font-bold text-green-600">{importResult.imported}</p>
                  <p className="text-sm text-green-800">Imported</p>
                </div>
                <div className="bg-blue-50 rounded-lg p-4 text-center">
                  <p className="text-2xl font-bold text-blue-600">{importResult.updated}</p>
                  <p className="text-sm text-blue-800">Updated</p>
                </div>
                <div className="bg-red-50 rounded-lg p-4 text-center">
                  <p className="text-2xl font-bold text-red-600">{importResult.failed}</p>
                  <p className="text-sm text-red-800">Failed</p>
                </div>
              </div>

              {importResult.errors.length > 0 && (
                <div className="border-t pt-4">
                  <p className="text-sm font-medium text-gray-900 mb-2">Failed Rows:</p>
                  <ul className="text-sm text-gray-700 space-y-1 max-h-40 overflow-y-auto">
                    {importResult.errors.map((error, idx) => (
                      <li key={idx} className="flex items-start">
                        <ExclamationTriangleIcon className="h-4 w-4 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" />
                        <span>Row {error.row}: {error.error}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* Export Tab */}
      {activeTab === 'export' && (
        <div className="space-y-6">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Export Filters</h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Category ID (Optional)
                </label>
                <input
                  type="number"
                  value={exportFilters.category_id}
                  onChange={(e) => setExportFilters({ ...exportFilters, category_id: e.target.value })}
                  placeholder="Leave empty for all categories"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Product Status
                </label>
                <select
                  value={exportFilters.status}
                  onChange={(e) => setExportFilters({ ...exportFilters, status: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="out_of_stock">Out of Stock</option>
                </select>
              </div>

              <button
                onClick={handleExport}
                disabled={exporting}
                className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ArrowDownTrayIcon className="h-5 w-5" />
                {exporting ? 'Exporting...' : 'Export Products'}
              </button>
            </div>
          </div>

          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 className="text-sm font-medium text-gray-900 mb-2">Export Information</h4>
            <ul className="text-sm text-gray-600 space-y-1">
              <li>• Exported file will be in CSV format</li>
              <li>• All product fields will be included</li>
              <li>• You can re-import the exported file after modifications</li>
              <li>• Use filters to export specific products only</li>
            </ul>
          </div>
        </div>
      )}
    </div>
  );
}
