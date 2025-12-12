# Testing Guide

## Overview

This project uses a comprehensive testing strategy including:
- **Unit Tests**: Vitest + React Testing Library
- **E2E Tests**: Playwright
- **Component Documentation**: Storybook
- **Accessibility Testing**: @storybook/addon-a11y
- **Visual Regression**: Chromatic (optional)

## Running Tests

### Unit Tests

```bash
# Run all unit tests
npm run test

# Run tests in watch mode
npm run test:watch

# Generate coverage report
npm run test:coverage

# Run specific test file
npm run test AccessibilityControls.test.tsx
```

### E2E Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run tests in UI mode
npm run test:e2e:ui

# Run tests in headed mode
npm run test:e2e:headed

# Run specific test file
npm run test:e2e checkout.spec.ts

# Debug tests
npm run test:e2e:debug
```

### Storybook

```bash
# Start Storybook development server
npm run storybook

# Build Storybook static site
npm run build-storybook
```

## Writing Tests

### Unit Tests

Unit tests should be placed in `__tests__` directories next to the component:

```typescript
// components/MyComponent/__tests__/MyComponent.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import MyComponent from '../MyComponent';

describe('MyComponent', () => {
  it('renders correctly', () => {
    render(<MyComponent />);
    expect(screen.getByRole('button')).toBeInTheDocument();
  });
});
```

### E2E Tests

E2E tests should be placed in the `e2e` directory:

```typescript
// e2e/my-feature.spec.ts
import { test, expect } from '@playwright/test';

test.describe('My Feature', () => {
  test('should work correctly', async ({ page }) => {
    await page.goto('/my-feature');
    await page.click('[data-testid="my-button"]');
    await expect(page.getByText('Success')).toBeVisible();
  });
});
```

### Storybook Stories

Stories should be placed in the `stories` directory or next to components:

```typescript
// stories/MyComponent.stories.tsx
import type { Meta, StoryObj } from '@storybook/react';
import MyComponent from '@/components/MyComponent';

const meta: Meta<typeof MyComponent> = {
  title: 'Components/MyComponent',
  component: MyComponent,
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof MyComponent>;

export const Default: Story = {};

export const WithProps: Story = {
  args: {
    title: 'Custom Title',
  },
};
```

## Testing Best Practices

### 1. Use Testing Library Queries in Order of Priority

1. `getByRole` - Most accessible
2. `getByLabelText` - Good for forms
3. `getByPlaceholderText` - Fallback for forms
4. `getByText` - User-visible text
5. `getByTestId` - Last resort

### 2. Test User Behavior, Not Implementation

```typescript
// ❌ Bad
expect(component.state.isOpen).toBe(true);

// ✅ Good
expect(screen.getByRole('dialog')).toBeVisible();
```

### 3. Use Data Test IDs Consistently

```tsx
<button data-testid="submit-button">Submit</button>
```

### 4. Mock External Dependencies

```typescript
vi.mock('@/lib/api', () => ({
  fetchProducts: vi.fn(() => Promise.resolve([])),
}));
```

### 5. Clean Up After Tests

```typescript
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});
```

## Coverage Goals

- **Statements**: >80%
- **Branches**: >80%
- **Functions**: >80%
- **Lines**: >80%

## Accessibility Testing

All components should pass accessibility tests:

```typescript
test('has no accessibility violations', async () => {
  const { container } = render(<MyComponent />);
  const results = await axe(container);
  expect(results).toHaveNoViolations();
});
```

## Continuous Integration

Tests run automatically on:
- Pull requests
- Push to main branch
- Pre-commit hooks (optional)

## Debugging

### Unit Tests

```bash
# Add debugger statement in test
debugger;

# Run tests in debug mode
npm run test:debug
```

### E2E Tests

```bash
# Use Playwright Inspector
npm run test:e2e:debug

# Use --headed flag to see browser
npm run test:e2e:headed
```

## Resources

- [Vitest Documentation](https://vitest.dev/)
- [Testing Library](https://testing-library.com/)
- [Playwright Documentation](https://playwright.dev/)
- [Storybook Documentation](https://storybook.js.org/)
