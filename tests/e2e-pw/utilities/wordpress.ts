import {Page} from "@playwright/test";
import {RequestUtils} from "@wordpress/e2e-test-utils-playwright";

/**
 * Give the page's browser context the administrator session saved by `global-setup.ts`.
 *
 * Pages start out with it (`use.storageState` in `playwright.config.ts`), so this is only needed
 * after `logout()`, or after an autologin URL has logged the page in as somebody else.
 */
async function loginAsAdmin(page: Page, requestUtils: RequestUtils) {
    await page.context().clearCookies();
    await page.context().addCookies(requestUtils.storageState.cookies);
}

async function createUser(requestUtils: RequestUtils, username: string = null, email: string = null, role: string = null) {

    const clean = (value: string) => value.replace(/^[@\W]*/g, '').replace(/[:]/g, '');

    username = clean(username ?? ('bob' + Math.random()));
    email = clean(email ?? (username + '@example.org'));

    await requestUtils.createUser({
        username,
        email,
        // The REST API requires a password; nothing logs in with it.
        password: 'password' + Math.random(),
        // WordPress's default role is "Subscriber".
        roles: role ? [role] : undefined,
    });

    return username;
}

/**
 * Forget the session in this browser only.
 *
 * Not WordPress's logout link: that destroys the session on the server, and it is the one
 * administrator session shared by every spec.
 */
async function logout(page: Page) {
    await page.context().clearCookies();
}

export {loginAsAdmin, createUser, logout};
