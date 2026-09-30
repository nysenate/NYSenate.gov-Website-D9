import { defineConfig, devices } from '@playwright/test';

const userAgent = process.env.PANTHEON_TEST_UA;

export default defineConfig({
  testDir: './specs',
  fullyParallel: false,
  workers: 1,
  timeout: 60_000,
  expect: {
    timeout: 10_000,
  },
  reporter: [
    ['line'],
    ['html', { open: 'never' }],
    ['junit', { outputFile: 'test-results/accessibility-junit.xml' }],
  ],
  use: {
    baseURL: process.env.BASE_URL || 'https://www.nysenate.gov',
    bypassCSP: true,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    ...(userAgent ? { userAgent } : {}),
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
