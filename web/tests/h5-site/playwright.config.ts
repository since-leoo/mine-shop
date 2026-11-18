import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: '.', testMatch: '*.spec.ts',
  outputDir: process.env.H5_ARTIFACTS || '/tmp/mine-h5-site-playwright',
  workers: 2, fullyParallel: true, timeout: 30_000,
  expect: { timeout: 8_000 }, reporter: [['list']],
  use: { baseURL: 'http://127.0.0.1:4003', headless: true, isMobile: true, hasTouch: true, serviceWorkers: 'block', screenshot: 'only-on-failure', trace: 'retain-on-failure' },
  projects: [320, 375, 430].map(width => ({ name: `chromium-${width}`, use: { browserName: 'chromium' as const, viewport: { width, height: 900 } } })),
  webServer: { cwd: new URL('../../', import.meta.url).pathname, command: 'node node_modules/vite/bin/vite.js preview --config tests/h5-site/vite.config.ts', url: 'http://127.0.0.1:4003', reuseExistingServer: false },
})
