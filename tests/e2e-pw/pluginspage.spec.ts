import { test, Page, expect } from '@playwright/test';
import {Locator} from "playwright-core";

test.describe( 'Plugins page tests', () => {

  test.describe.configure({ mode: 'serial' });

  let page: Page;

  let pluginTableRow: Locator

  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();

    await page.goto('http://localhost:8889/wp-login.php?redirect_to=http%3A%2F%2Flocalhost%3A8889%2Fwp-admin%2F&reauth=1');
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.getByLabel('Password', {exact: true}).press('Enter');

    await page.goto('http://localhost:8889/wp-admin/plugins.php');

    pluginTableRow = page.locator("//*[@data-slug='bh-wp-autologin-urls']");
  });

  test('verify plugin is active', async () => {
    await expect(
        page.locator( '#deactivate-bh-wp-autologin-urls' )
    ).toBeVisible();
  });

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