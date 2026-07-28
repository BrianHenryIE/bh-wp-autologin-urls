import {expect, Page} from "@playwright/test";

/**
 * Read the body of the most recently logged email to an address, from WP Mail Logging's log.
 */
async function getMostRecentEmailContent(page: Page, email: string, subject: string ) {

    // Check for sent email
    let mailLogUrl = "/wp-admin/admin.php?page=wpml_plugin_log";
    await page.goto(mailLogUrl, {waitUntil: 'domcontentloaded'});

    const row = page.locator('tr:has-text("' + email + '")').first();
    await row.locator('.wp-mail-logging-action-column').first()
        .getByRole('button', { name: 'View log' }).click();

    await page.getByRole('link', { name: 'raw' }).click();

    // `networkidle` never settles here – wp-admin keeps polling the heartbeat API – so wait for
    // the modal's content to be populated instead.
    const emailContent = page.locator('.wp-mail-logging-modal-row-html-container').first();
    await expect(emailContent).not.toBeEmpty();

    return await emailContent.textContent();
}


export {getMostRecentEmailContent};
