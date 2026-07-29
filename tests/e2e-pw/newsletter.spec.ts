import {test, expect, Page} from '@playwright/test';
import {loginAsAdmin, createUser, logout} from './utilities/wordpress';
import {getMostRecentEmailContent} from './utilities/mail';

test.describe( 'The Newsletter Plugin tests', () => {

  let page: Page;

  // async function beforeEach() {
  //   // TODO: Delete all transients.
  // }

  async function addNewsletterSubscriber(page: Page, email: string, firstName: string, lastName: string) {
    await page.goto('/wp-admin/admin.php?page=newsletter_users_new', {waitUntil: 'domcontentloaded'});

    await page.getByPlaceholder('Valid email address').fill(email );
    await page.waitForTimeout(50);
    await page.getByRole('button', {name: '»'}).click();

    await page.locator('#options-name').fill(firstName);
    await page.locator('#options-surname').fill(lastName);

    await page.waitForTimeout(100);
    await page.getByRole('button', {name: ' Save'}).click();
  }

  /**
   * Create a newsletter whose body contains a link to the site, and advance to the send screen.
   *
   * The Newsletter Plugin 9.x drag-and-drop composer no longer offers the "RAW" template or an
   * HTML source editor; raw HTML newsletters are now started from "Add new (raw HTML)" on the
   * newsletters index, which presents a plain `#options-message` textarea.
   */
  async function createRawHtmlNewsletter(page: Page) {
    await page.goto('/wp-admin/admin.php?page=newsletter_emails_index', {waitUntil: 'domcontentloaded'});
    await page.getByRole('link', {name: 'Add new (raw HTML)'}).click();
    await page.waitForLoadState('domcontentloaded');

    // `#options-message` is hidden behind a CodeMirror instance, which the plugin exposes as the
    // global `templateEditor` and which syncs itself back to the textarea when the form submits.
    await page.locator('.CodeMirror').waitFor();
    await page.evaluate(() => {
      const doc = (window as any).templateEditor.getDoc();
      doc.setValue(doc.getValue().replace('<body>', '<body><a href="/">A link</a>'));
    });

    await page.getByRole('button', {name: 'Next »'}).click();
    await page.waitForLoadState('domcontentloaded');
  }

  /**
   * Immediately manually trigger sending.
   *
   * Without intervention, it won't send correctly because of the environment.
   * After 900 seconds a "Run manually" button appears on `newsletter_system_scheduler` page.
   * `<input class="button-primary tnpc-button" type="submit" value="Run manually" onclick="this.form.act.value='trigger';return true;">`
   *
   * This function emulates that button click by filling and submitting the form immediately.
   *
   * `newsletter/system/scheduler.php:118`
   * @see NewsletterSystemAdmin::get_job_status()
   */
  async function manuallyTriggerNewsletterSend( page: Page ) {
    let newsletterSystemSchedulerUrl= "/wp-admin/admin.php?page=newsletter_system_scheduler";
    await page.goto(newsletterSystemSchedulerUrl, {waitUntil: 'domcontentloaded'});

    await Promise.all([
      page.waitForNavigation({waitUntil: 'domcontentloaded'}),
      page.evaluate(() => {
        const form = document.getElementById('tnp-body').querySelector('form');
        form.elements["act"].value='trigger';
        form.submit();
      }),
    ]);
  }

  // This should be in a do-until loop checking that the newsletter has been sent.
  async function waitForNewsletterSendComplete(page: Page) {

    // # TODO: add a check for "newsletter has actually been sent"
    let times = 3;

    let newslettersListUrl = "/wp-admin/admin.php?page=newsletter_emails_index";
    do{
      await page.goto(newslettersListUrl, {waitUntil: 'domcontentloaded'});
      await manuallyTriggerNewsletterSend(page);
      times--;
    } while (times >= 0);
  }

  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();
    await loginAsAdmin(page);
  });

  test('test_logs_in_wpuser', async () => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    await loginAsAdmin(page);

    await createUser( page, firstName, email );

    await addNewsletterSubscriber(page, email, firstName, lastName);

    await createRawHtmlNewsletter(page);

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await page.getByRole('button', {name: 'Send now'}).click();

    await waitForNewsletterSendComplete(page);

    // This is flaky – returning an empty string. TODO: try a different mail logging plugin.
    let emailContent = await getMostRecentEmailContent(page, email, '');

    // A url contained in the newsletter body.
    let newsletterContentUrl = emailContent.match(/href="(.*?)"/)[1];

    await logout(page);

    await page.waitForTimeout(50);
    await page.goto(newsletterContentUrl, {waitUntil:'domcontentloaded'});

    const bodyLocator = page.locator("body")
    await expect(bodyLocator).toHaveClass(/\blogged-in\b/);

    // await page.goto('/wp-admin/profile.php', {waitUntil:'domcontentloaded'});
    // const woocommerceMyAccount = page.locator('.woocommerce-MyAccount-content')
    // if (await woocommerceMyAccount.isVisible()) {
    //   await expect(woocommerceMyAccount).toContainText('Hello ' + firstName);
    // } else {
    //   await expect(page.locator('#wp-admin-bar-my-account')).toContainText('Howdy, ' + firstName);
    // }
  });


  test('test_fills_in_woocommerce_checkout_without_wpuser', async () => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    await loginAsAdmin(page);

    await addNewsletterSubscriber(page, email, firstName, lastName);

    await createRawHtmlNewsletter(page);

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await page.getByRole('button', {name: 'Send now'}).click();

    await waitForNewsletterSendComplete(page);

    // This is flaky – returning an empty string. TODO: try a different mail logging plugin.
    let emailContent = await getMostRecentEmailContent(page, email, '');

    // A url contained in the newsletter body.
    let newsletterContentUrl = emailContent.match(/href="(.*?)"/)[1];

    await logout(page);
    
    await page.waitForTimeout(50);
    
    // Visit the newsletter URL.
    await page.goto(newsletterContentUrl, {waitUntil:'domcontentloaded'});

    // We do not expect to be logged in here because the point is to test the checkout process without being logged in.

    // Shop
    await page.goto('/shop', {waitUntil:'domcontentloaded'});

    await page.waitForTimeout(100);

    // Add item to cart.
    // await page.getByLabel('Add “Test Product” to your cart').click();
    await page.getByRole('button', {name: 'Add to cart'}).click();
    await page.waitForLoadState( 'networkidle' );
    await page.waitForTimeout(100);

    // Visit checkout.
    // await page.goto('/cart', {waitUntil:'domcontentloaded'});
    // await page.waitForLoadState( 'networkidle' );
    await page.goto('/blocks-checkout', {waitUntil:'domcontentloaded'});
    await page.waitForLoadState( 'networkidle' );

    await page.waitForTimeout(250);

    // Name and email should be filled out
    // if (await woocommerceMyAccount.isVisible()) {
    //   await expect(woocommerceMyAccount).toContainText('Hello ' + firstName);
    // } else {
      await expect(page.locator('#email')).toHaveValue(email);
    // }
  });

});