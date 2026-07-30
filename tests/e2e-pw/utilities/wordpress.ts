import {expect, Page} from "@playwright/test";

async function loginAsAdmin( page: Page ) {
    await page.goto('/wp-login.php?redirect_to=%2Fwp-admin%2F&reauth=1', {waitUntil: 'load'});

    // Neither field can be trusted to hold what was typed by the time the form submits: the login
    // was seen failing with "the password you entered for the username bob0.399… is incorrect",
    // i.e. the username field had picked up a subscriber created by an earlier test, and per the
    // original note here "password" had also ended up in the username field. Confirm both values
    // stuck before submitting, rather than submitting a form we have not checked.
    const usernameField = page.getByLabel('Username or Email Address');
    const passwordField = page.locator('#user_pass');

    await expect(async () => {
        await usernameField.fill('admin');
        await passwordField.fill('password');
        await expect(usernameField).toHaveValue('admin', {timeout: 1000});
        await expect(passwordField).toHaveValue('password', {timeout: 1000});
    }).toPass({timeout: 30000});

    await passwordField.press('Enter');

    // Not `networkidle`: wp-admin keeps polling the heartbeat API, so it never settles reliably.
    // The admin bar only renders for an authenticated user, so it is proof the login succeeded.
    // The generous timeout covers the login POST itself – the 5s default is not enough while the
    // newsletter specs have the container busy sending.
    await expect(page.locator('#wpadminbar')).toBeVisible({timeout: 60000});
}

async function createUser(page: Page, username: string = null, email: string = null, role: string = null) {

    const clean = (value: string) => value.replace(/^[@\W]*/g, '').replace(/[:]/g, '');

    username = clean(username ?? ('bob' + Math.random()));
    email = clean(email ?? (username + '@example.org'));

    // `load`, not `domcontentloaded`: user-new.php's scripts touch the form as they initialise, and
    // filling before they have run lets them clobber the value written to `#user_login`.
    await page.goto('/wp-admin/user-new.php', {waitUntil: 'load'});

    // Firefox was intermittently ending up with an empty `#user_login` despite the fill – the form
    // then failed validation, no user was created, and the caller searched users.php in vain. Retry
    // the fill until the value sticks rather than submitting a form we have not verified.
    const usernameField = page.locator('#user_login');
    await expect(async () => {
        await usernameField.fill(username);
        await expect(usernameField).toHaveValue(username, {timeout: 1000});
    }).toPass({timeout: 30000});

    await page.locator('#email').fill(email);
    await expect(page.locator('#email')).toHaveValue(email);

    await page.locator('#send_user_notification').uncheck();

    // default role is "Subscriber"
    // <select id="role" name="role">
    if(role) {
        await page.selectOption('select#role', {value: role});
    }

    // WordPress 7.0 renamed the button from "Add New User" to "Add User"; the id is stable.
    await page.locator('#createusersub').click();

    // Wait for the redirect the POST produces, not `waitForLoadState`: that resolves against the
    // still-current user-new.php document when the navigation has not started yet, letting the
    // caller's next `page.goto()` abort the in-flight POST so the user is never created.
    await page.waitForURL(/\/wp-admin\/users\.php/); // "New user created."

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