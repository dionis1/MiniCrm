import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  timeout: 45_000,
  retries: 0,
  use: {
    baseURL: 'http://127.0.0.1:8010', browserName: 'chromium',
    viewport: { width: 1440, height: 1000 }, trace: 'retain-on-failure',
    launchOptions: process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : {},
  },
  webServer: {
    command: 'sh scripts/browser-server.sh', url: 'http://127.0.0.1:8010/login',
    timeout: 120_000, reuseExistingServer: false,
    gracefulShutdown: { signal: 'SIGTERM', timeout: 10_000 },
  },
});
