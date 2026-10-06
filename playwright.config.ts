import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
require('dotenv').config();

// The site under test is wp-env's, so default to the port in `.wp-env.json` (which `.wp-env.ci.json`
// matches) rather than repeating it here. `WP_BASE_URL` is the name WordPress's own tooling uses.
const wpEnvPort = require('./.wp-env.json').port ?? 8888;
const BASE_URL = process.env.BASE_URL ?? process.env.BASEURL ?? process.env.WP_BASE_URL ?? `http://localhost:${wpEnvPort}`;

// `@wordpress/e2e-test-utils-playwright` discovers the REST API from this variable alone, ignoring
// Playwright's `baseURL`, and otherwise assumes port 8889.
process.env.WP_BASE_URL = BASE_URL;

// Where `global-setup.ts` saves the administrator's session, and where the `requestUtils` fixture of
// `@wordpress/e2e-test-utils-playwright` reads it from.
process.env.STORAGE_STATE_PATH ??= 'tests/_output/storage-states/admin.json';

// "test:e2e": "wp-scripts test-playwright
/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests/e2e-pw',
  /* Logs in as the administrator once, over HTTP, rather than through wp-login.php in each spec. */
  globalSetup: require.resolve('./tests/e2e-pw/global-setup.ts'),
  // testDir: './vendor/wordpress/wordpress/tests/e2e',
  /* Pattern to match test files. `.spec.[j|t]s` is Playwright default; WordPress uses `.test.[j|t]s` */
  // grep: /(spec|test)/,
  /* The specs share one WordPress install and mutate its users, newsletters and mail log, so
   * they cannot safely run in parallel: concurrent workers also overload the single wp-env
   * container enough for admin pages to time out. The whole suite takes ~25 minutes serially across
   * the three browser projects, most of it in newsletter.spec.ts. */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Retries exist to surface flakiness, not to hide it: a test that only passes on retry still
   * fails the build. Without this, Playwright exits 0 and CI goes green on a flaky run. */
  failOnFlakyTests: !!process.env.CI,
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  // override that location using the PLAYWRIGHT_HTML_REPORT environment variable
  // A fixed folder so CI can upload it as an artifact.
  reporter: [['html', { outputFolder: 'tests/_output/playwright-report', open: 'never' }]],

  timeout: 120000,

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    // wp-env's development environment. `.wp-env.json` sets `testsEnvironment: false`, so there is
    // no separate tests instance.
    baseURL: BASE_URL,

    /* Every browser context starts logged in as the administrator. */
    storageState: process.env.STORAGE_STATE_PATH,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },

  // Folder for test artifacts such as screenshots, videos, traces, etc.
  outputDir: './tests/_output/playwright-results',

  // // path to the global teardown files.
  // globalTeardown: require.resolve('./global-teardown'),
  //
  // // Each test is given 30 seconds.
  // timeout: 30000,

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://127.0.0.1:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
