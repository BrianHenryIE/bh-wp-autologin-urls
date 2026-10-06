import {Page} from '@playwright/test';
import {test, expect} from '@wordpress/e2e-test-utils-playwright';
import {createUser, logout} from './utilities/wordpress';

test.describe( 'Autologin link tests', () => {

  let loginRedirectUrl = '/wp-admin/user-new.php';

  let page: Page;

  test.beforeAll(async ({ browser }) => {
    // Create page once; it starts logged in as the administrator.
    page = await browser.newPage();

    await page.goto(loginRedirectUrl, {waitUntil:'domcontentloaded'});
  });


  async function getAutoLoginUrlFromUserEditPage( username ) {
    await page.goto('/wp-admin/users.php?s=' + username, {waitUntil:'domcontentloaded'});

    await page.getByRole('link', { name: username, exact: true }).click();
    await page.waitForLoadState( 'domcontentloaded' );

    await page.locator('#autologin-url');

    let autologinUrl = await page.evaluate(async() => {
      return document.getElementById('autologin-url').getAttribute('value');
    });

    return autologinUrl;
  }


  test('Get link from users.php and verify it works to login', async ({ requestUtils }) => {
    let username = await createUser(requestUtils);

    let autologinUrl = await getAutoLoginUrlFromUserEditPage( username );

    await logout(page);

    await page.goto(autologinUrl, {waitUntil:'domcontentloaded'});

    const bodyLocator = page.locator("body")
    await expect(bodyLocator).toHaveClass(/\blogged-in\b/);

    // await page.goto('/wp-admin/profile.php', {waitUntil:'domcontentloaded'});
    //
    // const woocommerceMyAccount = page.locator('.woocommerce-MyAccount-content')
    // if (await woocommerceMyAccount.isVisible()) {
    //   await expect(woocommerceMyAccount).toContainText('Hello ' + username);
    // } else {
    //   await expect(page.locator('#wp-admin-bar-my-account')).toContainText('Howdy, ' + username);
    // }
  });
});