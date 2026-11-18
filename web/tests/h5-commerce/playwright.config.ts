import { defineConfig } from '@playwright/test'

// cd web && H5_DIST=/absolute/h5/build node node_modules/@playwright/test/cli.js test -c tests/h5-commerce/playwright.config.ts
export default defineConfig({
  testDir: '.', testMatch: '*.spec.ts', outputDir: process.env.H5_ARTIFACTS || '/tmp/mine-h5-commerce-playwright',
  workers: 2, fullyParallel: true, timeout: 25_000, expect: { timeout: 7_000 }, reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:4002', headless: true, isMobile: true, hasTouch: true,
    screenshot: 'only-on-failure', trace: 'retain-on-failure', serviceWorkers: 'block',
  },
  projects: [320, 375, 390, 430].map(width => ({ name: `chromium-${width}`, use: { browserName: 'chromium' as const, viewport: { width, height: 900 } } })),
  webServer: {
    cwd: new URL('../../', import.meta.url).pathname,
    command: 'node node_modules/vite/bin/vite.js preview --config tests/h5-commerce/vite.config.ts',
    url: 'http://127.0.0.1:4002', reuseExistingServer: false,
  },
})
