import { test, expect } from '@playwright/test';

test.describe('Product Search', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('searches for products', async ({ page }) => {
    const searchInput = page.getByRole('searchbox');
    
    await searchInput.fill('iPhone');
    await page.keyboard.press('Enter');

    // Wait for results
    await page.waitForSelector('[data-testid="search-results"]');
    
    // Verify results
    const results = page.locator('[data-testid="product-card"]');
    await expect(results).toHaveCountGreaterThan(0);
    
    // Verify search term in results
    const firstProduct = results.first();
    await expect(firstProduct).toContainText(/iphone/i);
  });

  test('filters search results', async ({ page }) => {
    await page.goto('/products?q=phone');
    
    // Apply price filter
    await page.click('[data-testid="filter-price"]');
    await page.fill('[data-testid="min-price"]', '500');
    await page.fill('[data-testid="max-price"]', '1000');
    await page.click('[data-testid="apply-filter"]');

    // Verify filtered results
    await page.waitForSelector('[data-testid="search-results"]');
    
    const prices = await page.locator('[data-testid="product-price"]').allTextContents();
    prices.forEach((price) => {
      const numPrice = parseFloat(price.replace(/[^0-9.]/g, ''));
      expect(numPrice).toBeGreaterThanOrEqual(500);
      expect(numPrice).toBeLessThanOrEqual(1000);
    });
  });

  test('voice search works', async ({ page }) => {
    await page.goto('/');
    
    // Click voice search button
    await page.click('[data-testid="voice-search"]');
    
    // Verify microphone permission prompt or active state
    const voiceIndicator = page.getByText(/listening/i);
    await expect(voiceIndicator).toBeVisible({ timeout: 5000 });
  });

  test('visual search works', async ({ page }) => {
    await page.goto('/');
    
    // Click visual search button
    await page.click('[data-testid="visual-search"]');
    
    // Upload image
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles('./test-assets/sample-product.jpg');
    
    // Verify search initiated
    await expect(page.getByText(/searching/i)).toBeVisible();
    
    // Wait for results
    await page.waitForSelector('[data-testid="search-results"]', { timeout: 10000 });
  });

  test('search suggestions appear', async ({ page }) => {
    const searchInput = page.getByRole('searchbox');
    
    await searchInput.fill('lapt');
    
    // Wait for suggestions
    const suggestions = page.locator('[data-testid="search-suggestion"]');
    await expect(suggestions).toHaveCountGreaterThan(0);
    
    // Click first suggestion
    await suggestions.first().click();
    
    // Verify search performed
    await expect(page).toHaveURL(/\/products\?q=/);
  });
});
