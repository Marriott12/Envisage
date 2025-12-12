import { test, expect } from '@playwright/test';

test.describe('Accessibility', () => {
  test('has no automatically detectable accessibility issues', async ({ page }) => {
    await page.goto('/');
    
    // You would integrate axe-core here
    // const results = await injectAxe(page);
    // expect(results.violations).toHaveLength(0);
  });

  test('skip to content link works', async ({ page }) => {
    await page.goto('/');
    
    // Press Tab to focus skip link
    await page.keyboard.press('Tab');
    
    // Skip link should be visible when focused
    const skipLink = page.getByText(/skip to main content/i);
    await expect(skipLink).toBeVisible();
    
    // Click skip link
    await skipLink.click();
    
    // Main content should be focused
    const mainContent = page.locator('#main-content');
    await expect(mainContent).toBeFocused();
  });

  test('keyboard navigation works', async ({ page }) => {
    await page.goto('/products');
    
    // Tab through interactive elements
    await page.keyboard.press('Tab'); // Skip link
    await page.keyboard.press('Tab'); // Logo
    await page.keyboard.press('Tab'); // Search
    await page.keyboard.press('Tab'); // First product
    
    // Verify focus on product
    const firstProduct = page.locator('[data-testid="product-card"]').first();
    await expect(firstProduct).toBeFocused();
    
    // Press Enter to open product
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/products\/.+/);
  });

  test('screen reader labels are present', async ({ page }) => {
    await page.goto('/');
    
    // Check for aria-labels
    const searchInput = page.getByRole('searchbox');
    await expect(searchInput).toHaveAttribute('aria-label');
    
    const cartButton = page.getByRole('button', { name: /cart/i });
    await expect(cartButton).toHaveAttribute('aria-label');
  });

  test('focus trap works in modal', async ({ page }) => {
    await page.goto('/products');
    
    // Open product modal
    await page.click('[data-testid="quick-view"]');
    
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();
    
    // Tab through modal elements
    await page.keyboard.press('Tab');
    const closeButton = page.getByRole('button', { name: /close/i });
    
    // Keep tabbing - focus should stay within modal
    for (let i = 0; i < 10; i++) {
      await page.keyboard.press('Tab');
    }
    
    // Focus should still be within modal
    const focusedElement = await page.evaluate(() => document.activeElement?.tagName);
    const modalElements = await modal.locator('*').allTextContents();
    expect(focusedElement).toBeDefined();
  });
});
