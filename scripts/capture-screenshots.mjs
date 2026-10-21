import { chromium, expect } from '@playwright/test';

const base = process.env.SCREENSHOT_URL || 'http://127.0.0.1:8000';
const browser = await chromium.launch(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : {});
try {
  const page = await browser.newPage({ viewport: { width: 1680, height: 1050 }, deviceScaleFactor: 1 });
  await page.goto(base + '/login');
  await page.getByLabel('Email', { exact: true }).fill('sales@demo.minicrm.test');
  await page.getByLabel('Password', { exact: true }).fill(process.env.DEMO_PASSWORD || 'MiniCrmDemo!2026');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByText('Pipeline by stage', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Dismiss notification' }).click();
  await page.waitForTimeout(1200); // Allow the chart's initial animation to finish.
  await page.screenshot({ path: 'docs/screenshots/dashboard.png', fullPage: true });
  await page.getByRole('link', { name: 'Pipeline', exact: true }).click();
  await expect(page.getByLabel('Stage for Maya Chen')).toBeVisible();
  await expect(page.getByText('Converted ✓', { exact: true })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/pipeline.png', fullPage: true });
  await page.getByRole('link', { name: 'Customers', exact: true }).click();
  await expect(page.getByRole('link', { name: 'Northstar Studio', exact: true })).toBeVisible();
  await page.screenshot({ path: 'docs/screenshots/customers.png', fullPage: true });
  await page.getByRole('link', { name: 'Tasks', exact: true }).click();
  await expect(page.getByText('Send Northstar proposal', { exact: true })).toBeVisible();
  await page.setViewportSize({ width: 390, height: 844 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.screenshot({ path: 'docs/screenshots/tasks-mobile.png', fullPage: true });
  console.log('Captured four demo screenshots.');
} finally {
  await browser.close();
}
