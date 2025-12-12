/**
 * ARIA live region announcer for screen readers
 * WCAG 2.1 Success Criterion 4.1.3 (Level AA)
 */

let announcer: HTMLDivElement | null = null;

/**
 * Initialize the live region announcer
 */
function getAnnouncer(): HTMLDivElement {
  if (announcer) return announcer;

  // Create announcer element
  announcer = document.createElement('div');
  announcer.setAttribute('role', 'status');
  announcer.setAttribute('aria-live', 'polite');
  announcer.setAttribute('aria-atomic', 'true');
  announcer.className = 'sr-only';
  announcer.style.cssText = `
    position: absolute;
    left: -10000px;
    width: 1px;
    height: 1px;
    overflow: hidden;
  `;

  document.body.appendChild(announcer);

  return announcer;
}

/**
 * Announce a message to screen readers
 */
export function announce(
  message: string,
  priority: 'polite' | 'assertive' = 'polite',
  delay = 100
): void {
  const announcerElement = getAnnouncer();
  announcerElement.setAttribute('aria-live', priority);

  // Clear previous message
  announcerElement.textContent = '';

  // Announce new message after a brief delay to ensure it's picked up
  setTimeout(() => {
    announcerElement.textContent = message;
  }, delay);
}

/**
 * Announce page navigation
 */
export function announceNavigation(pageName: string): void {
  announce(`Navigated to ${pageName}`, 'polite');
}

/**
 * Announce form errors
 */
export function announceError(message: string): void {
  announce(`Error: ${message}`, 'assertive');
}

/**
 * Announce success messages
 */
export function announceSuccess(message: string): void {
  announce(`Success: ${message}`, 'polite');
}

/**
 * Announce loading states
 */
export function announceLoading(isLoading: boolean, resource?: string): void {
  if (isLoading) {
    announce(resource ? `Loading ${resource}` : 'Loading', 'polite');
  } else {
    announce(resource ? `${resource} loaded` : 'Content loaded', 'polite');
  }
}

/**
 * Announce item count changes
 */
export function announceCount(count: number, itemName: string): void {
  const plural = count === 1 ? itemName : `${itemName}s`;
  announce(`${count} ${plural}`, 'polite');
}

export default {
  announce,
  announceNavigation,
  announceError,
  announceSuccess,
  announceLoading,
  announceCount,
};
