import {test, expect, Page} from '@playwright/test';

test.describe( 'Autologin link tests', () => {

  let loginRedirectUrl = '/wp-admin/user-new.php';

  let page: Page;

  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();

    await page.goto('/wp-login.php', {'waitUntil':'domcontentloaded'});

    await page.getByLabel('Username or Email Address').fill('admin');
    await page.waitForLoadState( 'networkidle' );
    await page.locator('.wp-pwd #user_pass').fill('password');
    await page.locator('#wp-submit').click();

    await page.goto(loginRedirectUrl, {'waitUntil':'domcontentloaded'});
  });

  async function createUser() {
    let username = 'bob' + Math.random();

    await page.getByLabel('Username (required)').fill(username);
    await page.getByLabel('Email (required)').fill(username + '@example.org');

    await page.locator('#send_user_notification').uncheck();

    await page.getByRole('button', { name: 'Add New User' }).click();

    await page.waitForLoadState( 'networkidle' );

    return username;
  }

  async function getAutoLoginUrlFromUserEditPage( username ) {
    await page.goto('/wp-admin/users.php?s=' + username, {'waitUntil':'domcontentloaded'});

    await page.getByRole('link', { name: username, exact: true }).click();

    await page.waitForLoadState( 'networkidle' );

    await page.locator('#autologin-url');

    let autologinUrl = await page.evaluate(async() => {
      return document.getElementById('autologin-url').getAttribute('value');
    });

    return autologinUrl;
  }

  async function logout() {
    let logoutLink = await page.evaluate(async() => {
      return document.getElementById('wp-admin-bar-logout').firstChild.getAttribute("href");
    });

    await page.goto(logoutLink, {'waitUntil':'domcontentloaded'});
  }

  test('Get link from users.php and verify it works to login', async () => {
    let username = await createUser();

    let autologinUrl = await getAutoLoginUrlFromUserEditPage( username );

    await logout();

    await page.goto(autologinUrl, {'waitUntil':'domcontentloaded'});

    await page.goto('/wp-admin/profile.php', {'waitUntil':'domcontentloaded'});

    await expect(page.locator('#wp-admin-bar-my-account')).toContainText('Howdy, ' + username);
  });
});