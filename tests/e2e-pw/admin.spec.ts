// Verify when autologin urls are disabled for admins:
// * The autologin url is not available on the user edit page
// * Emails to admins do not contain autologin urls
// * Magic login button does not work for admins

import {Page} from '@playwright/test';
import {test, expect, RequestUtils} from '@wordpress/e2e-test-utils-playwright';
import {createUser} from './utilities/wordpress';

test.describe( 'Autologin link tests', () => {

    let loginRedirectUrl = '/wp-admin/user-new.php';

    let page: Page;

    test.beforeAll(async ({ browser }) => {
        // Create page once; it starts logged in as the administrator.
        page = await browser.newPage();

        await page.goto(loginRedirectUrl, {waitUntil:'domcontentloaded'});
    });

    async function createAdminUser( requestUtils: RequestUtils, username ) {
        let uidUsernme = username + new Date().toISOString().slice(0,19).replace('T', '-');
        return await createUser(requestUtils, uidUsernme, uidUsernme + '@example.com', 'administrator');
    }

    // ::is_magic_link_enabled()
    test('Admin magic url is not present on users.php', async ({ requestUtils }) => {

        let username = await createAdminUser(requestUtils, 'bobadmin');

        await page.goto('/wp-admin/users.php?s=' + username, {waitUntil:'domcontentloaded'});

        let userRowLocator = await page.locator('.wp-list-table').locator('tr:has-text("' + username + '")').first();
        let idForUser = await userRowLocator.getAttribute('id');
        await page.hover('#' + idForUser);

        await page.waitForTimeout(50);

        await expect(page.locator('.sendmagiclink').first()).not.toBeAttached()
    });

    test('Admin magic url is not present on individual users profile', async ({ requestUtils }) => {
        let username = await createAdminUser(requestUtils, 'bobadmin');

        await page.goto('/wp-admin/users.php?s=' + username, {waitUntil:'domcontentloaded'});

        await page.getByRole('link', { name: username, exact: true }).click();
        await page.waitForLoadState( 'domcontentloaded' );

        await page.locator('#autologin-url');

        await expect(page.locator('#autologin-url')).not.toBeAttached()
    });
});