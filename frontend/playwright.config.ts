import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: 'tests/e2e',
  timeout: 60_000,
  retries: 1,
  projects: [
    {
      name: 'desktop',
      use: {
        baseURL: 'http://127.0.0.1:5173',
        viewport: { width: 1440, height: 900 },
      },
      testIgnore: /mobile\.spec\.ts/,
    },
    {
      name: 'mobile',
      use: {
        ...devices['iPhone 13'],
        browserName: 'chromium',
        baseURL: 'http://127.0.0.1:5173',
      },
      testMatch: /(mobile|notifications|quotation-response)\.spec\.ts/,
    },
  ],
})
