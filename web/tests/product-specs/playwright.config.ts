import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: '.', testMatch: '*.spec.ts', outputDir: '/tmp/mine-product-specs-playwright', workers: 1, reporter: [['list']],
  use: { baseURL: 'http://127.0.0.1:4027', headless: true, viewport: { width: 1440, height: 1000 } },
  webServer: { cwd: new URL('../../', import.meta.url).pathname, command: 'node node_modules/vite/bin/vite.js --config tests/product-specs/vite.config.ts', url: 'http://127.0.0.1:4027', reuseExistingServer: true },
})
