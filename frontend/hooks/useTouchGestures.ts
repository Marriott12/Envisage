'use client';

import { useEffect, useState, TouchEvent, useRef } from 'react';

/**
 * Touch gesture utilities for mobile interactions
 */

interface SwipeHandlers {
  onSwipeLeft?: () => void;
  onSwipeRight?: () => void;
  onSwipeUp?: () => void;
  onSwipeDown?: () => void;
}

interface SwipeOptions {
  threshold?: number; // Minimum distance for a swipe (default: 50px)
  timeout?: number; // Maximum time for a swipe (default: 300ms)
}

/**
 * Hook for swipe gestures
 */
export function useSwipe(handlers: SwipeHandlers, options: SwipeOptions = {}) {
  const { threshold = 50, timeout = 300 } = options;
  const [touchStart, setTouchStart] = useState<{ x: number; y: number; time: number } | null>(null);

  const handleTouchStart = (e: TouchEvent) => {
    const touch = e.touches[0];
    if (!touch) return;
    
    setTouchStart({
      x: touch.clientX,
      y: touch.clientY,
      time: Date.now(),
    });
  };

  const handleTouchEnd = (e: TouchEvent) => {
    if (!touchStart) return;

    const touch = e.changedTouches[0];
    if (!touch) return;
    
    const deltaX = touch.clientX - touchStart.x;
    const deltaY = touch.clientY - touchStart.y;
    const deltaTime = Date.now() - touchStart.time;

    // Check if swipe is within timeout
    if (deltaTime > timeout) {
      setTouchStart(null);
      return;
    }

    // Determine swipe direction
    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      // Horizontal swipe
      if (Math.abs(deltaX) > threshold) {
        if (deltaX > 0 && handlers.onSwipeRight) {
          handlers.onSwipeRight();
        } else if (deltaX < 0 && handlers.onSwipeLeft) {
          handlers.onSwipeLeft();
        }
      }
    } else {
      // Vertical swipe
      if (Math.abs(deltaY) > threshold) {
        if (deltaY > 0 && handlers.onSwipeDown) {
          handlers.onSwipeDown();
        } else if (deltaY < 0 && handlers.onSwipeUp) {
          handlers.onSwipeUp();
        }
      }
    }

    setTouchStart(null);
  };

  return {
    onTouchStart: handleTouchStart,
    onTouchEnd: handleTouchEnd,
  };
}

/**
 * Hook for pinch to zoom gesture
 */
export function usePinchZoom(
  onZoom: (scale: number) => void,
  options: { minScale?: number; maxScale?: number } = {}
) {
  const { minScale = 0.5, maxScale = 3 } = options;
  const initialDistance = useRef<number | null>(null);
  const currentScale = useRef(1);

  const getDistance = (touches: React.TouchList) => {
    if (touches.length < 2 || !touches[0] || !touches[1]) return 0;
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
  };

  const handleTouchStart = (e: TouchEvent) => {
    if (e.touches.length === 2) {
      initialDistance.current = getDistance(e.touches);
    }
  };

  const handleTouchMove = (e: TouchEvent) => {
    if (e.touches.length === 2 && initialDistance.current) {
      const distance = getDistance(e.touches);
      const scale = distance / initialDistance.current;
      const newScale = Math.min(Math.max(currentScale.current * scale, minScale), maxScale);
      
      onZoom(newScale);
      initialDistance.current = distance;
      currentScale.current = newScale;
    }
  };

  const handleTouchEnd = () => {
    initialDistance.current = null;
  };

  return {
    onTouchStart: handleTouchStart,
    onTouchMove: handleTouchMove,
    onTouchEnd: handleTouchEnd,
  };
}

/**
 * Hook for long press gesture
 */
export function useLongPress(
  onLongPress: () => void,
  options: { duration?: number } = {}
) {
  const { duration = 500 } = options;
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const isLongPressRef = useRef(false);

  const start = () => {
    isLongPressRef.current = false;
    timerRef.current = setTimeout(() => {
      isLongPressRef.current = true;
      onLongPress();
    }, duration);
  };

  const cancel = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
    }
    timerRef.current = null;
  };

  const handleTouchStart = () => {
    start();
  };

  const handleTouchEnd = () => {
    cancel();
  };

  const handleTouchMove = () => {
    cancel();
  };

  return {
    onTouchStart: handleTouchStart,
    onTouchEnd: handleTouchEnd,
    onTouchMove: handleTouchMove,
  };
}

/**
 * Hook for drag gesture
 */
export function useDrag(
  onDrag: (deltaX: number, deltaY: number) => void,
  onDragEnd?: () => void
) {
  const startPos = useRef<{ x: number; y: number } | null>(null);
  const isDragging = useRef(false);

  const handleTouchStart = (e: TouchEvent) => {
    const touch = e.touches[0];
    if (!touch) return;
    
    startPos.current = { x: touch.clientX, y: touch.clientY };
    isDragging.current = true;
  };

  const handleTouchMove = (e: TouchEvent) => {
    if (!isDragging.current || !startPos.current) return;

    const touch = e.touches[0];
    if (!touch) return;
    
    const deltaX = touch.clientX - startPos.current.x;
    const deltaY = touch.clientY - startPos.current.y;

    onDrag(deltaX, deltaY);

    startPos.current = { x: touch.clientX, y: touch.clientY };
  };

  const handleTouchEnd = () => {
    isDragging.current = false;
    startPos.current = null;
    if (onDragEnd) {
      onDragEnd();
    }
  };

  return {
    onTouchStart: handleTouchStart,
    onTouchMove: handleTouchMove,
    onTouchEnd: handleTouchEnd,
  };
}

/**
 * Hook for detecting touch device
 */
export function useIsTouchDevice() {
  const [isTouchDevice, setIsTouchDevice] = useState(false);

  useEffect(() => {
    const checkTouch = () => {
      setIsTouchDevice(
        'ontouchstart' in window ||
        navigator.maxTouchPoints > 0 ||
        (navigator as any).msMaxTouchPoints > 0
      );
    };

    checkTouch();
    window.addEventListener('resize', checkTouch);

    return () => {
      window.removeEventListener('resize', checkTouch);
    };
  }, []);

  return isTouchDevice;
}

export default {
  useSwipe,
  usePinchZoom,
  useLongPress,
  useDrag,
  useIsTouchDevice,
};
