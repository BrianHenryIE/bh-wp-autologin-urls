import { test, Page, expect } from '@playwright/test';
import {Locator} from "playwright-core";
import {loginAsAdmin} from "./utilities/wordpress";

test.describe( 'Plugins page tests', () => {

  test.describe.configure({ mode: 'serial' });

  let page: Page;

  let pluginTableRow: Locator

  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();

    await loginAsAdmin(page);

    await page.goto('/wp-admin/plugins.php', {waitUntil:'domcontentloaded'});

    pluginTableRow = page.locator("//*[@data-slug='bh-wp-autologin-urls']");
  });

  test('verify plugin is active', async () => {
    await expect(
        page.locator( '#deactivate-bh-wp-autologin-urls' )
    ).toBeVisible();
  });

  // Fragile.
  test('verify plugin title is correct', async () => {
    await expect(pluginTableRow.locator('.plugin-title'))
        .toContainText('Magic Emails & Autologin URLs');
  });

  test('verify Settings link is present', async () => {
    await expect(pluginTableRow.locator('.row-actions'))
        .toContainText('Settings');
  });

  test('verify Logs link is present', async () => {
   await expect(pluginTableRow.locator('.row-actions'))
       .toContainText('Logs');
  });

  test('verify link to GitHub is present', async () => {
    await expect(pluginTableRow.locator('.column-description'))
        .toContainText('View on GitHub');
  });
});