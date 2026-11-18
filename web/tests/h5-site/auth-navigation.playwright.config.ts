import { defineConfig } from '@playwright/test'
import base from './playwright.config'

export default defineConfig({
  ...base,
  testMatch: 'auth-navigation.spec.ts',
  use: { ...base.use, isMobile: false },
  projects: [320, 375, 430].map(width => ({ name: `firefox-${width}`, use: { browserName: 'firefox' as const, viewport: { width, height: 900 } } })),
})
