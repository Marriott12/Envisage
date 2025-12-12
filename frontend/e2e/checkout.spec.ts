import { test, expect } from '@playwright/test';

test.describe('Checkout Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('complete checkout process', async ({ page }) => {
    // Add item to cart
    await page.click('[data-testid="product-card"]:first-child');
    await page.click('[data-testid="add-to-cart"]');
    
    // Verify cart notification
    await expect(page.getByText(/added to cart/i)).toBeVisible();

    // Go to checkout
    await page.click('[data-testid="cart-icon"]');
    await page.click('[data-testid="checkout-button"]');

    // Step 1: Cart Review
    await expect(page.getByRole('heading', { name: /cart/i })).toBeVisible();
    await page.click('[data-testid="next-step"]');

    // Step 2: Shipping Address
    await page.fill('[name="firstName"]', 'John');
    await page.fill('[name="lastName"]', 'Doe');
    await page.fill('[name="street"]', '123 Main St');
    await page.fill('[name="city"]', 'New York');
    await page.fill('[name="state"]', 'NY');
    await page.fill('[name="postalCode"]', '10001');
    await page.selectOption('[name="country"]', 'US');
    await page.fill('[name="phone"]', '1234567890');
    await page.click('[data-testid="next-step"]');

    // Step 3: Payment
    await page.click('[data-testid="payment-credit-card"]');
    await page.fill('[name="cardNumber"]', '4242424242424242');
    await page.fill('[name="expiry"]', '12/25');
    await page.fill('[name="cvv"]', '123');
    await page.click('[data-testid="next-step"]');

    // Step 4: Review & Place Order
    await expect(page.getByRole('heading', { name: /review/i })).toBeVisible();
    await page.click('[data-testid="place-order"]');

    // Verify order confirmation
    await expect(page.getByText(/order confirmed/i)).toBeVisible({ timeout: 10000 });
  });

  test('validates required fields', async ({ page }) => {
    await page.goto('/checkout');
    
    // Try to proceed without filling required fields
    await page.click('[data-testid="next-step"]');

    // Verify error messages
    await expect(page.getByText(/required/i).first()).toBeVisible();
  });

  test('applies coupon code', async ({ page }) => {
    await page.goto('/checkout');
    
    // Apply coupon
    await page.fill('[data-testid="coupon-input"]', 'SAVE10');
    await page.click('[data-testid="apply-coupon"]');

    // Verify discount applied
    await expect(page.getByText(/discount/i)).toBeVisible();
  });
});
