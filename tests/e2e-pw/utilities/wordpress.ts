import {expect, Page} from "@playwright/test";

async function loginAsAdmin( page: Page ) {
    await page.goto('/wp-login.php?redirect_to=%2Fwp-admin%2F&reauth=1');
    await page.getByLabel('Username or Email Address').fill('admin');
    // It was filling "password" into the username field.
    await page.locator('#user_pass').focus();
    await page.locator('#user_pass').fill('password');
    await page.getByLabel('Password', {exact: true}).press('Enter');
    await page.waitForLoadState( 'networkidle' );
}

async function createUser(page: Page, username: string = null, email: string = null) {

    username = username ?? 'bob' + Math.random();
    email = email ?? username + '@example.org';

    await page.goto('/wp-admin/user-new.php', {waitUntil: 'domcontentloaded'});

    await page.getByLabel('Username (required)').fill(username);
    await page.getByLabel('Email (required)').fill(email);

    await page.locator('#send_user_notification').uncheck();

    await page.getByRole('button', { name: 'Add New User' }).click();
    await page.waitForLoadState( 'domcontentloaded' ); // "New user created."

    return username;
}

async function logout(page: Page) {

    await page.goto('/wp-admin/', {waitUntil:'domcontentloaded'});

    let logoutLink = await page.evaluate(async() => {
        return document.getElementById('wp-admin-bar-logout').firstChild.getAttribute("href");
    });

    await page.goto(logoutLink, {waitUntil:'domcontentloaded'});

    await expect(page.locator('#login')).toContainText("logged out");
}

export {loginAsAdmin, createUser, logout};