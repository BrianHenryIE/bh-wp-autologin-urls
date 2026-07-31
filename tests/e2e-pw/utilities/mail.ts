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

    // Opening the modal makes `wpml.modal.init()` fetch the body in the default "html" format. Let
    // that land before switching format: `wpml.modal.set()` replaces the whole container, so a
    // response still in flight when "raw" is clicked overwrites the raw body, leaving an empty
    // "html" container behind that never fills. This is what made reading the mail log flaky.
    await expect(page.locator('.wp-mail-logging-modal-row-html-container').first())
        .toBeAttached({timeout: 60000});

    await page.getByRole('link', { name: 'raw' }).click();

    // `networkidle` never settles here – wp-admin keeps polling the heartbeat API – so wait for
    // the modal's content to be populated instead. Match the format-specific class (rendered by
    // `BaseRenderer.php`) so this can only ever read the "raw" response, never the "html" one.
    const emailContent = page.locator('.wp-mail-logging-modal-row-html-container--raw').first();
    await expect(emailContent).not.toBeEmpty({timeout: 60000});

    return await emailContent.textContent();
}


export {getMostRecentEmailContent};
