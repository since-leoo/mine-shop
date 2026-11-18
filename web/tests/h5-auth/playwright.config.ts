import { defineConfig } from '@playwright/test'

// 先在 miniprogram 执行 npm run build:h5，再从 web 执行：
// node node_modules/@playwright/test/cli.js test -c tests/h5-auth/playwright.config.ts
// H5_DIST=/absolute/build/path 可回归旧构建；H5_ARTIFACTS 可分别保留红灯/绿灯截图。
export default defineConfig({
  testDir: '.',
  testMatch: '*.spec.ts',
  outputDir: process.env.H5_ARTIFACTS || '/tmp/mine-h5-auth-playwright',
  workers: 2,
  fullyParallel: true,
  timeout: 20_000,
  reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:3999',
    headless: true,
    isMobile: true,
    hasTouch: true,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    serviceWorkers: 'block',
  },
  projects: [320, 375, 390, 430].map(width => ({
    name: `chromium-${width}`,
    use: { browserName: 'chromium' as const, viewport: { width, height: 900 } },
  })),
  webServer: {
    cwd: new URL('../../', import.meta.url).pathname,
    command: 'node node_modules/vite/bin/vite.js preview --config tests/h5-auth/vite.config.ts',
    url: 'http://127.0.0.1:3999',
    reuseExistingServer: false,
  },
})
