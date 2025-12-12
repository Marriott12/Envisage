'use client';

import { useEffect } from 'react';

/**
 * Keyboard navigation utilities for accessibility
 * WCAG 2.1 Success Criterion 2.1.1 (Level A)
 */

interface KeyboardNavigationOptions {
  container?: HTMLElement | null;
  selector?: string;
  loop?: boolean;
  onEscape?: () => void;
}

/**
 * Hook for arrow key navigation
 */
export function useArrowKeyNavigation(options: KeyboardNavigationOptions = {}) {
  const {
    container,
    selector = '[role="menuitem"], [role="option"], button:not([disabled])',
    loop = true,
    onEscape,
  } = options;

  useEffect(() => {
    const targetContainer = container || document;

    const handleKeyDown = (e: KeyboardEvent) => {
      const focusableElements = Array.from(
        targetContainer.querySelectorAll<HTMLElement>(selector)
      );

      if (focusableElements.length === 0) return;

      const currentIndex = focusableElements.findIndex(
        (el) => el === document.activeElement
      );

      let nextIndex = currentIndex;

      switch (e.key) {
        case 'ArrowDown':
        case 'ArrowRight':
          e.preventDefault();
          nextIndex = currentIndex + 1;
          if (nextIndex >= focusableElements.length) {
            nextIndex = loop ? 0 : currentIndex;
          }
          break;

        case 'ArrowUp':
        case 'ArrowLeft':
          e.preventDefault();
          nextIndex = currentIndex - 1;
          if (nextIndex < 0) {
            nextIndex = loop ? focusableElements.length - 1 : 0;
          }
          break;

        case 'Home':
          e.preventDefault();
          nextIndex = 0;
          break;

        case 'End':
          e.preventDefault();
          nextIndex = focusableElements.length - 1;
          break;

        case 'Escape':
          if (onEscape) {
            e.preventDefault();
            onEscape();
          }
          return;

        default:
          return;
      }

      focusableElements[nextIndex]?.focus();
    };

    targetContainer.addEventListener('keydown', handleKeyDown as EventListener);

    return () => {
      targetContainer.removeEventListener('keydown', handleKeyDown as EventListener);
    };
  }, [container, selector, loop, onEscape]);
}

/**
 * Hook for typeahead/keyboard search in lists
 */
export function useTypeahead(
  items: Array<{ id: string; label: string }>,
  onSelect: (id: string) => void
) {
  useEffect(() => {
    let searchString = '';
    let timeoutId: NodeJS.Timeout;

    const handleKeyPress = (e: KeyboardEvent) => {
      // Ignore special keys
      if (e.ctrlKey || e.metaKey || e.altKey || e.key.length > 1) {
        return;
      }

      clearTimeout(timeoutId);

      searchString += e.key.toLowerCase();

      // Find matching item
      const matchedItem = items.find((item) =>
        item.label.toLowerCase().startsWith(searchString)
      );

      if (matchedItem) {
        onSelect(matchedItem.id);
      }

      // Clear search string after 500ms
      timeoutId = setTimeout(() => {
        searchString = '';
      }, 500);
    };

    document.addEventListener('keypress', handleKeyPress);

    return () => {
      document.removeEventListener('keypress', handleKeyPress);
      clearTimeout(timeoutId);
    };
  }, [items, onSelect]);
}

/**
 * Get all focusable elements within a container
 */
export function getFocusableElements(container: HTMLElement): HTMLElement[] {
  const selector =
    'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  return Array.from(container.querySelectorAll<HTMLElement>(selector)).filter(
    (el) => {
      // Check if element is visible
      const style = window.getComputedStyle(el);
      return (
        style.display !== 'none' &&
        style.visibility !== 'hidden' &&
        el.offsetParent !== null
      );
    }
  );
}

/**
 * Focus first element in container
 */
export function focusFirst(container: HTMLElement): void {
  const elements = getFocusableElements(container);
  elements[0]?.focus();
}

/**
 * Focus last element in container
 */
export function focusLast(container: HTMLElement): void {
  const elements = getFocusableElements(container);
  elements[elements.length - 1]?.focus();
}

/**
 * Move focus to next element
 */
export function focusNext(currentElement: HTMLElement, container?: HTMLElement): void {
  const root = container || document.body;
  const elements = getFocusableElements(root);
  const currentIndex = elements.indexOf(currentElement);

  if (currentIndex === -1) return;

  const nextIndex = (currentIndex + 1) % elements.length;
  elements[nextIndex]?.focus();
}

/**
 * Move focus to previous element
 */
export function focusPrevious(currentElement: HTMLElement, container?: HTMLElement): void {
  const root = container || document.body;
  const elements = getFocusableElements(root);
  const currentIndex = elements.indexOf(currentElement);

  if (currentIndex === -1) return;

  const prevIndex = currentIndex === 0 ? elements.length - 1 : currentIndex - 1;
  elements[prevIndex]?.focus();
}

/**
 * Keyboard shortcut handler
 */
interface Shortcut {
  key: string;
  ctrl?: boolean;
  shift?: boolean;
  alt?: boolean;
  meta?: boolean;
  callback: (e: KeyboardEvent) => void;
}

export function useKeyboardShortcuts(shortcuts: Shortcut[]) {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      shortcuts.forEach((shortcut) => {
        const matchesKey = e.key.toLowerCase() === shortcut.key.toLowerCase();
        const matchesCtrl = shortcut.ctrl === undefined || e.ctrlKey === shortcut.ctrl;
        const matchesShift = shortcut.shift === undefined || e.shiftKey === shortcut.shift;
        const matchesAlt = shortcut.alt === undefined || e.altKey === shortcut.alt;
        const matchesMeta = shortcut.meta === undefined || e.metaKey === shortcut.meta;

        if (matchesKey && matchesCtrl && matchesShift && matchesAlt && matchesMeta) {
          e.preventDefault();
          shortcut.callback(e);
        }
      });
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [shortcuts]);
}

/**
 * Hook for escape key handler
 */
export function useEscapeKey(callback: () => void) {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        callback();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [callback]);
}

export default {
  useArrowKeyNavigation,
  useTypeahead,
  getFocusableElements,
  focusFirst,
  focusLast,
  focusNext,
  focusPrevious,
  useKeyboardShortcuts,
  useEscapeKey,
};
