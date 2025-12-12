'use client';

import { useState, useRef, useEffect, TouchEvent, MouseEvent } from 'react';
import { Maximize2, Minimize2, RotateCw, Play, Pause } from 'lucide-react';
import Image from 'next/image';

interface Product360ViewerProps {
  images: string[]; // Array of image URLs (360° sequence)
  alt: string;
  autoRotate?: boolean;
  rotationSpeed?: number; // ms per frame
  className?: string;
}

export function Product360Viewer({
  images,
  alt,
  autoRotate = false,
  rotationSpeed = 100,
  className = '',
}: Product360ViewerProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isDragging, setIsDragging] = useState(false);
  const [isAutoRotating, setIsAutoRotating] = useState(autoRotate);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [startX, setStartX] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);
  const autoRotateRef = useRef<NodeJS.Timeout | null>(null);

  const totalFrames = images.length;
  const sensitivity = 3; // pixels per frame

  // Auto-rotate effect
  useEffect(() => {
    if (isAutoRotating && !isDragging) {
      autoRotateRef.current = setInterval(() => {
        setCurrentIndex((prev) => (prev + 1) % totalFrames);
      }, rotationSpeed);
    } else {
      if (autoRotateRef.current) {
        clearInterval(autoRotateRef.current);
        autoRotateRef.current = null;
      }
    }

    return () => {
      if (autoRotateRef.current) {
        clearInterval(autoRotateRef.current);
      }
    };
  }, [isAutoRotating, isDragging, totalFrames, rotationSpeed]);

  // Mouse drag handlers
  const handleMouseDown = (e: MouseEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(true);
    setStartX(e.clientX);
    setIsAutoRotating(false);
  };

  const handleMouseMove = (e: MouseEvent<HTMLDivElement>) => {
    if (!isDragging) return;

    const deltaX = e.clientX - startX;
    const framesToMove = Math.floor(Math.abs(deltaX) / sensitivity);

    if (framesToMove > 0) {
      const direction = deltaX > 0 ? 1 : -1;
      setCurrentIndex((prev) => {
        const newIndex = prev + direction * framesToMove;
        return ((newIndex % totalFrames) + totalFrames) % totalFrames;
      });
      setStartX(e.clientX);
    }
  };

  const handleMouseUp = () => {
    setIsDragging(false);
  };

  // Touch handlers
  const handleTouchStart = (e: TouchEvent<HTMLDivElement>) => {
    if (e.touches[0]) {
      setIsDragging(true);
      setStartX(e.touches[0].clientX);
      setIsAutoRotating(false);
    }
  };

  const handleTouchMove = (e: TouchEvent<HTMLDivElement>) => {
    if (!isDragging || !e.touches[0]) return;

    const deltaX = e.touches[0].clientX - startX;
    const framesToMove = Math.floor(Math.abs(deltaX) / sensitivity);

    if (framesToMove > 0) {
      const direction = deltaX > 0 ? 1 : -1;
      setCurrentIndex((prev) => {
        const newIndex = prev + direction * framesToMove;
        return ((newIndex % totalFrames) + totalFrames) % totalFrames;
      });
      setStartX(e.touches[0].clientX);
    }
  };

  const handleTouchEnd = () => {
    setIsDragging(false);
  };

  // Fullscreen toggle
  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      containerRef.current?.requestFullscreen();
      setIsFullscreen(true);
    } else {
      document.exitFullscreen();
      setIsFullscreen(false);
    }
  };

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'ArrowLeft') {
        setCurrentIndex((prev) => ((prev - 1 + totalFrames) % totalFrames));
        setIsAutoRotating(false);
      } else if (e.key === 'ArrowRight') {
        setCurrentIndex((prev) => (prev + 1) % totalFrames);
        setIsAutoRotating(false);
      } else if (e.key === ' ') {
        e.preventDefault();
        setIsAutoRotating((prev) => !prev);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [totalFrames]);

  if (images.length === 0) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-100 rounded-lg">
        <p className="text-gray-500">No images available</p>
      </div>
    );
  }

  return (
    <div
      ref={containerRef}
      className={`relative bg-gray-50 rounded-lg overflow-hidden ${className}`}
    >
      {/* Main Image Display */}
      <div
        className="relative aspect-square cursor-grab active:cursor-grabbing select-none"
        onMouseDown={handleMouseDown}
        onMouseMove={handleMouseMove}
        onMouseUp={handleMouseUp}
        onMouseLeave={handleMouseUp}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
      >
        <Image
          src={images[currentIndex] || images[0] || ''}
          alt={`${alt} - View ${currentIndex + 1}`}
          fill
          className="object-contain pointer-events-none"
          priority={currentIndex === 0}
          loading={currentIndex === 0 ? 'eager' : 'lazy'}
        />

        {/* Drag Instruction Overlay */}
        {!isDragging && currentIndex === 0 && (
          <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 text-white transition-opacity hover:opacity-0 pointer-events-none">
            <div className="text-center">
              <RotateCw className="w-12 h-12 mx-auto mb-2 animate-spin-slow" />
              <p className="text-sm font-medium">Drag to rotate 360°</p>
            </div>
          </div>
        )}
      </div>

      {/* Controls */}
      <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center gap-2 bg-white rounded-full shadow-lg px-4 py-2">
        {/* Auto-rotate toggle */}
        <button
          onClick={() => setIsAutoRotating(!isAutoRotating)}
          className="p-2 hover:bg-gray-100 rounded-full transition-colors"
          aria-label={isAutoRotating ? 'Pause rotation' : 'Auto rotate'}
        >
          {isAutoRotating ? (
            <Pause className="w-4 h-4" />
          ) : (
            <Play className="w-4 h-4" />
          )}
        </button>

        {/* Frame indicator */}
        <div className="px-3 py-1 bg-gray-100 rounded-full text-xs font-medium">
          {currentIndex + 1} / {totalFrames}
        </div>

        {/* Fullscreen toggle */}
        <button
          onClick={toggleFullscreen}
          className="p-2 hover:bg-gray-100 rounded-full transition-colors"
          aria-label={isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
        >
          {isFullscreen ? (
            <Minimize2 className="w-4 h-4" />
          ) : (
            <Maximize2 className="w-4 h-4" />
          )}
        </button>
      </div>

      {/* Progress Bar */}
      <div className="absolute bottom-0 left-0 right-0 h-1 bg-gray-200">
        <div
          className="h-full bg-blue-600 transition-all duration-100"
          style={{ width: `${((currentIndex + 1) / totalFrames) * 100}%` }}
        />
      </div>

      {/* Keyboard shortcuts hint */}
      <div className="absolute top-4 right-4 bg-black bg-opacity-60 text-white text-xs px-3 py-2 rounded opacity-0 hover:opacity-100 transition-opacity">
        <p className="font-medium mb-1">Keyboard shortcuts:</p>
        <p>← → Arrow keys to rotate</p>
        <p>Space to toggle auto-rotate</p>
      </div>
    </div>
  );
}

// Thumbnail navigation variant
export function Product360ViewerWithThumbs({
  images,
  alt,
  className = '',
}: Omit<Product360ViewerProps, 'autoRotate' | 'rotationSpeed'>) {
  const [currentIndex, setCurrentIndex] = useState(0);

  return (
    <div className={className}>
      <Product360Viewer
        images={images}
        alt={alt}
        autoRotate={false}
        className="mb-4"
      />
      
      {/* Thumbnail strip */}
      <div className="flex gap-2 overflow-x-auto pb-2">
        {images.map((img, index) => (
          <button
            key={index}
            onClick={() => setCurrentIndex(index)}
            className={`relative w-16 h-16 flex-shrink-0 rounded border-2 transition-all ${
              index === currentIndex
                ? 'border-blue-600 ring-2 ring-blue-200'
                : 'border-gray-200 hover:border-gray-400'
            }`}
          >
            <Image
              src={img}
              alt={`${alt} thumbnail ${index + 1}`}
              fill
              className="object-cover rounded"
            />
          </button>
        ))}
      </div>
    </div>
  );
}
