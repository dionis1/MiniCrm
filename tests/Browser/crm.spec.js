import { test, expect } from '@playwright/test';

test('register, create lead, manage customer and complete follow-up', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByRole('heading', { name: '◈ MiniCRM' })).toBeVisible();
  await page.getByRole('button', { name: 'New here? Create an account' }).click();
  await page.getByLabel('Name', { exact: true }).fill('Browser Sales Rep');
  await page.getByLabel('Email', { exact: true }).fill('sales-' + Date.now() + '@example.test');
  await page.getByLabel('Password', { exact: true }).fill('very-long-test-password-123');
  await page.getByLabel('Confirm password').fill('very-long-test-password-123');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByRole('heading', { name: 'Pipeline at a glance' })).toBeVisible();

  await page.getByRole('link', { name: 'Pipeline', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Sales pipeline' })).toBeVisible();
  await page.getByRole('button', { name: 'New lead' }).click();
  await page.getByRole('dialog', { name: 'New lead' }).getByLabel('Name').fill('Ada Prospect');
  await page.getByRole('dialog', { name: 'New lead' }).getByLabel('Company').fill('Acme Ltd');
  await page.getByRole('dialog', { name: 'New lead' }).getByLabel('Deal value').fill('1500');
  await page.getByRole('button', { name: 'Save lead' }).click();
  await expect(page.getByRole('button', { name: 'Ada Prospect' })).toBeVisible();

  await page.getByRole('link', { name: 'Customers', exact: true }).click();
  await page.getByRole('button', { name: 'Add customer' }).click();
  await page.getByRole('dialog', { name: 'New customer' }).getByLabel('Company name').fill('Example Corp');
  await page.getByRole('button', { name: 'Save customer' }).click();
  await expect(page.getByRole('link', { name: 'Example Corp' })).toBeVisible();

  await page.getByRole('link', { name: 'Tasks', exact: true }).click();
  await page.getByRole('button', { name: 'New follow-up' }).click();
  await page.getByRole('dialog', { name: 'New follow-up' }).getByLabel('Title').fill('Send proposal');
  await page.getByRole('button', { name: 'Save task' }).click();
  await expect(page.getByText('Send proposal')).toBeVisible();
  await page.getByRole('checkbox', { name: 'Complete Send proposal' }).check();
  await expect(page.getByText('Send proposal')).toHaveCount(0);
});
