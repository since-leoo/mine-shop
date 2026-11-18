import { defineConfig } from '@playwright/test'

// cd web && node node_modules/@playwright/test/cli.js test -c tests/diy/playwright.config.ts
export default defineConfig({
  testDir: '.',
  testMatch: '*.spec.ts',
  outputDir: '/tmp/mine-diy-playwright',
  timeout: 30_000,
  expect: { timeout: 8_000 },
  workers: 1,
  reporter: [['list']],
  use: { baseURL: 'http://127.0.0.1:4001', viewport: { width: 1440, height: 1000 }, headless: true, trace: 'retain-on-failure' },
  webServer: {
    cwd: new URL('../../', import.meta.url).pathname,
    command: 'node node_modules/vite/bin/vite.js --config tests/diy/vite.config.ts',
    url: 'http://127.0.0.1:4001',
    reuseExistingServer: false,
  },
})
