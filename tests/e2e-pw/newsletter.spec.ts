import {Locator, Page} from '@playwright/test';
import {test, expect} from '@wordpress/e2e-test-utils-playwright';
import {loginAsAdmin, createUser, logout} from './utilities/wordpress';
import {getMostRecentEmailContent} from './utilities/mail';

test.describe( 'The Newsletter Plugin tests', () => {

  /* These are far slower than the rest of the suite and exceeded the 120s default: each test creates
   * a subscriber and a newsletter, then `waitForNewsletterSendComplete()` walks the send queue until
   * the email is sent, before reading the mail log. */
  test.describe.configure({ timeout: 420_000 });

  let page: Page;

  // async function beforeEach() {
  //   // TODO: Delete all transients.
  // }

  /**
   * Click a button which submits a form, and wait for the page the POST returns.
   *
   * `click()` resolves as soon as the click is dispatched. When the next step is a `page.goto()`, it
   * can abort the form's POST while it is still in flight, so the subscriber is never saved or the
   * newsletter never queued – seen on WebKit as an email which never arrives.
   */
  async function clickAndWaitForNavigation(page: Page, locator: Locator) {
    await Promise.all([
      page.waitForNavigation({waitUntil: 'domcontentloaded'}),
      locator.click(),
    ]);
  }

  async function addNewsletterSubscriber(page: Page, email: string, firstName: string, lastName: string) {
    await page.goto('/wp-admin/admin.php?page=newsletter_users_new', {waitUntil: 'domcontentloaded'});

    await page.getByPlaceholder('Valid email address').fill(email );
    await page.waitForTimeout(50);
    await page.getByRole('button', {name: '»'}).click();

    await page.locator('#options-name').fill(firstName);
    await page.locator('#options-surname').fill(lastName);

    await page.waitForTimeout(100);
    await clickAndWaitForNavigation(page, page.getByRole('button', {name: ' Save'}));
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

  /**
   * Keep triggering the send queue until the subscriber's email is in WP Mail Logging's log.
   *
   * Each run of the queue only sends a batch of 8, and the newsletter goes to every subscriber, so
   * the number of runs needed grows with the subscribers left behind by earlier test runs.
   */
  async function waitForNewsletterSendComplete(page: Page, email: string) {
    await expect(async () => {
      await manuallyTriggerNewsletterSend(page);
      await page.goto('/wp-admin/admin.php?page=wpml_plugin_log', {waitUntil: 'domcontentloaded'});
      await expect(page.locator('tr:has-text("' + email + '")').first()).toBeVisible({timeout: 2000});
    }).toPass({timeout: 180_000});
  }

  test.beforeAll(async ({ browser }) => {
    // Create page once; it starts logged in as the administrator.
    page = await browser.newPage();
  });

  test('test_logs_in_wpuser', async ({ requestUtils }) => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    // The previous test, or a retry of this one, leaves the page logged out or logged in as a subscriber.
    await loginAsAdmin(page, requestUtils);

    await createUser( requestUtils, firstName, email );

    await addNewsletterSubscriber(page, email, firstName, lastName);

    await createRawHtmlNewsletter(page);

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await clickAndWaitForNavigation(page, page.getByRole('button', {name: 'Send now'}));

    await waitForNewsletterSendComplete(page, email);

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


  test('test_fills_in_woocommerce_checkout_without_wpuser', async ({ requestUtils }) => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    // The previous test, or a retry of this one, leaves the page logged out or logged in as a subscriber.
    await loginAsAdmin(page, requestUtils);

    await addNewsletterSubscriber(page, email, firstName, lastName);

    await createRawHtmlNewsletter(page);

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await clickAndWaitForNavigation(page, page.getByRole('button', {name: 'Send now'}));

    await waitForNewsletterSendComplete(page, email);

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