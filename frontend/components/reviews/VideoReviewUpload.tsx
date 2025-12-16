'use client';

import React, { useState, useRef } from 'react';
import { VideoCameraIcon, XMarkIcon, CheckCircleIcon } from '@heroicons/react/24/outline';
import api from '@/lib/api';
import { toast } from 'react-hot-toast';

interface VideoReviewUploadProps {
  productId: number;
  reviewId?: number;
  onUploadComplete: (videoUrl: string) => void;
  maxSizeMB?: number;
  maxDurationSeconds?: number;
}

export default function VideoReviewUpload({
  productId,
  reviewId,
  onUploadComplete,
  maxSizeMB = 100,
  maxDurationSeconds = 120
}: VideoReviewUploadProps) {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [videoDuration, setVideoDuration] = useState<number>(0);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const videoRef = useRef<HTMLVideoElement>(null);

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('video/')) {
      toast.error('Please select a valid video file');
      return;
    }

    // Validate file size
    const fileSizeMB = file.size / (1024 * 1024);
    if (fileSizeMB > maxSizeMB) {
      toast.error(`Video size must be less than ${maxSizeMB}MB`);
      return;
    }

    setSelectedFile(file);

    // Create preview
    const url = URL.createObjectURL(file);
    setPreview(url);

    // Get video duration
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = () => {
      window.URL.revokeObjectURL(video.src);
      const duration = Math.floor(video.duration);
      setVideoDuration(duration);

      if (duration > maxDurationSeconds) {
        toast.error(`Video duration must be less than ${maxDurationSeconds} seconds`);
        handleRemove();
      }
    };
    video.src = url;
  };

  const handleRemove = () => {
    setSelectedFile(null);
    setPreview(null);
    setVideoDuration(0);
    setUploadProgress(0);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleUpload = async () => {
    if (!selectedFile) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('video', selectedFile);
    formData.append('product_id', productId.toString());
    if (reviewId) {
      formData.append('review_id', reviewId.toString());
    }

    try {
      const response = await api.post('/video-reviews/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
          const progress = progressEvent.total
            ? Math.round((progressEvent.loaded * 100) / progressEvent.total)
            : 0;
          setUploadProgress(progress);
        },
      });

      toast.success('Video uploaded successfully! Processing...');
      onUploadComplete(response.data.video_url);
      handleRemove();
    } catch (error: any) {
      console.error('Video upload failed:', error);
      toast.error(error.response?.data?.message || 'Failed to upload video');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-4">
      {!selectedFile ? (
        <div
          onClick={() => fileInputRef.current?.click()}
          className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary-400 transition-colors cursor-pointer"
        >
          <VideoCameraIcon className="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-700 font-medium mb-1">
            Click to upload a video review
          </p>
          <p className="text-sm text-gray-500">
            MP4, WebM, or MOV (max {maxSizeMB}MB, {maxDurationSeconds}s)
          </p>
          <input
            ref={fileInputRef}
            type="file"
            accept="video/mp4,video/webm,video/quicktime"
            onChange={handleFileSelect}
            className="hidden"
          />
        </div>
      ) : (
        <div className="space-y-4">
          {/* Video Preview */}
          <div className="relative rounded-lg overflow-hidden bg-black">
            <video
              ref={videoRef}
              src={preview || ''}
              controls
              className="w-full max-h-96"
            />
            {!uploading && (
              <button
                onClick={handleRemove}
                className="absolute top-2 right-2 p-2 bg-black bg-opacity-50 rounded-full hover:bg-opacity-70 transition-colors"
              >
                <XMarkIcon className="w-5 h-5 text-white" />
              </button>
            )}
          </div>

          {/* Video Info */}
          <div className="flex items-center justify-between text-sm text-gray-600">
            <span>{selectedFile.name}</span>
            <span>
              {(selectedFile.size / (1024 * 1024)).toFixed(2)}MB • {videoDuration}s
            </span>
          </div>

          {/* Upload Progress */}
          {uploading && (
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-700">Uploading...</span>
                <span className="font-semibold text-primary-600">
                  {uploadProgress}%
                </span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div
                  className="bg-primary-600 h-2 rounded-full transition-all duration-300"
                  style={{ width: `${uploadProgress}%` }}
                ></div>
              </div>
            </div>
          )}

          {/* Upload Button */}
          <div className="flex gap-3">
            <button
              onClick={handleRemove}
              disabled={uploading}
              className="flex-1 btn-secondary"
            >
              Cancel
            </button>
            <button
              onClick={handleUpload}
              disabled={uploading}
              className="flex-1 btn-primary"
            >
              {uploading ? 'Uploading...' : 'Upload Video'}
            </button>
          </div>

          {/* Guidelines */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm font-semibold text-blue-900 mb-2">
              Video Review Guidelines:
            </p>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• Show the product clearly and in good lighting</li>
              <li>• Demonstrate key features and functionality</li>
              <li>• Be honest about your experience</li>
              <li>• Keep it concise and relevant</li>
            </ul>
          </div>
        </div>
      )}
    </div>
  );
}
