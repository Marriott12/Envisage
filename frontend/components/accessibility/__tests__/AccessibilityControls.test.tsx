import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { AccessibilityControls } from '@/components/accessibility/AccessibilityControls';

describe('AccessibilityControls', () => {
  beforeEach(() => {
    // Clear localStorage before each test
    localStorage.clear();
    // Reset document classes
    document.documentElement.className = '';
    document.documentElement.style.fontSize = '';
  });

  it('renders toggle button', () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    expect(button).toBeInTheDocument();
  });

  it('opens controls panel when button clicked', () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    
    fireEvent.click(button);
    
    const dialog = screen.getByRole('dialog', { name: /accessibility controls/i });
    expect(dialog).toBeInTheDocument();
  });

  it('adjusts font size when slider changed', async () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    fireEvent.click(button);

    const slider = screen.getByLabelText(/text size/i);
    fireEvent.change(slider, { target: { value: '125' } });

    await waitFor(() => {
      expect(document.documentElement.style.fontSize).toBe('125%');
    });
  });

  it('toggles high contrast mode', async () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    fireEvent.click(button);

    const checkbox = screen.getByRole('checkbox', { name: /high contrast/i });
    fireEvent.click(checkbox);

    await waitFor(() => {
      expect(document.documentElement.classList.contains('high-contrast')).toBe(true);
    });
  });

  it('saves preferences to localStorage', async () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    fireEvent.click(button);

    const slider = screen.getByLabelText(/text size/i);
    fireEvent.change(slider, { target: { value: '150' } });

    await waitFor(() => {
      expect(localStorage.getItem('a11y-font-size')).toBe('150');
    });
  });

  it('resets all settings when reset button clicked', async () => {
    render(<AccessibilityControls />);
    const button = screen.getByRole('button', { name: /accessibility settings/i });
    fireEvent.click(button);

    // Set some preferences
    const slider = screen.getByLabelText(/text size/i);
    fireEvent.change(slider, { target: { value: '150' } });

    const checkbox = screen.getByRole('checkbox', { name: /high contrast/i });
    fireEvent.click(checkbox);

    // Reset
    const resetButton = screen.getByRole('button', { name: /reset to defaults/i });
    fireEvent.click(resetButton);

    await waitFor(() => {
      expect(document.documentElement.style.fontSize).toBe('100%');
      expect(document.documentElement.classList.contains('high-contrast')).toBe(false);
      expect(localStorage.getItem('a11y-font-size')).toBeNull();
    });
  });

  it('loads saved preferences on mount', () => {
    localStorage.setItem('a11y-font-size', '125');
    localStorage.setItem('a11y-high-contrast', 'true');

    render(<AccessibilityControls />);

    expect(document.documentElement.style.fontSize).toBe('125%');
    expect(document.documentElement.classList.contains('high-contrast')).toBe(true);
  });
});
