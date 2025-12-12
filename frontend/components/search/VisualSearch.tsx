'use client';

import { useState, useRef } from 'react';
import { Camera, Upload, X, Loader2, Image as ImageIcon } from 'lucide-react';

interface VisualSearchProps {
  onResult: (results: any[]) => void;
  onError?: (error: string) => void;
  className?: string;
}

interface SearchResult {
  id: string;
  title: string;
  price: number;
  image: string;
  similarity: number;
}

export function VisualSearch({
  onResult,
  onError,
  className = '',
}: VisualSearchProps) {
  const [isSearching, setIsSearching] = useState(false);
  const [previewImage, setPreviewImage] = useState<string | null>(null);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const cameraInputRef = useRef<HTMLInputElement>(null);

  const handleImageUpload = async (file: File) => {
    if (!file.type.startsWith('image/')) {
      onError?.('Please upload a valid image file.');
      return;
    }

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => {
      setPreviewImage(e.target?.result as string);
    };
    reader.readAsDataURL(file);

    // Search
    setIsSearching(true);
    try {
      const formData = new FormData();
      formData.append('image', file);

      const response = await fetch('/api/search/visual', {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        throw new Error('Failed to perform visual search');
      }

      const data = await response.json();
      onResult(data.results || []);
    } catch (error) {
      console.error('Visual search error:', error);
      onError?.(
        'Failed to search with this image. Please try another image or check your connection.'
      );
    } finally {
      setIsSearching(false);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      handleImageUpload(file);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    const file = e.dataTransfer.files?.[0];
    if (file) {
      handleImageUpload(file);
    }
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const clearPreview = () => {
    setPreviewImage(null);
    if (fileInputRef.current) fileInputRef.current.value = '';
    if (cameraInputRef.current) cameraInputRef.current.value = '';
  };

  return (
    <div className={`bg-white rounded-lg shadow-lg p-6 ${className}`}>
      <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
        <ImageIcon className="w-6 h-6" />
        Visual Search
      </h2>
      <p className="text-sm text-gray-600 mb-6">
        Upload or take a photo to find similar products
      </p>

      {previewImage ? (
        <div className="relative">
          <img
            src={previewImage}
            alt="Preview"
            className="w-full h-64 object-contain bg-gray-50 rounded-lg"
          />
          <button
            onClick={clearPreview}
            className="absolute top-2 right-2 p-2 bg-white rounded-full shadow-lg hover:bg-gray-100"
            aria-label="Clear image"
          >
            <X className="w-4 h-4" />
          </button>
          
          {isSearching && (
            <div className="absolute inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center rounded-lg">
              <Loader2 className="w-8 h-8 text-blue-500 animate-spin" />
              <p className="text-sm text-gray-600 mt-2">Analyzing image...</p>
            </div>
          )}
        </div>
      ) : (
        <div
          onDrop={handleDrop}
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
            dragActive
              ? 'border-blue-500 bg-blue-50'
              : 'border-gray-300 hover:border-gray-400'
          }`}
        >
          <ImageIcon className="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p className="text-sm text-gray-600 mb-4">
            Drag and drop an image here, or
          </p>
          
          <div className="flex gap-3 justify-center">
            <button
              onClick={() => fileInputRef.current?.click()}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <Upload className="w-4 h-4" />
              Upload Photo
            </button>
            
            <button
              onClick={() => cameraInputRef.current?.click()}
              className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <Camera className="w-4 h-4" />
              Take Photo
            </button>
          </div>

          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            onChange={handleFileSelect}
            className="hidden"
          />
          <input
            ref={cameraInputRef}
            type="file"
            accept="image/*"
            capture="environment"
            onChange={handleFileSelect}
            className="hidden"
          />
          
          <p className="text-xs text-gray-500 mt-4">
            Supported formats: JPG, PNG, WebP (max 10MB)
          </p>
        </div>
      )}
    </div>
  );
}

// Compact version for inline use
export function VisualSearchButton({
  onResult,
  onError,
  className = '',
}: VisualSearchProps) {
  const [isSearching, setIsSearching] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setIsSearching(true);
    try {
      const formData = new FormData();
      formData.append('image', file);

      const response = await fetch('/api/search/visual', {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      onResult(data.results || []);
    } catch (error) {
      onError?.('Visual search failed. Please try again.');
    } finally {
      setIsSearching(false);
    }
  };

  return (
    <>
      <button
        type="button"
        onClick={() => fileInputRef.current?.click()}
        disabled={isSearching}
        className={`p-2 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50 ${className}`}
        aria-label="Visual search"
        title="Search by image"
      >
        {isSearching ? (
          <Loader2 className="w-5 h-5 text-gray-500 animate-spin" />
        ) : (
          <Camera className="w-5 h-5 text-gray-500" />
        )}
      </button>
      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        onChange={handleFileSelect}
        className="hidden"
      />
    </>
  );
}
