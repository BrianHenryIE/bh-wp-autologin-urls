import {test, expect, Page} from '@playwright/test';

test.describe( 'Autologin link tests', () => {

  let loginRedirectUrl = '/wp-admin/user-new.php';

  let page: Page;

  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();

    await page.goto('/wp-login.php');
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.waitForLoadState( 'networkidle' );
    await page.locator('.wp-pwd #user_pass').fill('password');
    await page.locator('#wp-submit').click();

    await page.goto(loginRedirectUrl);
  });

  test('Get link from users.php and verify it works to login', async () => {

    let username = 'bob' + Math.random();

    await page.getByLabel('Username (required)').fill(username);
    await page.getByLabel('Email (required)').fill(username + '@example.org');

    await page.locator('#send_user_notification').uncheck();

    await page.getByRole('button', { name: 'Add New User' }).click();

    await page.getByRole('link', { name: username, exact: true }).click();

    await page.waitForLoadState( 'networkidle' );

    await page.locator('#autologin-url');

    let autologinUrl = await page.evaluate(async() => {
      return document.getElementById('autologin-url').getAttribute('value');
    });

    let logoutLink = await page.evaluate(async() => {
      return document.getElementById('wp-admin-bar-logout').firstChild.getAttribute("href");
    });

    await page.goto(logoutLink);

    await page.goto(autologinUrl);

    await page.getByRole('link', { name: 'My account' }).click();

    await page.waitForLoadState( 'networkidle' );

    await expect(page.locator('.woocommerce-MyAccount-content')).toContainText('Hello ' + username);
  });
});