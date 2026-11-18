import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: '.',
  testMatch: '*.spec.ts',
  outputDir: '/tmp/mine-export-download-playwright',
  workers: 2,
  reporter: [['list']],
  use: { baseURL: 'http://127.0.0.1:2994', headless: true },
  webServer: {
    cwd: new URL('../../', import.meta.url).pathname,
    command: 'node node_modules/vite/bin/vite.js --config tests/export-download/vite.config.ts',
    url: 'http://127.0.0.1:2994',
    reuseExistingServer: false,
  },
})
