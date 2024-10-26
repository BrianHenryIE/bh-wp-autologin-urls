
// rename this to match the plugin name


import {Page} from "@playwright/test";

async function getMostRecentEmailContent(page: Page, email: string, subject: string ) {

    // Check for sent email
    let mailLogUrl = "/wp-admin/admin.php?page=wpml_plugin_log";
    await page.goto(mailLogUrl, {waitUntil: 'domcontentloaded'});

    const row = page.locator('tr:has-text("' + email + '")').first();
    // await page.locator('#the-list').locator('.wp-mail-logging-action-column').first()
    await row.locator('.wp-mail-logging-action-column').first()
        .getByRole('button', { name: 'View log' }).click();

    await page.getByRole('link', { name: 'raw' }).click();
    await page.waitForTimeout(100);

    // TODO: Try another mail logging plugin to avoid flaky tests.
    await page.waitForLoadState( 'networkidle' );
    await page.waitForTimeout(100);

    const emailContent = await page.locator('.wp-mail-logging-modal-row-html-container').first();
    await emailContent.focus();
    var text = '';
    do {
        await page.waitForTimeout(100);
        text = await emailContent.textContent();
    } while (text === null || text.length == 0);
    return text
}


export {getMostRecentEmailContent};