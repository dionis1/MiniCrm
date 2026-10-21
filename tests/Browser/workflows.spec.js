import { test, expect } from '@playwright/test';

async function register(page) {
  await page.goto('/login');
  await page.getByRole('button', { name: 'New here? Create an account' }).click();
  await page.getByLabel('Name', { exact: true }).fill('Workflow tester');
  await page.getByLabel('Email', { exact: true }).fill('workflow-' + Date.now() + '@example.test');
  await page.getByLabel('Password', { exact: true }).fill('WorkflowSecure!2026');
  await page.getByLabel('Confirm password').fill('WorkflowSecure!2026');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
}

async function createLead(page, name) {
  await page.goto('/leads');
  await page.getByRole('button', { name: 'New lead' }).click();
  const dialog = page.getByRole('dialog', { name: 'New lead' });
  await dialog.getByLabel('Name', { exact: true }).fill(name);
  await page.getByRole('button', { name: 'Save lead' }).click();
  await expect(page.getByRole('button', { name, exact: true })).toBeVisible();
}

test('keyboard stage changes, one-time conversion, and read-only converted lead', async ({ page }) => {
  await register(page);
  await createLead(page, 'Keyboard prospect');
  const stage = page.getByLabel('Stage for Keyboard prospect');
  await stage.focus();
  await Promise.all([page.waitForResponse(response => response.url().includes('/stage') && response.status() === 200), stage.press('ArrowDown')]);
  await expect(stage).toHaveValue('contacted');
  await stage.selectOption('closed_won');
  await expect(stage).toHaveValue('closed_won');
  const conversion = page.getByRole('button', { name: 'Convert to customer' });
  await expect(conversion).toBeVisible();
  page.once('dialog', dialog => dialog.accept());
  await conversion.click();
  await expect(page.getByText('Converted ✓', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Keyboard prospect', exact: true }).click();
  await expect(page.getByText('Converted leads are read-only.')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save lead' })).toBeDisabled();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Keyboard prospect', exact: true })).toBeFocused();
  await page.getByRole('link', { name: 'Customers', exact: true }).click();
  await expect(page.getByRole('link', { name: 'Keyboard prospect', exact: true })).toBeVisible();
});

test('failed stage change rolls back and leaves a usable mobile dialog', async ({ page }) => {
  await register(page);
  await createLead(page, 'Conflict prospect');
  await page.route('**/api/v1/leads/*/stage', route => route.fulfill({ status: 409, contentType: 'application/json', body: JSON.stringify({ message: 'Changed in another tab.' }) }));
  await page.getByLabel('Stage for Conflict prospect').selectOption('proposal');
  await expect(page.getByText('Changed in another tab.')).toBeVisible();
  await expect(page.getByLabel('Stage for Conflict prospect')).toHaveValue('new');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.getByRole('button', { name: 'New lead' }).click();
  const dialog = page.getByRole('dialog', { name: 'New lead' });
  await expect(dialog.getByRole('button', { name: 'Close', exact: true })).toBeFocused();
  await page.keyboard.press('Shift+Tab');
  await expect(dialog.getByRole('button', { name: 'Save lead' })).toBeFocused();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.keyboard.press('Escape');
  await expect(page.getByRole('button', { name: 'New lead' })).toBeFocused();
  await page.route('**/api/v1/tasks**', route => route.fulfill({ status: 401, contentType: 'application/json', body: JSON.stringify({ message: 'Unauthenticated.' }) }));
  await page.getByRole('link', { name: 'Tasks', exact: true }).click();
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByText('Your session expired. Sign in again.')).toBeVisible();
});

test('server search reaches leads beyond the first pipeline page', async ({ page }) => {
  await register(page);
  await page.evaluate(async () => {
    const cookie = document.cookie.split('; ').find(item => item.startsWith('XSRF-TOKEN='));
    const token = decodeURIComponent(cookie.substring('XSRF-TOKEN='.length));
    for (let i = 0; i < 27; i++) {
      const response = await fetch('/api/v1/leads', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': token }, body: JSON.stringify({ name: i === 0 ? 'Older searchable prospect' : 'Prospect ' + i, stage: 'new' }) });
      if (!response.ok) throw new Error('Unable to create browser fixture: ' + response.status);
    }
  });
  await page.goto('/leads');
  await expect(page.getByText('Page 1 of 2 · 27 leads')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Older searchable prospect', exact: true })).toHaveCount(0);
  await page.getByPlaceholder('Find leads by name, email or company').fill('Older searchable');
  await expect(page.getByRole('button', { name: 'Older searchable prospect', exact: true })).toBeVisible();
  await expect(page.getByText('Page 1 of 1 · 1 leads')).toBeVisible();
});

test('password recovery screens provide generic confirmation and token errors', async ({ page }) => {
  await page.goto('/login');
  await page.getByRole('link', { name: 'Forgot your password?' }).click();
  await page.getByLabel('Email', { exact: true }).fill('missing@example.test');
  await page.getByRole('button', { name: 'Send reset link' }).click();
  await expect(page.getByRole('status')).toHaveText('If that account exists, a reset link has been sent.');
  await page.goto('/reset-password/invalid-token?email=missing%40example.test');
  await page.getByLabel('New password', { exact: true }).fill('ResetSecure!2026');
  await page.getByLabel('Confirm password', { exact: true }).fill('ResetSecure!2026');
  await page.getByRole('button', { name: 'Save new password' }).click();
  await expect(page.getByRole('alert')).toContainText('invalid or expired');
});

test('untrusted writes require CSRF protection and private APIs require authentication', async ({ page }) => {
  await page.goto('/login');
  const response = await page.request.post('/login', {
    headers: { Accept: 'application/json', Origin: 'https://untrusted.example.test', 'Sec-Fetch-Site': 'cross-site' },
    data: { email: 'nobody@example.test', password: 'TestingSecure!2026' },
  });
  expect(response.status()).toBe(419);
  expect((await page.request.get('/api/v1/leads')).status()).toBe(401);
});
